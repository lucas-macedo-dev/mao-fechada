# Financial Control

## Objective
- Provide a simple web-first personal finance flow for authenticated users.
- Reuse the same `/api/v1` contracts for future mobile clients.
- Deliver a mobile-first web layout with touch-friendly forms and readable finance cards.

## Entities
- **User**: name, email, password, plan, subscription_status.
- **Category**: user_id, name, type (`income|expense`).
- **Transaction**: user_id, category_id, type, amount, transacted_at, notes.
- **Budget**: user_id, category_id, year, month, amount.

## Business Rules
- BR-01: Finance endpoints require authenticated users via token auth.
- BR-02: Users can only read/update/delete their own categories, transactions, and budgets.
- BR-03: Transaction amount must be positive; date and category are required.
- BR-04: Transaction lists are user-scoped and ordered by transaction date descending by default.
- BR-05: Monthly summary returns income_total, expense_total, net_balance, and category variance.
- BR-06: Budget upsert is unique per user/category/year/month.
- ⚠️ INFERRED: BR-07: Free/default plan has full core finance access until paid mobile gating is introduced.
- ⚠️ GAP: BR-08: Exact premium feature gates and transition criteria for paid mobile are not yet defined.
- BR-09: Web UI must keep all finance actions usable on small screens without changing API behavior.

## Dependencies
- → `api-v1-contract`: Stable JSON envelopes for auth, validation, authorization, and domain responses.
- → `web-spa`: React app consumes auth, categories, transactions, budgets, and summaries endpoints with mobile-first responsive layout.
- ← `future-mobile-client`: Mobile app will consume the same versioned API contracts.

## Constraints and Exceptions
- Laravel backend must expose API routes under `/api/v1` with consistent `{ data }` success envelope.
- Error responses must use `{ error: { type, message, details? } }`.
- Monthly summary for empty months must return zero totals and valid structure.
- Web screens must prioritize single-column/touch interaction on mobile and progressively enhance for larger breakpoints.
- ⚠️ GAP: Final performance constraints/caching thresholds for large transaction volumes are not documented.
