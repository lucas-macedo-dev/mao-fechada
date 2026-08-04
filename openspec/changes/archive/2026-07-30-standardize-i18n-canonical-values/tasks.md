## 1. Backend: `payment_method` data migration

- [x] 1.1 Create migration: widen `transactions.payment_method` ENUM to include both old (`cartao_credito`, `cartao_debito`, `dinheiro`, `pix`, `boleto`, `ted`) and new (`credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, `bank_transfer`) values
- [x] 1.2 In the same migration, `UPDATE` existing rows via a `CASE` mapping old → new (`pix` unchanged)
- [x] 1.3 In the same migration, narrow the ENUM to the new English-only values with `DEFAULT 'pix'`
- [x] 1.4 Implement `down()` as the symmetric reverse (widen → rewrite back → narrow to old values)
- [x] 1.5 Run the migration against local dev DB (`make artisan migrate`) and spot-check existing transaction rows

## 2. Backend: validation rules

- [x] 2.1 `StoreTransactionRequest`/`UpdateTransactionRequest`: change `type` rule to `Rule::in(['income', 'expense'])`; change `payment_method` rule to the new 6 English values
- [x] 2.2 `ListTransactionsRequest`/`GenerateReportRequest`: same `type` and `payment_method` rule updates
- [x] 2.3 `StoreCategoryRequest`/`UpdateCategoryRequest`: change `type` rule to `Rule::in(['income', 'expense'])`

## 3. Backend: remove `TransactionTypeMapper`

- [x] 3.1 `TransactionFilterQuery.php`: replace `where('type', TransactionTypeMapper::toDatabase($filters['type']))` with `where('type', $filters['type'])`
- [x] 3.2 `TransactionController.php` (store + update): remove the `$validated['type'] = TransactionTypeMapper::toDatabase(...)` lines (validation already guarantees `income`/`expense`)
- [x] 3.3 `CategoryController.php` (store + update): same removal
- [x] 3.4 `DashboardController.php`: replace each `whereIn('type', TransactionTypeMapper::incomeValues())` / `expenseValues()` with `where('type', 'income')` / `where('type', 'expense')`; replace the `in_array($tx->type, TransactionTypeMapper::incomeValues())` check with `$tx->type === 'income'`
- [x] 3.5 `MonthlySummaryController.php`: same `whereIn(...)` → `where(...)` simplification
- [x] 3.6 `CsvReportWriter.php`: replace `TransactionTypeMapper::toApi($transaction->type)` with `$transaction->type`
- [x] 3.7 `PdfReportWriter.php`: replace `$transaction->type === TransactionTypeMapper::toDatabase('income')` with `$transaction->type === 'income'`
- [x] 3.8 Delete `api/app/Support/TransactionTypeMapper.php` and its test coverage (if any) once no callers remain
- [x] 3.9 Run `php artisan test` (full Pest suite) and fix any failures

## 4. Frontend: `type` literal cleanup

- [x] 4.1 `ReportsPage.tsx`: change Select option values from `"entrada"`/`"saida"` to `"income"`/`"expense"`
- [x] 4.2 `TransactionsPage.tsx`: change Select option values from `"entrada"`/`"saida"` to `"income"`/`"expense"`; delete `normalizeType()` and use the `income`/`expense` value directly wherever it was called
- [x] 4.3 `CategoriesPage.tsx`: change Select option values from `"entrada"`/`"saida"` to `"income"`/`"expense"`; delete the `formTypeNorm`/`parentDbType` PT-BR round-trip and use `income`/`expense` directly in form state
- [x] 4.4 `HomePage.tsx`: simplify `type === 'income' || type === 'entrada'` checks to `type === 'income'`

## 5. Frontend: `payment_method` rename

- [x] 5.1 `TransactionsPage.tsx`: rename the `paymentMethods` constant array to `["credit_card", "debit_card", "cash", "pix", "bank_slip", "bank_transfer"]`
- [x] 5.2 `TransactionsPage.tsx`: update the `formPaymentMethod === "cartao_credito"` comparisons to `"credit_card"`
- [x] 5.3 `web/src/i18n/config.ts`: rename the 6 `transactions.payment.*` keys (both `pt-BR` and `en` blocks) to match the new English codes, keeping the displayed text unchanged
- [x] 5.4 `web/src/types/api.ts`: tighten `payment_method: string` to a literal union of the 6 canonical values

## 6. Verify

- [x] 6.1 `npm run lint` and `npm run build` in `web` pass with no leftover `entrada`/`saida`/Portuguese `payment_method` literals in `web/src`
- [x] 6.2 `php artisan test` passes in `api`, including transaction/category/report/dashboard tests exercising `type` and `payment_method`
- [x] 6.3 Manually create/edit a transaction and a category in the running app for both types and all payment methods, confirming filters, dashboard totals, and CSV/PDF report export all still work correctly
