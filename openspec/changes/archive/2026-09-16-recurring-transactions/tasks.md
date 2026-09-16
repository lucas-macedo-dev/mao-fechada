## 1. Database

- [x] 1.1 Create migration for `recurring_transactions` table: `user_id` (FK, cascade delete), `category_id` (FK, restrict delete), `type` (enum income/expense), `amount` (decimal 12,2), `payment_method` (enum, canonical values), `notes` (nullable text), `day_of_month` (tinyint unsigned, 1-31), `status` (enum active/cancelled, default active), `last_generated_at` (nullable date), timestamps; index on `(user_id, status)`.
- [x] 1.2 Create migration adding nullable `recurring_transaction_id` (FK to `recurring_transactions`, restrict-on-delete) to `transactions`, indexed alongside `user_id`.

## 2. Backend Model & Validation

- [x] 2.1 Create `RecurringTransaction` model (`api/app/Models/RecurringTransaction.php`) with fillable fields, casts (`amount` decimal:2, `day_of_month` integer, `last_generated_at` date), `belongsTo(User)`, `belongsTo(Category)`, `hasMany(Transaction)`.
- [x] 2.2 Add `recurring_transaction_id` to `Transaction`'s fillable fields and casts; add `belongsTo(RecurringTransaction)` relation.
- [x] 2.3 Create `StoreRecurringTransactionRequest` (or extend `StoreTransactionRequest`) validating category/type match, positive amount, `day_of_month` 1-31, and rejecting a payload that sets both installment and recurring fields simultaneously.
- [x] 2.4 Update `TransactionController::store` to branch on a `recurring` flag: when set, create a `RecurringTransaction` rule (status `active`, `day_of_month` derived from the submitted date, `last_generated_at` set to the submitted date's cycle) and one `Transaction` linked to it via `recurring_transaction_id`, inside a `DB::transaction()`.
- [x] 2.5 Confirm `TransactionController::update`/`destroy` do NOT cascade for transactions with a `recurring_transaction_id` (only installment-group cascade applies) — add an explicit test/check since this is a new code path adjacent to existing cascade logic.

## 3. Backend Recurring Rule Management

- [x] 3.1 Create `RecurringTransactionController` with `index` (list authenticated user's rules, active first) and `cancel` (set `status = cancelled` for a rule owned by the authenticated user; no-op if already cancelled; 403/404 if not owned).
- [x] 3.2 Register routes in `api/routes/api.php` inside the existing `auth:sanctum` + `verified` group: `GET /recurring-transactions`, `POST /recurring-transactions/{id}/cancel`.
- [x] 3.3 Add an API resource/transformer for `RecurringTransaction` including category, last-generated date, and status, matching the response shape conventions used for `Transaction`.

## 4. Scheduled Relaunch Job

- [x] 4.1 Create Artisan command `api/app/Console/Commands/RelaunchRecurringTransactions.php` (signature `transactions:relaunch-recurring`): query `RecurringTransaction::where('status', 'active')`, skip rules already generated for the current year-month cycle (compare `last_generated_at`), skip rules whose `day_of_month` hasn't been reached yet this cycle.
- [x] 4.2 For each due rule, create the `Transaction` via `Model::create()` (not a mass insert, per design.md Decision 3) dated on `day_of_month` clamped to the last day of the current month when shorter, linked via `recurring_transaction_id`; update the rule's `last_generated_at`.
- [x] 4.3 Wrap each rule's generation in its own try/catch so one rule's failure doesn't abort the batch; log failures.
- [x] 4.4 Register the command in `api/routes/console.php`: `Schedule::command('transactions:relaunch-recurring')->dailyAt(...)`, following the existing `reports:purge-expired` pattern.
- [x] 4.5 Verify (manually or via test) that generating a transaction through `Model::create()` in this command still triggers `Transaction::booted()`'s cache-tag flush for the affected user, satisfying the "Relaunch invalidates the dashboard summary cache" spec scenario.

## 5. Frontend: Types, Services, Hooks

- [x] 5.1 Add `RecurringTransaction` interface to `web/src/types/api.ts`; add `recurring_transaction_id` to the `Transaction` interface.
- [x] 5.2 Add `listRecurringTransactions` and `cancelRecurringTransaction` methods to `web/src/services/api.ts`, following existing method conventions.
- [x] 5.3 Add `useRecurringTransactions` (query) and `useCancelRecurringTransaction` (mutation, invalidating `['recurring-transactions']` and `['transactions']`/`['dashboard']` on success) to `web/src/hooks/api.ts`.
- [x] 5.4 Extend `useCreateTransaction`'s payload type/call site to optionally include the recurring flag/day-of-month.

## 6. Frontend: Create Modal

- [x] 6.1 Add a recurring `Switch` toggle to the create-transaction form in `web/src/pages/TransactionsPage.tsx`, visible for any type/payment method, create-mode only.
- [x] 6.2 Make the recurring and installment toggles mutually exclusive in the UI (toggling one hides/disables and clears the other's state).
- [x] 6.3 Update `handleFormSubmit` to include the recurring flag in the create payload when the toggle is on, and to omit installment fields in that case.

## 7. Frontend: Recurring Transactions Page

- [x] 7.1 Create `web/src/pages/RecurringTransactionsPage.tsx` listing rules via `useRecurringTransactions`, showing category, amount, day of month, status badge (active/cancelled), and last-generated date.
- [x] 7.2 Add a cancel action (button + `ConfirmDialog`, matching the existing installment confirm-dialog pattern) that calls `useCancelRecurringTransaction`, disabled/hidden for already-cancelled rules.
- [x] 7.3 Add route `/recurring-transactions` in `web/src/Router.tsx` wrapped in `PrivateRoute` + `Layout`, following the `/transactions` route pattern.

## 8. Frontend: Navigation

- [x] 8.1 Add a "Recurring Transactions" link to the desktop sidebar nav in `web/src/components/Layout.tsx`, with a FontAwesome icon (e.g. `fa-arrows-rotate`).
- [x] 8.2 Add an equivalent entry to the mobile bottom nav in `Layout.tsx`, or nest it under the existing Transactions entry if bottom-nav space is constrained — decide based on how the other 4 items lay out.

## 9. i18n

- [x] 9.1 Add `nav.recurring` and `recurring.*` translation keys (toggle label, page title, status labels, cancel confirm dialog text, day-of-month label) to all locale files used by `web/src/i18n/config.ts`.

## 10. Testing

- [x] 10.1 Backend feature tests: creating a recurring transaction creates both the rule and the first transaction; validation rejects invalid payloads and simultaneous installment+recurring flags.
- [x] 10.2 Backend feature/unit tests for the relaunch command: generates exactly one transaction per due active rule per cycle, skips cancelled rules, skips already-generated cycles (idempotency), clamps short months correctly, flushes the dashboard cache tag.
- [x] 10.3 Backend feature tests for cancel endpoint: cancels an owned active rule, rejects cancelling another user's rule, no-ops on an already-cancelled rule, and confirms a cancelled rule is excluded from the next relaunch run.
- [x] 10.4 Frontend: manually verify the create modal toggle behavior (mutual exclusivity, submission payload) and the recurring transactions page (list, status display, cancel flow) in the browser per the project's UI verification convention.

## 11. Documentation

- [x] 11.1 Sync the `recurring-transactions` and `financial-ledger` deltas into `openspec/specs/` (via `/opsx:archive` at the end of implementation, not manually now).
