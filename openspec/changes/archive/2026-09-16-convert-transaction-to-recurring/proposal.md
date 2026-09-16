## Why

Recurring transactions can currently only be created from scratch: a user must know upfront, at the moment they log a transaction, that it will repeat. In practice, users often only recognize a pattern after the fact (e.g. they logged a gym payment as a normal transaction and later realize it repeats every month). Today there is no way to turn that existing transaction into a recurring series — the user's only option is to leave it as-is and manually create a separate recurring rule, duplicating the amount/category/day-of-month entry and losing the link to the transaction they already logged.

## What Changes

- Add a new action that converts an existing, non-recurring, non-installment transaction into the seed of a recurring series: it creates an `active` `RecurringTransaction` rule derived from the transaction's own category, type, amount, payment method, and notes (day of month derived from the transaction's date), and links the existing transaction to that new rule — without creating a duplicate transaction.
- The converted transaction's date determines whether the monthly relaunch job immediately generates a new instance (if the transaction is dated in a past cycle) or waits until the next cycle (if dated in the current month), matching how the relaunch job already treats any rule's `last_generated_at`.
- Converting is rejected if the transaction is already linked to a recurring rule, or if it belongs to an installment group — preserving the existing mutual exclusivity between recurring and installment transactions.
- Frontend: a new action on each eligible transaction row in the Transactions page lets the user trigger this conversion, with a confirmation step explaining that a monthly recurring rule will be created.

## Capabilities

### New Capabilities

(none)

### Modified Capabilities

- `recurring-transactions`: adds a new requirement allowing a user to convert an existing one-off transaction into a recurring rule, with rejection scenarios for transactions that are already recurring or part of an installment group.

## Impact

- **Backend**: new `POST /v1/transactions/{id}/convert-to-recurring` endpoint, new `TransactionController::convertToRecurring` method, no new FormRequest. Affects `api/routes/api.php`, `api/app/Http/Controllers/Api/V1/TransactionController.php`. New/updated tests in `api/tests/Feature/`.
- **Frontend**: new API call, hook, and UI action in `web/src/services/api.ts`, `web/src/hooks/api.ts`, `web/src/pages/TransactionsPage.tsx`, plus new i18n keys.
- **No database migration needed** — reuses the existing `recurring_transaction_id` column on `transactions` and the existing `recurring_transactions` table.
