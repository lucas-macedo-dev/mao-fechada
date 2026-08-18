## 1. Backend: monthly income vs. expenses comparison

- [x] 1.1 Add `monthlyComparison(Request $request)` to `DashboardController`: resolve anchor month from `month` param (default current), return the anchor month plus 2 previous months, oldest first, each with `income`/`expense` totals
- [x] 1.2 Add `GET /dashboard/monthly-comparison` route in `api/routes/api.php`
- [x] 1.3 Add Pest feature test: correct 3 months returned in order, correct totals, empty months return zeros

## 2. Backend: expenses-to-date vs. previous-month-same-period

- [x] 2.1 Add `expensesMtdComparison(Request $request)` to `DashboardController` implementing the cutoff-day logic from design.md Decision 2 (current-month vs. past-month branches, clamped cutoff day)
- [x] 2.2 Add `GET /dashboard/expenses-mtd-comparison` route
- [x] 2.3 Add Pest feature test covering: current-month MTD cutoff, past-month full-month comparison, previous total of zero (`change_percent` is `null`), month-length clamping (e.g. day 31 vs. a 28/29-day previous month)

## 3. Backend: expenses by payment method

- [x] 3.1 Add `byPaymentMethod(Request $request)` to `DashboardController`: expense-type transactions for the selected month, grouped by canonical `payment_method`, returning `{ name, value }[]`
- [x] 3.2 Add `GET /dashboard/by-payment-method` route
- [x] 3.3 Add Pest feature test: correct grouping/totals, empty month returns empty array

## 4. Backend: weekly expenses

- [x] 4.1 Add `weeklyExpenses(Request $request)` to `DashboardController`: expense-type transactions for the current real calendar week (Monday–Sunday), independent of the `month` param, returning one entry per weekday (ISO `1`–`7`) with `expense` total
- [x] 4.2 Add `GET /dashboard/weekly-expenses` route
- [x] 4.3 Add Pest feature test: correct week boundaries, days with no expenses return zero, ownership scoping

## 5. Frontend: types, API calls, hooks

- [x] 5.1 Add response types to `web/src/types/api.ts`: `DashboardMonthlyComparisonItem`, `DashboardMtdComparison`, `DashboardPaymentMethodItem`, `DashboardWeeklyExpenseItem`
- [x] 5.2 Add API calls in `web/src/services/api.ts` (or equivalent) for the 4 new endpoints
- [x] 5.3 Add hooks in `web/src/hooks/api.ts`: `useDashboardMonthlyComparison(month?)`, `useDashboardMtdComparison(month?)`, `useDashboardByPaymentMethod(month?)`, `useDashboardWeeklyExpenses()`

## 6. Frontend: i18n

- [x] 6.1 Add pt-BR and en keys in `web/src/i18n/config.ts` for: monthly comparison chart title, MTD comparison card title/labels, payment-method chart title, weekly expenses chart title, main-categories chart title, expenses-per-day chart title
- [x] 6.2 Confirm existing `transactions.payment.*` keys are reused (not duplicated) for payment-method chart labels

## 7. Frontend: HomePage wiring

- [x] 7.1 Add "Income vs. Expenses" monthly grouped bar chart section (3 months) using `BarChart`
- [x] 7.2 Add "Expenses to date vs. last month" percent-change stat card
- [x] 7.3 Add "Expenses by Payment Method" donut chart section
- [x] 7.4 Add "Weekly Expenses" bar chart section (Mon–Sun)
- [x] 7.5 Add "Main Categories" vertical bar chart section alongside the existing category donut, reusing `useDashboardByCategory` data
- [x] 7.6 Replace the existing daily `BarChart` (income + expense) with a `LineChart` showing only the `expense` series; update section title from "Income vs. Expenses per day" to "Expenses per day"
- [x] 7.7 Add empty-state handling for each new chart consistent with existing charts (e.g. `dashboard.no_expenses` / `dashboard.no_transactions` pattern)

## 8. Verify

- [x] 8.1 Run the full Pest suite (`php artisan test`) inside the `api` container — all SHALL pass
- [x] 8.2 Run `npm run lint` and `npm run build` inside the `web` container — both SHALL pass
- [x] 8.3 Manually verify all 6 charts on HomePage: correct data for current month, correct behavior when navigating months (including MTD and monthly-comparison charts), correct empty states, weekly chart unaffected by month navigation
