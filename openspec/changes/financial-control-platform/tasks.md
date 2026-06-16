## 1. Project and Domain Setup

- [x] 1.1 Create backend domain entities and migrations for categories, transactions, budgets, and subscription-ready account fields.
- [x] 1.2 Define API resource shapes and shared validation/error response conventions for finance endpoints.
- [x] 1.3 Create frontend API client modules and base types aligned with backend contracts.

## 2. Authentication and Access Control

- [x] 2.1 Implement authentication endpoints and protected route middleware for all finance APIs.
- [x] 2.2 Enforce user-level ownership policies for transactions, budgets, and summary resources.
- [x] 2.3 Add tests for unauthenticated access denial and cross-user access denial.

## 3. Financial Ledger Capability

- [x] 3.1 Implement transaction CRUD endpoints with validation for amount, date, category, and notes.
- [x] 3.2 Implement transaction listing with default date-desc ordering and user scoping.
- [x] 3.3 Build React screens/forms for creating, editing, deleting, and listing transactions.

## 4. Budget and Monthly Summary Capability

- [x] 4.1 Implement monthly budget create/update endpoints per category and month.
- [x] 4.2 Implement monthly summary endpoint returning income, expenses, net balance, and category variance.
- [x] 4.3 Build React budget and monthly summary UI with category-level variance visualization.

## 5. API Stability and Future Mobile Readiness

- [x] 5.1 Add API versioning conventions and ensure all finance routes follow the same response envelope.
- [x] 5.2 Standardize validation, authentication, and authorization error payloads across endpoints.
- [x] 5.3 Document endpoint contracts and example payloads for reuse by future mobile clients.

## 6. Quality and Delivery

- [x] 6.1 Add/extend backend feature tests covering all required scenarios in ledger, budget, summary, and access control specs.
- [x] 6.2 Run backend and frontend lint/build checks and fix any issues introduced by this change.
- [x] 6.3 Perform end-to-end manual validation of core web flows and prepare rollout notes for web-first launch.
