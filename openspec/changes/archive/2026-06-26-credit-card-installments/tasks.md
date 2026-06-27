## 1. Database Migration

- [x] 1.1 Create migration to add `installment_group_id` (varchar 36, nullable), `installment_number` (tinyint unsigned, nullable), and `installment_total` (tinyint unsigned, nullable) columns to the `transactions` table
- [x] 1.2 Add composite index on `(user_id, installment_group_id)` in the same migration
- [x] 1.3 Run migration and verify columns are present in the database

## 2. Backend – Transaction Model

- [x] 2.1 Add `installment_group_id`, `installment_number`, `installment_total` to the `#[Fillable]` attribute on `Transaction` model
- [x] 2.2 Add casts for `installment_number` and `installment_total` as integers in the `casts()` method

## 3. Backend – Store Installments (TransactionController@store)

- [x] 3.1 Create `StoreTransactionRequest` validation rules for the new optional fields: `installment_group_id`, `installment_number` (integer, min:1, max:installment_total), `installment_total` (integer, min:1, max:60)
- [x] 3.2 In `TransactionController@store`, detect when `installment_number` and `installment_total` are present in the validated payload
- [x] 3.3 When installment payload is detected, generate a UUID for `installment_group_id` and bulk-create T transaction records inside a `DB::transaction()`, each with `transacted_at` offset by the appropriate number of months relative to the provided date
- [x] 3.4 Return the full list of created installment records (or just the current-month one) with a 201 response

## 4. Backend – Cascade Edit and Delete

- [x] 4.1 In `TransactionController@update`, after ownership check, detect if the transaction has an `installment_group_id`
- [x] 4.2 If yes, apply the update to all transactions sharing that `installment_group_id` (excluding `transacted_at` and `installment_number` from the cascade) inside a `DB::transaction()`
- [x] 4.3 In `TransactionController@destroy`, after ownership check, detect if the transaction has an `installment_group_id`
- [x] 4.4 If yes, delete all transactions sharing that `installment_group_id` inside a `DB::transaction()`

## 5. Backend – Installment Filter on Transaction List

- [x] 5.1 Add `installment` (boolean/nullable) to `ListTransactionsRequest` validation rules
- [x] 5.2 In `TransactionController@index`, when `installment=true` is passed, add `->whereNotNull('installment_group_id')` to the query

## 6. Backend – Dashboard Installments Total Endpoint

- [x] 6.1 Add route `GET /dashboard/installments-total` in `routes/api.php` pointing to `DashboardController@installmentsTotal`
- [x] 6.2 Implement `DashboardController@installmentsTotal`: accept `?month=YYYY-MM`, sum `amount` for the user's expense transactions where `installment_group_id IS NOT NULL` for that month, return `{ month, total }`

## 7. Frontend – New Transaction Form (Installment Fields)

- [x] 7.1 In `TransactionsPage.tsx`, add state for `installmentEnabled` (boolean), `installmentCurrent` (number), and `installmentTotal` (number)
- [x] 7.2 Conditionally render a Mantine `Switch` (installment toggle) below the payment method field — only visible when `paymentMethod === 'cartao_credito'` and `createType === 'saida'`
- [x] 7.3 When the toggle is off or hidden, reset installment state to defaults
- [x] 7.4 When `installmentEnabled` is true, render two `NumberInput` fields: "Parcela atual" (current installment) and "Total de parcelas" (total installments)
- [x] 7.5 Pass `installment_number`, `installment_total` to `createTransaction.mutateAsync` when installment mode is active; omit them otherwise

## 8. Frontend – API Types and Hook

- [x] 8.1 Update `Transaction` type in `web/src/api/types.ts` (or `web/src/types/api.ts`) to include optional `installment_group_id`, `installment_number`, `installment_total` fields
- [x] 8.2 Add `useInstallmentsTotal(month: string)` hook in `web/src/hooks/api.ts` that calls `GET /dashboard/installments-total?month=...`
- [x] 8.3 Add `installment` filter param support to `useTransactions` hook and the underlying API call

## 9. Frontend – Installment Filter on Transactions Page

- [x] 9.1 Add state `showInstallmentsOnly` (boolean) to `TransactionsPage`
- [x] 9.2 Add a Mantine `Switch` or `Checkbox` in the filter section of the transactions list labeled "Somente parcelamentos"
- [x] 9.3 Pass `installment: showInstallmentsOnly || undefined` to the `useTransactions` params

## 10. Frontend – Confirmation Dialogs for Installment Group Edit/Delete

- [x] 10.1 In `TransactionsPage`, when `handleStartEdit` is called on a transaction with `installment_group_id`, show a `window.confirm` (or Mantine Modal) warning that all installments will be updated
- [x] 10.2 In `handleDelete`, when the transaction has `installment_group_id`, replace the existing `window.confirm` with a message specifically saying all installments will be deleted

## 11. Frontend – Home Page Installment Summary Card

- [x] 11.1 In `HomePage.tsx`, call `useInstallmentsTotal(month)` where `month` is the currently selected month
- [x] 11.2 Render a new summary card (using the same `SectionCard` or equivalent card component) showing the installment total with a label like "Compras parceladas"
- [x] 11.3 Display R$ 0,00 when total is zero or data is loading

## 12. Verification

- [x] 12.1 Manually test: create a 3-installment credit card expense for month 2 of 3 → verify 3 transaction records are created across 3 months
- [x] 12.2 Manually test: edit one of the 3 installment records → verify all 3 are updated
- [x] 12.3 Manually test: delete one of the installment records → verify all 3 are deleted
- [x] 12.4 Manually test: filter transactions by installment → verify only installment records appear
- [x] 12.5 Manually test: verify the installment card on the home page shows the correct monthly total
