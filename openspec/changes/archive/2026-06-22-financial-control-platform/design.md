## Context

The product starts as a personal financial control web app using Laravel 13 for backend APIs and React + TypeScript for frontend UX. It must remain simple to build and operate, while preserving a clean path to multi-user usage and a paid mobile app later. The current design needs strong domain boundaries (users, transactions, budgets) and API contracts that can be reused by future mobile clients without rewriting business rules.

## Goals / Non-Goals

**Goals:**
- Deliver core finance workflows quickly: authentication, category management, income/expense logging, monthly summaries, and budget comparison.
- Keep architecture simple: modular monolith backend and single React SPA frontend.
- Define API contracts and data model in a client-agnostic way to support future mobile apps.
- Include subscription-ready account fields and access checks without introducing a full billing provider in v1.

**Non-Goals:**
- Building native mobile apps in this change.
- Implementing advanced accounting features (double-entry bookkeeping, tax engine, reconciliation automation).
- Building complex multi-tenant organization features.
- Integrating payment gateways at this stage.

## Decisions

1. **Use Laravel API + React SPA as a modular monolith**
   - **Why:** Fastest way to ship working value without distributed-system complexity.
   - **Alternative considered:** Split into multiple services now. Rejected as unnecessary overengineering for v1.

2. **Domain model centered on User, Category, Transaction, Budget**
   - **Why:** Covers the essential personal finance use cases with minimal schema complexity.
   - **Alternative considered:** Rich finance model with accounts, ledgers, and transfer entities from day one. Rejected to keep scope practical.

3. **API-first contracts with stable JSON resources**
   - **Why:** Web app uses the same APIs a future mobile app will consume, reducing rework.
   - **Alternative considered:** Web-only server-rendered patterns. Rejected because they increase future mobile migration cost.

4. **Subscription-ready access via plan/status flags**
   - **Why:** Enables future paid app rollout with minimal refactoring.
   - **Alternative considered:** Full billing integration now. Rejected due to unnecessary complexity and external dependency coupling.

5. **Monthly summary computed from persisted transactions, with optional cached aggregates**
   - **Why:** Ensures source-of-truth correctness while allowing performance optimization if needed.
   - **Alternative considered:** Store only denormalized monthly totals. Rejected to avoid data drift and difficult corrections.

## Risks / Trade-offs

- **[Risk] Scope creep into advanced finance/accounting features early** → **Mitigation:** Keep strict v1 boundaries around transactions, categories, budgets, and monthly summaries.
- **[Risk] Future mobile needs may expose weak API design decisions** → **Mitigation:** Standardize resource naming, pagination, and error format from the start.
- **[Risk] Subscription-ready fields may be underused initially** → **Mitigation:** Keep initial model minimal (`plan`, `subscription_status`) and avoid billing workflows until needed.
- **[Risk] Summary queries can become expensive with large datasets** → **Mitigation:** Start with indexed date/user queries and add incremental caching only when performance requires it.
