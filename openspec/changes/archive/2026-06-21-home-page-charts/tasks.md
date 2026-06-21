## 1. Frontend Dependency

- [x] 1.1 Install `@mantine/charts` in `web/` (inside the container: `docker compose exec web npm install @mantine/charts`)

## 2. Backend — Expenses by Category Endpoint

- [x] 2.1 Add `byCategory(Request $request): JsonResponse` method to `DashboardController` — query expense transactions for the month grouped by `category_id`, join category name, cap at top 8 by sum and group the rest into "Other", return array of `{ name, value }`
- [x] 2.2 Register route `GET /dashboard/by-category` pointing to `DashboardController@byCategory` in `routes/api.php`

## 3. Backend — Daily Income vs Expenses Endpoint

- [x] 3.1 Add `byDay(Request $request): JsonResponse` method to `DashboardController` — query transactions for the month, group by day, fill in all calendar days of the month with zero income/expense where no transactions exist, return array of `{ day, income, expense }`
- [x] 3.2 Register route `GET /dashboard/by-day` pointing to `DashboardController@byDay` in `routes/api.php`

## 4. Frontend — API Hooks

- [x] 4.1 Add `useDashboardByCategory(month: string)` hook in `web/src/hooks/api.ts` — fetches `GET /dashboard/by-category?month=<month>` with React Query, same pattern as `useDashboardSummary`
- [x] 4.2 Add `useDashboardByDay(month: string)` hook in `web/src/hooks/api.ts` — fetches `GET /dashboard/by-day?month=<month>`

## 5. Frontend — Chart Components in HomePage

- [x] 5.1 Import `DonutChart` from `@mantine/charts` and add the expenses-by-category chart section to `HomePage.tsx` below the summary cards — show empty-state text when there are no expense categories
- [x] 5.2 Import `BarChart` from `@mantine/charts` and add the income-vs-expenses-per-day chart section to `HomePage.tsx` below the donut chart — income bars in green, expense bars in red/orange

## 6. Validation

- [x] 6.1 Verify the frontend build passes (`docker compose exec web npm run build`)
- [x] 6.2 Verify both chart endpoints return correct data for a month with transactions (manually call or check in browser)
- [x] 6.3 Verify both charts update when the month selector changes
- [x] 6.4 Verify empty-state handling when the selected month has no transactions
