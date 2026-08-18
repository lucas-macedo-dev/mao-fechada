## Why

The HomePage dashboard (`web/src/pages/HomePage.tsx`) currently shows only two charts scoped to a single selected month: an expenses-by-category donut and an income-vs-expenses-per-day bar chart (`api/app/Http/Controllers/Api/V1/DashboardController.php`, endpoints `by-category` and `by-day`). That single-month view gives no sense of trend across months, no comparison against recent spending pace, no breakdown by how money was actually paid (`payment_method` on `Transaction` — `credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, `bank_transfer`), and no short-term (weekly) view. The daily chart also mixes income and expense bars per day, which is less legible than a single expense trend line for the "how are my expenses trending this month" question it's actually meant to answer. This change adds the missing views and simplifies the daily chart to the metric it's meant to show.

## What Changes

- Add a monthly trend chart: **Income vs. Expenses** grouped by month, showing the currently selected month plus the 2 previous months (3 grouped bars). New endpoint `GET /dashboard/monthly-comparison`.
- Add an **Expenses to date vs. same period last month** indicator: a single percent-change stat (e.g. "+12% vs last month") comparing the selected month's expenses through a cutoff day against the previous month's expenses through the same cutoff day. New endpoint `GET /dashboard/expenses-mtd-comparison`.
- Add an **Expenses by payment method** chart (donut, matching the existing category chart's visual style) for the selected month. New endpoint `GET /dashboard/by-payment-method`.
- Add a **Weekly expenses** chart: a bar per day (Monday–Sunday) of expenses for the current real-world calendar week, independent of the month selector. New endpoint `GET /dashboard/weekly-expenses`.
- Add a **Main categories** vertical bar chart alongside (not replacing) the existing expenses-by-category donut, reusing the existing `GET /dashboard/by-category` data with a second visualization.
- Replace the existing **"Income vs. Expenses per day"** bar chart with an **"Expenses per day"** line chart for the selected month, reusing the existing `GET /dashboard/by-day` endpoint (its `income` field simply goes unused by this chart).
- **BREAKING**: none. All existing endpoints and response shapes are unchanged; this is additive except for the HomePage layout/chart swap described above.

## Capabilities

### New Capabilities
(none — this extends the existing `dashboard-charts` capability)

### Modified Capabilities
- `dashboard-charts`: adds four new chart requirements (monthly income/expenses comparison, MTD expense comparison indicator, expenses-by-payment-method, weekly expenses), adds a main-categories bar chart requirement alongside the existing category donut, and replaces the income-vs-expenses-per-day requirement with an expenses-per-day requirement.

## Impact

- `api/app/Http/Controllers/Api/V1/DashboardController.php`: add `monthlyComparison`, `expensesMtdComparison`, `byPaymentMethod`, `weeklyExpenses` actions.
- `api/routes/api.php`: add 4 new `GET /dashboard/*` routes.
- `web/src/pages/HomePage.tsx`: add 4 new chart sections, add a second (bar) rendering of the existing category data, replace the daily `BarChart` with a `LineChart`.
- `web/src/hooks/api.ts`, `web/src/services/api.ts` (or equivalent): add 4 new query hooks/API calls.
- `web/src/types/api.ts`: add response types for the 4 new endpoints.
- `web/src/i18n/config.ts`: add new translation keys for chart titles/labels (pt-BR and en); reuse existing `transactions.payment.*` keys for payment-method labels.
- No database migrations needed — all new endpoints read existing `transactions` data.
