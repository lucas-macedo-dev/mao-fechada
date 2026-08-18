## Context

Verified current state (2026-07-29):
- `DashboardController` (`api/app/Http/Controllers/Api/V1/DashboardController.php`) exposes `summary`, `chart`, `recent`, `byCategory`, `installmentsTotal`, `byDay`, all scoped to a single `month=YYYY-MM` query param via `resolveMonth()`/`baseQuery()`.
- `byCategory` already groups expenses by **main category** (`$transaction->category?->parent_id ?? $transaction->category_id`, then `$category?->parent ?? $category`), caps at top 8 + "Outros" — this exact grouping is reused as-is for the new "Main categories" bar chart, no backend change needed there.
- `Transaction.type` is `income` | `expense` (already migrated off `entrada`/`saida`, per the separate `standardize-i18n-canonical-values` change). `payment_method` is canonical: `credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, `bank_transfer` (per `2026_07_29_000100_migrate_transactions_payment_method_to_canonical_values.php`).
- `web/src/i18n/config.ts` already has `transactions.payment.credit_card` / `debit_card` / `cash` / `pix` / `bank_slip` / `bank_transfer` label keys in both `pt-BR` and `en` — reused for the new payment-method chart instead of adding a parallel set.
- HomePage (`web/src/pages/HomePage.tsx`) uses `@mantine/charts` (`DonutChart`, `BarChart`) exclusively; no other charting library is present. `LineChart` from the same package is used for the new expenses-per-day chart, keeping one charting dependency.
- All existing dashboard hooks follow the pattern `useDashboard<Thing>(month?: string)` in `web/src/hooks/api.ts`, calling `api.getDashboard<Thing>(month)`; new hooks follow the same shape.

## Goals / Non-Goals

**Goals:**
- Give the dashboard a sense of trend (3-month comparison), pace (MTD vs. last month), and composition (payment method, main categories) beyond the current single-month category/day view.
- Reuse existing grouping logic (`byCategory`'s main-category rollup, canonical `payment_method` values, existing i18n keys) rather than introducing parallel implementations.
- Keep every new endpoint scoped per-user via the existing `baseQuery()`/`ensureOwnership()` pattern already used by all `DashboardController` actions.

**Non-Goals:**
- Not adding a new charting library — everything uses `@mantine/charts` (`DonutChart`, `BarChart`, `LineChart`), consistent with the rest of the app.
- Not changing `type` or `payment_method` values, storage, or validation — this change is read-only against existing data.
- Not adding month-over-month history beyond 3 months, or configurable date ranges — scope is fixed to what was requested (current + 2 previous months for the trend chart; current real week for the weekly chart).
- Not persisting or caching aggregated chart data — every endpoint computes on read from `transactions`, matching the existing endpoints' approach; dataset size per user does not warrant caching today.

## Decisions

**1. `monthly-comparison` computes the anchor month from the `month` param (default: current month) and always includes exactly that month plus the 2 calendar months before it, oldest first.**
Matches the requested "active month and 2 previous," and keeps it driven by the existing month selector so paging the dashboard also pages this chart, rather than adding a second, independent date control.

**2. `expenses-mtd-comparison` is scoped by the `month` param, not by "today" directly, so it stays consistent with month-selector navigation.**
Cutoff day = `min(today's day-of-month, days in selected month)` when the selected month is the current calendar month; cutoff day = last day of the selected month (i.e., the full month) when the selected month is a past month. The comparison month is the calendar month immediately before the selected month, using the same cutoff day (clamped to that month's length). This means: viewing the current month shows real "month to date" progress; viewing a past (fully elapsed) month shows a full-month-vs-full-previous-month comparison, which is the only sensible reading of "to date" for a month that has already ended.
Alternative considered: always anchor to the server's real "today" regardless of the selected month. Rejected — that would make the card silently ignore the month selector, which is inconsistent with every other chart on the dashboard and confusing when a user is looking at a past month.
`change_percent` is `null` when the previous-period total is `0` (avoids a divide-by-zero / infinite-percent display); the frontend renders a neutral state ("—" or "n/a") in that case.

**3. `weekly-expenses` is scoped to the real current calendar week (Monday–Sunday containing today), independent of the `month` query param.**
"Weekly" is inherently a short, real-time window distinct from the month-scoped charts; tying it to the month selector would make it show a week from a different month than "this week," which doesn't match the requested "weekly expenses" framing. It accepts no `month` param.
Days are returned Monday-first (ISO weekday `1`–`7`) to match common financial-app week conventions and avoid ambiguity with `Carbon`'s default Sunday-first week in some locales.

**4. `by-payment-method` mirrors `byCategory`'s shape (`{ name, value }[]`) but returns the canonical `payment_method` code as `name` (e.g. `"credit_card"`), not a pre-translated label.**
Consistent with how `payment_method` is already handled elsewhere in the API (raw canonical value; translation happens client-side via `transactions.payment.*` i18n keys in `TransactionsPage.tsx`). Keeping translation client-side avoids baking `Accept-Language`/locale handling into the dashboard endpoint, which no other dashboard endpoint does today.
No Top-N/"Other" capping (unlike `byCategory`) — there are only 6 canonical payment methods, so capping is unnecessary.

**5. "Main categories" vertical bar chart reuses the existing `useDashboardByCategory` hook and `GET /dashboard/by-category` response as-is; no new endpoint.**
The data (main-category name + total expense value) is identical to what the existing donut chart already renders — the only difference is the requested chart type. Adding a second endpoint for the same aggregation would duplicate the `byCategory` grouping logic for no reason.

**6. The daily chart change is frontend-only: swap `BarChart` (income + expense series) for `LineChart` (expense series only), still fed by the existing `GET /dashboard/by-day` response.**
`by-day` already returns `{ day, income, expense }` per calendar day; the `income` field is simply not read by the new chart. Changing the endpoint's response shape (e.g., dropping `income`) is unnecessary churn and would be a breaking change to a documented response format for no functional benefit.

## Risks / Trade-offs

- **[Risk]** `expenses-mtd-comparison`'s cutoff-day clamping logic (Decision 2) is the most complex date math added by this change → **Mitigation**: unit-test it directly (Pest) with cases spanning month-length differences (e.g., viewing March 31 compared against Feb's 28/29 days) and the "past month" full-month-comparison branch, independent of any HTTP request.
- **[Risk]** `weekly-expenses` being decoupled from the month selector could look inconsistent to a user who doesn't expect one dashboard card to ignore the month control → **Mitigation**: labeled clearly (e.g. "Weekly Expenses (this week)") in the UI copy so it doesn't read as a bug.
- **[Risk]** Six dashboard endpoints now run independent queries against `transactions` for one HomePage render (up from four) → **Mitigation**: each query is already `user_id`-scoped and date-range-scoped with existing indexes (`payment_method`/`transacted_at` composite index already present per `2026_06_20_000200_add_payment_method_and_indexes_to_transactions_table.php`); no evidence of a performance problem at current data volumes, and this matches the existing pattern of one endpoint per chart rather than a combined "dashboard bundle" endpoint.

## Migration Plan

1. Backend: add the 4 new `DashboardController` actions and routes; add Pest feature tests per new endpoint (ownership scoping, empty-data states, the MTD cutoff-day edge cases).
2. Frontend: add types (`web/src/types/api.ts`), API calls + hooks (`web/src/services/api.ts`, `web/src/hooks/api.ts`), and i18n keys (pt-BR + en) for the 4 new charts.
3. Frontend: wire the 4 new chart sections into `HomePage.tsx`, add the second (bar) view of category data, and swap the daily `BarChart` for a `LineChart`.
4. No rollback complexity: purely additive endpoints plus one frontend chart swap; reverting is a straightforward revert of the same commits if needed.

## Open Questions

None outstanding — chart scope, the payment-method dedup, the weekly-chart window, and the MTD-comparison visualization were all confirmed with the user before writing this design.
