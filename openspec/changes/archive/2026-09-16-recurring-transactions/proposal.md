## Why

Users with recurring expenses or income (streaming subscriptions, gym memberships, rent, recurring freelance retainers) currently have to manually re-enter the same transaction every month. The existing installment feature only fits fixed-length, pre-known series (e.g. a 12x purchase) — it cannot model an open-ended subscription that continues until the user cancels it.

## What Changes

- Add a "recurring" toggle to the create-transaction modal, mutually exclusive with the existing installment toggle, available for any transaction type and payment method.
- Introduce a `RecurringTransaction` rule (category, amount, type, payment method, day-of-month, notes, status: active/cancelled, user) that a scheduled job uses to generate the next month's `Transaction` row on the appropriate day.
- Add a scheduled Artisan command that runs daily, finds active recurring rules due to relaunch, and creates one new `Transaction` row per rule for the current cycle, linked back to the rule.
- Each generated `Transaction` carries a reference to its originating `RecurringTransaction` rule so history stays visible even after the rule is cancelled.
- Add a dedicated "Recurring Transactions" page listing all recurring rules (active and cancelled) for the user, showing next relaunch date and generation history, with a cancel action that stops future relaunches without touching already-generated transactions.
- Add navigation entries (desktop sidebar and mobile bottom nav) linking to the new page.
- Ensure dashboard summary cache invalidation fires when the scheduled job generates new transactions.

## Capabilities

### New Capabilities
- `recurring-transactions`: Rules for defining a recurring transaction, the scheduled relaunch/generation job, cancellation behavior, and the dedicated management page for viewing and cancelling recurring series.

### Modified Capabilities
- `financial-ledger`: The create-transaction modal gains a recurring toggle alongside the existing installment toggle; the two are mutually exclusive on a single transaction. Generated transactions carry a `recurring_transaction_id` reference.

## Impact

- **Backend**: new migration for `recurring_transactions` table and a new nullable `recurring_transaction_id` column on `transactions`; new `RecurringTransaction` model; new `RecurringTransactionController` (list/create/cancel); new `StoreRecurringTransactionRequest`; new Artisan command (e.g. `transactions:relaunch-recurring`) registered in `routes/console.php`; new API routes under the existing `auth:sanctum` group; `TransactionController::store` gains recurring-toggle handling (create rule + first transaction) alongside existing installment handling.
- **Frontend**: new `RecurringTransactionsPage.tsx`; new route in `Router.tsx`; new nav links in `Layout.tsx` (sidebar + mobile bottom nav); `TransactionsPage.tsx` create modal gains the recurring toggle; new hooks/service methods in `hooks/api.ts` / `services/api.ts`; new `RecurringTransaction` type in `types/api.ts`; new i18n keys (`recurring.*`, `nav.recurring`).
- **Cache**: the relaunch job must explicitly flush `Cache::tags(["dashboard-summary:{userId}"])` per affected user after generating transactions, since batch/job-driven creation needs the same invalidation the `Transaction::booted()` hook provides for interactive create/delete.
- **No breaking changes** to existing installment or transaction behavior.
