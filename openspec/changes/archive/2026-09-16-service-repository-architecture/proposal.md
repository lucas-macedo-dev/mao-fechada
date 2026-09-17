## Why

Controllers in `api/app/Http/Controllers/Api/V1` currently mix HTTP concerns, business rules, and direct Eloquent query building in the same class. `TransactionController` (235 lines) and `DashboardController` (349 lines) each hold multiple responsibilities — validation-adjacent business rules, DB transactions, caching, and raw queries — making them hard to test in isolation and causing logic (like ownership checks and query filters) to be duplicated across controllers. Introducing a Service (business logic) + Repository (data access) layer separates these concerns, makes business rules unit-testable without HTTP, and gives future features a consistent place to live.

## What Changes

- Introduce `app/Repositories` for Eloquent query/persistence logic, one repository per aggregate (Transaction, Budget, RecurringTransaction, Category), as concrete classes bound in the container (no interfaces, since there is a single Eloquent implementation).
- Introduce `app/Services` classes for business orchestration (ownership checks, cross-model rules, DB transactions) for the same domains, alongside the existing `ActivityLogger`, `MercadoPagoService`, etc.
- Refactor `TransactionController`, `BudgetController`, `RecurringTransactionController`, `CategoryController`, and `DashboardController` to delegate to their services; controllers keep only request validation, HTTP response shaping, and calls into services.
- Extract the duplicated `ensureOwnership` check into a shared location (base service or trait) used by all migrated services instead of being copy-pasted per controller.
- Add unit tests for the new services and repositories covering the business rules currently only exercised indirectly through feature tests.
- Other controllers (`AuthController`, `BillingController`, `TutorialController`, `PasswordResetController`, `EmailVerificationController`, `MercadoPagoWebhookController`) are **not** touched in this change — they remain as-is and can be migrated later following this same pattern.

No public API behavior, request/response shapes, or business rules change — this is an internal restructuring. **Non-breaking.**

## Capabilities

### New Capabilities
(none — this is an internal code-organization change; no new user-facing behavior)

### Modified Capabilities
(none — no spec-level requirements change; endpoints, inputs, and outputs are preserved exactly)

## Impact

- **Affected code**: `api/app/Http/Controllers/Api/V1/TransactionController.php`, `BudgetController.php`, `RecurringTransactionController.php`, `CategoryController.php`, `DashboardController.php`; new `api/app/Repositories/*`, new/expanded `api/app/Services/*`.
- **Tests**: existing Feature tests under `api/tests/Feature` must continue passing unchanged (they assert HTTP behavior, which does not change); new Unit tests added under `api/tests/Unit` for services/repositories.
- **No database migrations, no dependency additions, no API contract changes.**
