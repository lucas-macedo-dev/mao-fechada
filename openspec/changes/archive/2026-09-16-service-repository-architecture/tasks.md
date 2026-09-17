## 1. Scaffolding (foundation, no behavior change)

- [x] 1.1 Create `api/app/Repositories/BaseRepository.php` with model binding via constructor and shared `findOrFail(int $id)`, `create(array $attributes)` methods.
- [x] 1.2 Create `api/app/Services/Concerns/AuthorizesOwnership.php` trait with the shared `ensureOwnership(Request $request, int $ownerUserId): void` logic (matching current `abort(403, __('messages.ownership_denied'))` behavior).
- [x] 1.3 Create `api/app/DataTransferObjects/Input/` and `api/app/DataTransferObjects/Output/` directories with a shared `Output/Arrayable.php` interface (single `toArray(): array` method).
- [x] 1.4 Run the full existing test suite (`api/vendor/bin/pest`) to confirm a clean baseline before starting migrations.

## 2. Migrate Category domain

- [x] 2.1 Create `api/app/Repositories/CategoryRepository.php` (extends `BaseRepository`) with methods covering current query needs: `treeForUser`, `listForUser`, `findWithRelations`, `create`, `update`, `delete`, `hasTransactions`, `hasMismatchedChildren`.
- [x] 2.2 Create input DTOs `api/app/DataTransferObjects/Input/CreateCategoryData.php` and `UpdateCategoryData.php` (readonly, built from validated request arrays).
- [x] 2.3 Create output DTO `api/app/DataTransferObjects/Output/CategoryData.php` implementing `toArray()` reproducing the exact current JSON shape (including nested `parent`/`children` when loaded).
- [x] 2.4 Create `api/app/Services/CategoryService.php` using `AuthorizesOwnership`, moving `validateParent`, self-parent check, and type-mismatch-with-children business rules from `CategoryController`; wraps the `destroy` fallback-category reassignment in `DB::transaction()`.
- [x] 2.5 Rewrite `api/app/Http/Controllers/Api/V1/CategoryController.php` to only validate, map to input DTOs, call `CategoryService`, and return `ApiResponse::data($dto->toArray())`.
- [x] 2.6 Add Unit tests for `CategoryRepository`, `CategoryService`, and the Category DTOs under `api/tests/Unit`.
- [x] 2.7 Run `api/tests/Feature` tests covering categories; confirm no assertion changes needed.

## 3. Migrate Budget domain

- [x] 3.1 Create `api/app/Repositories/BudgetRepository.php` with `listForUserAndMonth`, `upsert` methods matching current query/`updateOrCreate` behavior.
- [x] 3.2 Create input DTO `api/app/DataTransferObjects/Input/CreateBudgetData.php` and output DTO `api/app/DataTransferObjects/Output/BudgetData.php` (including nested `category`).
- [x] 3.3 Create `api/app/Services/BudgetService.php` using `AuthorizesOwnership`, moving the "budgets only for expense categories" rule from `BudgetController`.
- [x] 3.4 Rewrite `api/app/Http/Controllers/Api/V1/BudgetController.php` to validate, map to DTOs, call `BudgetService`, return DTO-shaped `ApiResponse`.
- [x] 3.5 Add Unit tests for `BudgetRepository`, `BudgetService`, and Budget DTOs.
- [x] 3.6 Run `api/tests/Feature` tests covering budgets; confirm no assertion changes needed.

## 4. Migrate RecurringTransaction domain

- [x] 4.1 Create `api/app/Repositories/RecurringTransactionRepository.php` with `listForUser`, `findOrFail`, `cancel` methods.
- [x] 4.2 Create output DTO `api/app/DataTransferObjects/Output/RecurringTransactionData.php` (including nested `category`). No input DTO needed yet for `cancel` (no request body).
- [x] 4.3 Create `api/app/Services/RecurringTransactionService.php` using `AuthorizesOwnership`, moving the "only cancel if active" rule from the controller.
- [x] 4.4 Rewrite `api/app/Http/Controllers/Api/V1/RecurringTransactionController.php` to call `RecurringTransactionService` and return DTO-shaped `ApiResponse`.
- [x] 4.5 Add Unit tests for `RecurringTransactionRepository`, `RecurringTransactionService`, and its DTO.
- [x] 4.6 Run `api/tests/Feature` tests covering recurring transactions; confirm no assertion changes needed.

## 5. Migrate Transaction domain

- [x] 5.1 Create `api/app/Repositories/TransactionRepository.php` covering: filtered/paginated `index` query (reusing `App\Support\TransactionFilterQuery`), `findOrFail`, `create`, `createMany` (installments), `update`, `updateCascadeByInstallmentGroup`, `delete`, `deleteByInstallmentGroup`.
- [x] 5.2 Create input DTOs `api/app/DataTransferObjects/Input/CreateTransactionData.php`, `UpdateTransactionData.php`, `ListTransactionsFilterData.php` (mapping `ListTransactionsRequest`/`StoreTransactionRequest`/`UpdateTransactionRequest` validated arrays).
- [x] 5.3 Create output DTO `api/app/DataTransferObjects/Output/TransactionData.php` (including nested `category`) with a `collection()` static helper for mapping arrays/Eloquent collections.
- [x] 5.4 Create `api/app/Services/TransactionService.php` using `AuthorizesOwnership`, moving: category-type-must-match-type validation, recurring-creation orchestration, installment-group creation orchestration, installment cascade update, convert-to-recurring rule (reject if already installment/recurring), cascade delete for installment groups — each multi-step write wrapped in `DB::transaction()` at the service level exactly as today.
- [x] 5.5 Rewrite `api/app/Http/Controllers/Api/V1/TransactionController.php` to validate, map to input DTOs, call `TransactionService`, return DTO-shaped `ApiResponse` (including pagination meta assembled in the controller from the repository's paginator).
- [x] 5.6 Add Unit tests for `TransactionRepository`, `TransactionService` (covering recurring creation, installment creation, cascade update/delete, convert-to-recurring guard rails), and `TransactionData`.
- [x] 5.7 Run `api/tests/Feature` tests covering transactions; confirm no assertion changes needed.

## 6. Migrate Dashboard domain

- [x] 6.1 Extend `TransactionRepository` (from Section 5) with the date-range aggregate query methods Dashboard needs: `sumByTypeForMonth`, `recentForMonth`, `allForMonth`, `groupedByCategoryForMonth`, `groupedByPaymentMethodForMonth`, `forWeek`.
- [x] 6.2 Create output DTOs `api/app/DataTransferObjects/Output/DashboardSummaryData.php`, `DashboardChartData.php`, `CategoryBreakdownData.php`, `MonthlyComparisonData.php`, `MtdComparisonData.php`, `WeekdayExpenseData.php` matching each current endpoint's exact JSON shape.
- [x] 6.3 Create `api/app/Services/DashboardService.php` using `AuthorizesOwnership`, moving: month resolution, the Redis `Cache::tags(...)->remember(...)` block (caching the DTO's `toArray()` result, same cache key format), by-category grouping/rollup-to-"Outros", by-day/week aggregation, monthly comparison, MTD comparison, by-payment-method grouping — one service method per current controller action.
- [x] 6.4 Rewrite `api/app/Http/Controllers/Api/V1/DashboardController.php` so each action only validates the `month`/`limit` query params and delegates to the matching `DashboardService` method.
- [x] 6.5 Add Unit tests for the new `TransactionRepository` aggregate methods, `DashboardService` (including a test that the cache key/tag format is unchanged), and each Dashboard DTO.
- [x] 6.6 Run `api/tests/Feature` tests covering dashboard endpoints; confirm no assertion changes needed, including cached-response behavior.

## 7. Final verification

- [x] 7.1 Run the full test suite (`api/vendor/bin/pest`) and static analysis (`api/vendor/bin/phpstan` / `composer analyse` if configured) to confirm no regressions across all five migrated domains.
- [x] 7.2 Run `pint`/formatting check (`api/vendor/bin/pint --test`) on all new and modified files.
- [x] 7.3 Manually smoke-test the five migrated endpoint groups against a running local API (transactions CRUD + installments + recurring conversion, budgets, recurring cancel, categories CRUD, dashboard summary/chart/recent/by-category/by-day/comparisons) to confirm response shapes are pixel-identical to pre-refactor behavior.
- [x] 7.4 Update `api/README.md` (or relevant architecture doc) with a short note on the new Controller → DTO → Service → Repository → Model convention for future contributions.
