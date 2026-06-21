## Why

The HomePage currently shows only summary numbers (total income, expenses, balance) and a recent transactions list. Users have no quick way to see spending patterns by category or how income and expenses are distributed across days in the selected month. Adding two charts closes that gap and makes the dashboard significantly more useful at a glance.

## What Changes

- Add a new API endpoint `GET /dashboard/by-category?month=YYYY-MM` that returns expense totals grouped by category for the selected month.
- Add a new API endpoint `GET /dashboard/by-day?month=YYYY-MM` that returns daily income and expense totals for each day with a transaction in the selected month.
- Add `@mantine/charts` to the frontend (built on Recharts, fits the existing Mantine setup).
- Add two chart components to `HomePage`:
  - **Expenses by category** — donut/pie chart showing each expense category's share of total spending for the month.
  - **Income vs. expenses per day** — bar or area chart showing daily income (green) and expenses (red/orange) for each day in the month.
- Both charts respond to the existing month selector already on the page.

## Capabilities

### New Capabilities
- `dashboard-charts`: Display visual spending and income charts on the HomePage, driven by month-scoped API data.

### Modified Capabilities
- None.

## Impact

- **Backend (`api/`):** New methods on `DashboardController` + two new routes in `routes/api.php`. No schema or model changes needed — queries run over existing `transactions` + `categories` tables.
- **Frontend (`web/`):** New dependency `@mantine/charts`. `HomePage.tsx` gains two chart sections. New frontend hooks for the two new endpoints in `hooks/api.ts`.
- No breaking changes to existing endpoints or i18n keys.
