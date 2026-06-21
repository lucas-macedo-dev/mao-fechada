## Context

The HomePage already fetches `GET /dashboard/summary?month=YYYY-MM` and renders summary totals + recent transactions using Mantine. The existing `/dashboard/chart` endpoint only returns total income/expense and is not used in the frontend yet. No charting library is currently installed.

The two charts requested need data that doesn't exist in any current endpoint:
- **Expenses by category**: sum of expenses grouped by category name/icon for the month.
- **Income vs expenses per day**: daily aggregates of income and expense amounts for each day in the month.

## Goals / Non-Goals

**Goals:**
- Add two new API endpoints to `DashboardController` (by-category, by-day) returning structured data ready for charting.
- Install `@mantine/charts` and render two chart components in `HomePage` below the summary cards.
- Charts react immediately when the month selector changes (same reactive pattern already in use for summary data).
- Keep implementation beginner-friendly: minimal abstraction, explicit component code.

**Non-Goals:**
- Replacing or modifying existing endpoints (`/summary`, `/chart`, `/recent`).
- Adding charts to any other page.
- Filtering charts by type (income-only or expense-only) beyond what's defined.
- Pagination or drill-down interactions on the charts.

## Decisions

1. **Use `@mantine/charts` for the chart components.**  
   Rationale: It's the official Mantine charting package (wraps Recharts), fits the existing Mantine theme automatically, and keeps the dependency footprint cohesive. Alternative (standalone Recharts) requires manual theming.

2. **Expenses by category → `DonutChart`; daily flow → `BarChart`.**  
   Rationale: A donut chart makes category proportions immediately readable. A bar chart (grouped or stacked) makes daily comparison between income and expense clearer than a line chart, especially for months with sparse data.

3. **Two new backend endpoints (`/dashboard/by-category`, `/dashboard/by-day`) instead of extending `/dashboard/chart`.**  
   Rationale: The new data shapes are different enough from the current `chart` response that extending it would create an inconsistent, hard-to-evolve contract. Separate endpoints are explicit and independently cacheable.

4. **`by-category` returns only expense categories.**  
   Rationale: The user's request is "expenses per category". Income categories are fewer and less useful for spending analysis. Keeping it expense-only avoids chart clutter.

5. **`by-day` returns an entry for every calendar day in the month, zeroing days with no transactions.**  
   Rationale: A bar chart with gaps looks broken. Filling in zeros gives a consistent x-axis across the full month.

6. **New frontend hooks `useDashboardByCategory(month)` and `useDashboardByDay(month)` follow the same pattern as `useDashboardSummary`.**  
   Rationale: Consistent query key pattern, same React Query caching behavior, and easy to invalidate together with the rest of the dashboard data.

## Risks / Trade-offs

- **[Risk] `@mantine/charts` adds bundle weight (Recharts is ~250 kB minified).** → Mitigation: the bundle warning already exists from Mantine core; this is acceptable for a dashboard feature. No lazy-loading needed at this stage.
- **[Risk] Months with many categories may make the donut chart unreadable.** → Mitigation: cap display at top 8 categories by spend, grouping the remainder into "Other" if needed; document this in the spec.
- **[Risk] `/by-day` query may be slow if the user has thousands of transactions.** → Mitigation: query is already scoped to user + month (at most ~31 days of data); no index changes needed at this scale.

## Migration Plan

1. Install `@mantine/charts` in `web/`.
2. Add the two new routes and controller methods in `api/`.
3. Add the two new React Query hooks in `web/src/hooks/api.ts`.
4. Add the chart sections to `HomePage.tsx` below the summary cards.
5. No data migrations, no breaking changes — safe to deploy incrementally.
