## 1. Backend endpoint

- [x] 1.1 Add route `POST /v1/transactions/{id}/convert-to-recurring` in `api/routes/api.php`, near the existing `recurring-transactions` routes
- [x] 1.2 Implement `TransactionController::convertToRecurring(Request $request, int $id)`: `findOrFail` + `ensureOwnership`, reject 422 if `installment_group_id !== null`, reject 422 if `recurring_transaction_id !== null`
- [x] 1.3 Inside `DB::transaction`, create the `RecurringTransaction` from the transaction's own `category_id`/`type`/`payment_method`/`amount`/`notes`, `day_of_month` from `transacted_at`, `status` = `active`, `last_generated_at` = `transacted_at` (`Y-m-d`)
- [x] 1.4 Update the existing transaction's `recurring_transaction_id` to the new rule's id (no new `Transaction` row created)
- [x] 1.5 Return `ApiResponse::data($transaction->fresh()->load('category'))`

## 2. Backend tests

- [x] 2.1 Test: converting an eligible transaction creates an `active` `RecurringTransaction` with correct `day_of_month`/`last_generated_at`, links `recurring_transaction_id` back, and creates no additional transaction
- [x] 2.2 Test: converting another user's transaction is rejected (403), no changes made
- [x] 2.3 Test: converting an already-recurring transaction is rejected (422), no changes made
- [x] 2.4 Test: converting an installment-linked transaction is rejected (422), no changes made
- [x] 2.5 Test: converting a transaction dated in the current month, then running `transactions:relaunch-recurring`, generates no duplicate transaction this cycle
- [x] 2.6 Test: converting a transaction dated in a past month, then running `transactions:relaunch-recurring`, generates exactly one new transaction for the current cycle

## 3. Frontend API layer

- [x] 3.1 Add `convertTransactionToRecurring(id)` to `web/src/services/api.ts` (`POST /v1/transactions/{id}/convert-to-recurring`)
- [x] 3.2 Add `useConvertTransactionToRecurring` mutation hook to `web/src/hooks/api.ts`, invalidating `['transactions']`, `['dashboard']`, `['recurring-transactions']` on success

## 4. Frontend UI

- [x] 4.1 Add a third `ActionIcon` (repeat/sync icon) to each transaction row in `web/src/pages/TransactionsPage.tsx`, shown only when `tx.recurring_transaction_id == null && tx.installment_group_id == null`
- [x] 4.2 Wire the icon to open the existing `ConfirmDialog` (same pattern as `handleDelete`), explaining a monthly recurring rule will be created from this transaction
- [x] 4.3 On confirm, call the new mutation, surface success/error via existing `setSuccess`/`setError` + `extractApiError` pattern, close the dialog
- [x] 4.4 Add new i18n keys (`transactions.convert_to_recurring`, `transactions.convert_to_recurring_confirm_title`, `transactions.convert_to_recurring_confirm`, `transactions.convert_to_recurring_success`) to all locale files

## 5. Verification

- [x] 5.1 Run `cd api && ./vendor/bin/pest --filter=RecurringTransaction` and confirm all pass, including new tests
- [x] 5.2 Run `cd api && ./vendor/bin/pest --filter=RelaunchRecurringTransactions` and confirm no regressions
- [x] 5.3 Manually smoke-test the endpoint: create a plain transaction, convert it, confirm response and `GET /v1/recurring-transactions` reflect the new rule with no duplicate transaction
- [x] 5.4 Manually run `php artisan transactions:relaunch-recurring` against a past-dated converted transaction and confirm exactly one new transaction is generated
- [x] 5.5 Manually verify the frontend: action icon visibility rules, confirm dialog copy, successful conversion updates the row's recurring badge and removes the action icon, and the transaction appears on the Recurring Transactions page (confirmed by user)
