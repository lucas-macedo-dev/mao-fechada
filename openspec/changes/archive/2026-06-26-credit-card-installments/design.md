## Context

The app already supports `payment_method = cartao_credito` as an enum value on the `transactions` table (added in migration `2026_06_20_000200`). The `TransactionController` handles single-record creation. The `DashboardController` provides summary and chart data. The frontend (`TransactionsPage.tsx`) renders a form with payment method select but has no installment-specific logic. There is no existing concept of linked/grouped transactions.

## Goals / Non-Goals

**Goals:**
- When a user selects `cartao_credito` + `expense` in the new transaction form, conditionally reveal installment fields (toggle, current installment number, total installments)
- On form submission, auto-generate all installment records in the correct months (past, present, future), linked by a shared `installment_group_id`
- Editing an installment record cascades to all siblings in the group (amount, category, notes, payment method are synced; each record keeps its own `transacted_at` date)
- Deleting an installment record deletes all siblings in the group
- Add an `installment` filter to the transactions list API and page
- Add a dashboard summary card on the home page showing total installment expense for the selected month

**Non-Goals:**
- Editing individual installments independently (all edits are group-wide)
- Mobile app changes (web only)
- Paid vs. pending installment status tracking (deferred)

## Decisions

### Decision 1: Nullable installment columns on `transactions` table (not a separate table)
Adding `installment_group_id` (UUID string), `installment_number` (tinyint), and `installment_total` (tinyint) as nullable columns directly on the `transactions` table keeps queries simple and avoids joins for the common non-installment case. A UUID is used as the group ID to avoid leaking sequential IDs across users.

**Alternative considered**: A separate `installment_groups` table with a FK from `transactions`. Rejected — adds join overhead and a separate model for little benefit, given that installment metadata is just 3 scalar fields.

### Decision 2: Backend generates all installment records in a single request
When the user submits with installment data, `TransactionController@store` detects the installment payload and bulk-inserts all installment records (past + present + future) in a database transaction. The `transacted_at` date for each record is derived by adding/subtracting months from the provided date, preserving the day-of-month.

**Alternative considered**: Frontend generates N requests. Rejected — introduces partial-failure risk and N round trips.

### Decision 3: Edit cascades to the entire installment group
`TransactionController@update` checks if the target transaction has an `installment_group_id`. If yes, it updates **all** transactions in that group with the same field values (amount, category_id, payment_method, notes, type) — excluding `transacted_at` and `installment_number`, which are unique per record.

This means the edit form for an installment transaction behaves identically to a normal transaction form — no extra UI needed. The cascade happens transparently on the backend.

### Decision 4: Delete cascades to the entire installment group
`TransactionController@destroy` checks for `installment_group_id` and deletes all sibling records in the same group within a single database transaction.

### Decision 5: `installment_group_id` index + filter param on the list API
A new `?installment=true` query param on `GET /transactions` filters to records where `installment_group_id IS NOT NULL`. A composite index on `(user_id, installment_group_id)` supports efficient group lookups for cascade operations.

### Decision 6: New `GET /dashboard/installments-total?month=YYYY-MM` endpoint
A dedicated endpoint returns the total expense amount from installment transactions for the given month. This keeps the existing `/dashboard/summary` payload stable and lets the home page fetch the installment card data independently.

**Alternative considered**: Extend `/dashboard/summary` with an `installment_total` field. Rejected — would silently change existing consumers of that endpoint.

### Decision 7: Frontend installment fields are local state in `TransactionsPage`
The installment toggle and number inputs are added as local `useState` in the existing `TransactionsPage` component, following the same pattern used for all other form fields. No new component file is needed for the form inputs.

The home page installment card is a small new component that calls the new `/dashboard/installments-total` endpoint via a new hook.

## Risks / Trade-offs

- **Past installments created retroactively**: Records for past months are created at submission time, not when those months occurred. This is intentional for tracking debt history but may slightly affect past-month summaries. → By design; document in UI.
- **installment_total is validated ≤ 60**: Covers all practical Brazilian credit scenarios (máximo 60× parcelamentos). → Enforced at API request level.
- **Cascade on edit/delete is irreversible**: If a user accidentally edits an installment, all records in the group change. → Show a confirmation dialog on the frontend when editing or deleting a transaction that belongs to an installment group.

## Migration Plan

1. Run new migration: add `installment_group_id` (varchar/uuid, nullable), `installment_number` (tinyint, nullable), `installment_total` (tinyint, nullable) to `transactions` table + composite index on `(user_id, installment_group_id)`
2. Deploy backend (new columns are nullable; no backfill needed; zero downtime)
3. Deploy frontend
4. Existing records are unaffected; installment behavior is opt-in at creation time

## Open Questions

- Should the installment card on the home page show only the **current month's** installment total, or a running total of all future installments? → Assumed current month only (consistent with other dashboard cards). Confirm before implementing.
