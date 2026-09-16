## Context

See proposal.md - Why. Recurring transactions today are created only via `TransactionController::store` when `recurring: true` is passed at creation time — that path creates a `RecurringTransaction` rule and the first `Transaction` instance together, atomically. This change adds a retroactive path: converting a transaction that already exists.

Relevant existing behavior this design must stay consistent with:
- `RecurringTransaction` has no `end_date` or frequency field — monthly cadence is implicit, driven by `day_of_month` and `last_generated_at`.
- `RelaunchRecurringTransactions::relaunch()` skips a rule whose `last_generated_at` falls in the current calendar month (`isSameMonth`), and otherwise generates one transaction dated on `day_of_month` (clamped to the last day of shorter months).
- Recurring and installment are mutually exclusive by design (`StoreTransactionRequest`'s `prohibited_if:recurring,true` on installment fields); edits to a generated transaction or its rule are intentionally decoupled (no cascade in either direction).

## Goals / Non-Goals

**Goals:**
- Let a user convert an eligible existing transaction into the first instance of a recurring series, reusing its own fields as the rule template.
- Keep the relaunch job's idempotency guarantee intact — no duplicate transaction is generated for the cycle the converted transaction already belongs to.

**Non-Goals:**
- No reactivation/conversion path for cancelled rules (unchanged from the base feature).
- No editing of the rule's fields independently at conversion time — the rule is derived as-is from the transaction; the user can adjust amount/category afterward via the existing update paths if needed.
- No cascading changes to `RecurringTransaction` when the seed transaction is later edited or deleted (matches existing decoupling behavior).

## Decisions

**Decision: New dedicated endpoint (`POST /v1/transactions/{id}/convert-to-recurring`) rather than overloading `PATCH /v1/transactions/{id}`.**
Rationale: `UpdateTransactionRequest` and `TransactionController::update` handle field edits and installment-group cascade; conflating a structural state transition (creating a new related rule, no request body) into that path would complicate both the request validation and the cascade logic. A dedicated action endpoint mirrors the existing `POST /v1/recurring-transactions/{id}/cancel` pattern (no body, action-shaped URL, ownership check, single responsibility).
Alternative considered: add a `recurring: true` field to `UpdateTransactionRequest`. Rejected because `update()` already branches on `installment_group_id` for cascading edits, and this would add a second, unrelated branch to the same method, plus require distinguishing "convert" from a normal field update in one payload.

**Decision: Reuse the existing transaction row as the first instance; do not create a new `Transaction`.**
Rationale: The transaction already represents that cycle's occurrence. Creating a second row would duplicate it in totals/reports. This diverges intentionally from `store()`'s recurring branch (which always creates both rule and instance together, since no prior transaction exists there).

**Decision: Derive `day_of_month` and `last_generated_at` directly from the transaction's `transacted_at`.**
Rationale: This is exactly what `store()` already does when creating a recurring transaction from scratch, so behavior stays consistent. Setting `last_generated_at` to the transaction's own date is what makes the relaunch job's existing `isSameMonth` check do the right thing automatically — no new logic is needed in `RelaunchRecurringTransactions`.

**Decision: Reject conversion (422) for already-recurring or installment-linked transactions; no new FormRequest class.**
Rationale: These are the same two mutual-exclusivity constraints already enforced at creation time, just checked against the target row's existing state rather than request input. Since the endpoint takes no body, a FormRequest adds no value — the checks happen directly in the controller against the loaded model, same as the `installment_group_id` check already in `update()`.

## Risks / Trade-offs

- **[Risk]** A transaction dated far in the past, once converted, could cause the relaunch job to generate exactly one "catch-up" transaction dated in the *current* cycle on next run, which might read as unexpected if the user expected conversion to be a no-op until next month → **Mitigation**: this matches the spec's explicit scenario ("Converting a transaction dated in a past cycle generates the current cycle's instance on the next relaunch") and is the same behavior a newly created recurring rule would have if it had existed since that date; the frontend confirmation copy should make clear a rule is being created effective immediately.
- **[Risk]** Race condition if a user converts a transaction at the same moment the relaunch job runs → **Mitigation**: the conversion wraps rule creation and the transaction update in `DB::transaction`, matching the existing pattern in `store()`.

## Migration Plan

No database migration required — reuses the existing `recurring_transaction_id` column and `recurring_transactions` table from the base feature. Ship backend endpoint and frontend action together; no feature flag needed since this only adds a new opt-in action, with no changes to existing read/write paths.
