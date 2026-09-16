## Context

See proposal.md - Why. Relevant existing pieces this design builds on or must coexist with:

- `Transaction` model (`api/app/Models/Transaction.php`) has a `booted()` hook that flushes `Cache::tags(["dashboard-summary:{userId}"])` on `saved`/`deleted` model events. This fires automatically for `Model::create()`/`save()`/`delete()` but NOT for query-builder mass `update()`/`insert()`/`delete()`.
- Installments (`credit-card-installments` spec) pre-create every future row in one request-time `DB::transaction()` loop using `Model::create()` per row — fine for a fixed, bounded count (max 60), but wrong for an open-ended series with no end date.
- Laravel 11-style scheduling: commands live in `api/app/Console/Commands/`, registered with one line in `api/routes/console.php` (e.g. the existing `reports:purge-expired` daily command). `QUEUE_CONNECTION=redis` is already configured but no job currently needs it at scale.
- Frontend create-transaction form (`web/src/pages/TransactionsPage.tsx`) is hand-rolled `useState`, no `@mantine/form`; the installment toggle there is the direct UI precedent for the new recurring toggle.

## Goals / Non-Goals

**Goals:**
- Model a recurring series as a standalone rule, independent of any single generated transaction, so cancellation and lifecycle state don't depend on there being a "latest" transaction row to key off of.
- Generate exactly one transaction per rule per monthly cycle, on the correct day of month, without operator intervention.
- Make cancellation immediate and non-destructive: stops future generation, never touches past transactions.
- Reuse the existing cache-invalidation contract (`dashboard-summary:{userId}` tag) rather than inventing a second mechanism.

**Non-Goals:**
- No support for non-monthly cadences (weekly, yearly, custom intervals) — monthly only, matching the stated subscription use case.
- No retroactive backfill of missed cycles beyond the single "catch up if the job didn't run" case described below — this is not a general backfill/repair tool.
- No proration, amount escalation, or price-change scheduling for a recurring series — amount is fixed per rule until the user edits the generated transaction (which does not cascade back to the rule, matching the mutual-exclusivity decision with installments).
- No email/push notification on relaunch or upcoming charge — out of scope for this change.

## Decisions

### 1. Dedicated `recurring_transactions` table, not a flat group-id column on `transactions`
A `RecurringTransaction` row is the rule (category_id, type, amount, payment_method, notes, user_id, day_of_month, status, last_generated_at). Each generated `Transaction` gets a nullable `recurring_transaction_id` FK back to it.

Alternative considered: mirror installments exactly (`recurring_group_id` string column, no separate table). Rejected because a rule needs to exist and be cancellable *before* any relaunch has necessarily happened, and "cancelled with zero transactions generated yet" is a real state (a lookahead job or a rule created today for a relaunch day already past this month). A flat group-id has no row to hold that status if the rule hasn`t produced a transaction. A dedicated table also makes the management page a simple, direct query instead of a `GROUP BY` over transactions.

### 2. Scheduled job generates one cycle at a time (just-in-time), not a pre-created rolling window
A daily Artisan command (`transactions:relaunch-recurring`) queries `RecurringTransaction::where('status', 'active')` and, for each rule, checks whether a transaction for the current cycle already exists (`last_generated_at` is not in the current year-month). If not, and today's date has reached the rule's `day_of_month` (clamped to the last day of the month when the month is shorter), it creates the transaction and updates `last_generated_at`.

Alternative considered: pre-create a rolling N-month window (like installments) and top it up periodically. Rejected per user's confirmed preference — it adds "window maintenance" complexity (extend-on-schedule, handle cancellation of already-created future rows) for no real benefit, since a subscription has no fixed horizon to pre-materialize toward.

Idempotency: the `last_generated_at` (year-month) check on the rule is the guard against duplicate generation if the command runs twice in a day or is retried. This is simpler than a unique DB constraint on `(recurring_transaction_id, cycle)` but is enforced at the application level in the same code path that creates the transaction, inside a per-rule DB transaction, so it is not subject to a race between concurrent job runs in practice (single scheduled command, not parallelized workers).

### 3. Cache invalidation: explicit flush in the job, not reliance on the model event
Since generation happens inside an Artisan command looping over many users' rules, each created `Transaction` still goes through `Model::create()` (not a mass insert), so the existing `Transaction::booted()` `saved` hook DOES fire per row automatically. No extra invalidation code is strictly required — this is called out explicitly in the spec (`Relaunch invalidates the dashboard summary cache` scenario) as a behavior guarantee, and the design keeps generation on the `Model::create()` path specifically so that guarantee holds without duplicating the invalidation logic in the command. If a future optimization moves generation to a bulk insert for performance, that change must add an explicit per-user cache flush at that time.

### 4. Recurring and installment toggles are mutually exclusive in the UI and validation
Both are meaningful only for a "this transaction repeats" concept, and combining them (e.g. "installment 3 of 12, also recurring") has no clear semantics. The create form shows only one toggle's fields at a time; the backend `StoreTransactionRequest` rejects a payload that sets both installment and recurring fields.

### 5. Cancel is a status flip, not a delete
`RecurringTransaction.status` moves `active` → `cancelled`. The rule row itself is kept (not deleted) so the management page can still show cancelled series and their history for reference. There is no `active` ⇐ `cancelled` reactivation path in this change — the spec only requires stopping future relaunches, not resuming them; a user who wants to resume creates a new recurring transaction.

## Risks / Trade-offs

- [Risk] Day-of-month clamping on short months (e.g. rule set on the 31st) means some months relaunch on the 28th/29th/30th instead — the "same day" expectation from the proposal isn't literally true for those months. → Mitigation: document this clamping behavior in the recurring transactions page (e.g. "relaunches on the 31st, or last day of shorter months") so it's not a silent surprise.
- [Risk] If the scheduled command fails to run for one or more days (deploy issue, server downtime), a rule due earlier in the month could be skipped for that cycle if the check window logic isn't careful. → Mitigation: the due-check is "day_of_month has been reached AND no transaction exists yet for this cycle," not "day_of_month == today," so a command that runs late (or catches up the next day) still generates the missed cycle's transaction once, just a few days late — never silently skipped, never duplicated.
- [Risk] A user could cancel a rule the same day it was due to relaunch, racing the scheduled command. → Mitigation: acceptable ambiguity — whichever happens first wins (either the last transaction is generated before cancellation takes effect, or cancellation prevents that cycle's transaction). Not worth additional locking complexity for a personal finance tool.
- [Trade-off] No reactivation of a cancelled rule means a user who cancels by mistake must recreate the recurring transaction from scratch (losing the `day_of_month`/history linkage as a single series). Acceptable given the spec's explicit scope (cancel only) and keeps the state machine simple (two states, one direction).

## Migration Plan

1. Migration: create `recurring_transactions` table (`user_id`, `category_id`, `type`, `amount`, `payment_method`, `notes`, `day_of_month`, `status`, `last_generated_at`, timestamps).
2. Migration: add nullable `recurring_transaction_id` FK (restrict-on-delete, matching `category_id`'s convention) to `transactions`.
3. Ship model, controller, form request, routes, and Artisan command together; register the schedule entry in `routes/console.php`.
4. Ship frontend changes (toggle in create modal, new page, nav entries, hooks/types) in the same change since the toggle and the page are both required for the feature to be usable end to end.
5. No backfill needed — this is new functionality with no existing data to migrate. No feature flag: the toggle only appears in the create modal and the page is net-new, so there's no behavior change for users who don't opt in.
6. Rollback: standard migration rollback (drop `recurring_transactions`, drop the FK column); no data loss risk since rollback only applies before any production data exists in the new table, or the user accepts losing recurring-rule metadata (generated transactions themselves are untouched since the FK is nullable, not cascade-delete, on the transaction side... actually confirm restrict-on-delete direction in tasks so a rollback attempt with existing linked transactions fails loudly rather than silently orphaning data).
