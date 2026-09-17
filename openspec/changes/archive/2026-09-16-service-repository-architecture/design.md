## Context

See proposal.md - Why. Current state: `api/app/Http/Controllers/Api/V1/*` controllers call Eloquent directly (`Model::query()...`, `$request->user()->relation()...`), inline business rules (ownership checks, type-matching validation, cascade updates for installment/recurring groups), wrap multi-step writes in `DB::transaction()` closures inside the controller method itself, and pass Eloquent models straight into `ApiResponse::data()` for serialization. `app/Services` already exists and holds non-domain services (`ActivityLogger`, `MercadoPagoService`, `FallbackCategoryResolver`, `DefaultCategorySeeder`); there is no `app/Repositories` or `app/DataTransferObjects` yet. Laravel's container auto-resolves concrete class constructor dependencies without explicit bindings, so no repository interfaces are required for a single-implementation (Eloquent) data layer, per user decision.

## Goals / Non-Goals

**Goals:**
- Establish `app/Repositories`, `app/Services`, and `app/DataTransferObjects` with a clear, repeatable convention: Controller → (input DTO) → Service → Repository → Model, and back: Model → Repository → Service → (output DTO) → Controller → JSON.
- Fully migrate `TransactionController`, `BudgetController`, `RecurringTransactionController`, `CategoryController`, `DashboardController` onto this pattern, including DTOs at both boundaries.
- Eliminate duplicated `ensureOwnership` logic by centralizing it once and reusing it from every migrated service.
- Preserve all existing HTTP behavior exactly (status codes, JSON response field names/shapes, validation errors, caching behavior in Dashboard) — DTOs must serialize to the same JSON shape the Eloquent models produced.
- Add Unit test coverage for services, repositories, and DTOs so business rules and mapping logic can be tested without booting HTTP/Feature tests.

**Non-Goals:**
- Not migrating `AuthController`, `BillingController`, `TutorialController`, `PasswordResetController`, `EmailVerificationController`, `MercadoPagoWebhookController` in this change.
- Not introducing repository interfaces/contracts (concrete classes only, per user decision) — no swap-only-implementation use case exists today.
- Not changing database schema, adding packages, or altering API request/response contracts (DTOs must reproduce identical JSON output, not just "close enough").
- Not changing the caching strategy in `DashboardController::summary` (Redis tags) — it moves into the service layer as-is, just returning a DTO instead of an array/model mix.

## Decisions

**1. Concrete classes, no interfaces, for repositories.**
Repositories and services are registered as plain classes; Laravel resolves them automatically via type-hinting in controller/service constructors. No `ServiceProvider` bindings needed unless a class must be a singleton — follow the existing `ActivityLogger::class` singleton pattern in `AppServiceProvider` only where needed.
*Alternative considered*: interface + binding per repository — rejected as unnecessary indirection when there's one real implementation and no plan to mock at that layer.

**2. One repository per aggregate, one service per aggregate, mirroring controller boundaries.**
- `app/Repositories/TransactionRepository.php`, `BudgetRepository.php`, `RecurringTransactionRepository.php`, `CategoryRepository.php`
- `app/Services/TransactionService.php`, `BudgetService.php`, `RecurringTransactionService.php`, `CategoryService.php`, `DashboardService.php` (Dashboard has no dedicated model, so it composes `TransactionRepository` directly — no `DashboardRepository`).
*Alternative considered*: a single generic `Repository` base class with magic query methods — rejected; each aggregate's queries (filters, cascades, date-range aggregates) differ enough that a thin generic base would be reimplemented per repository anyway. A minimal `BaseRepository` (constructor takes the Eloquent model class, provides `findOrFail`, `create`) cuts boilerplate without hiding aggregate-specific query methods.

**3. Ownership check centralized as `App\Services\Concerns\AuthorizesOwnership` trait.**
Used by every migrated service instead of each controller re-declaring `ensureOwnership`. Kept as a trait (not a base `Service` class) since services don't share other state, and a trait composes cleanly if a future service needs multiple concerns.
*Alternative considered*: abstract `BaseService` class — rejected since some services may need to extend nothing else; a trait keeps composition flexible.

**4. DTOs at both service boundaries — input and output.**
- **Input DTOs** (`app/DataTransferObjects/Input/...`, e.g. `CreateTransactionData`, `UpdateTransactionData`, `CreateBudgetData`): built by the controller from `$request->validated()` (readonly, typed constructor properties — plain PHP readonly classes, no external package). The controller's job becomes: validate → map validated array to DTO → call service. Services accept only DTOs, never raw arrays or `Request` objects, so business logic never touches HTTP concerns.
- **Output DTOs** (`app/DataTransferObjects/Output/...`, e.g. `TransactionData`, `BudgetData`, `DashboardSummaryData`): built by the service (or repository, for simple 1:1 model mappings) from Eloquent models before returning to the controller. Each output DTO implements a `toArray(): array` method that reproduces the exact JSON shape the Eloquent model + its loaded relations previously produced (same keys, same nesting for `category`, `parent`, etc.), so `ApiResponse::data($dto)` — or `ApiResponse::data($dto->toArray())` if `ApiResponse` requires arrays — yields byte-for-byte identical responses.
- Collections map to `array` of output DTOs (e.g. `TransactionData::collection($transactions)` static helper) rather than introducing a paginator-wrapping DTO — pagination metadata stays assembled in the controller as today, since it's HTTP/response concern, not domain data.
*Alternative considered*: use Eloquent API Resources (`JsonResource`) instead of hand-rolled DTOs — rejected because the goal here is explicitly to learn/practice the DTO pattern, and DTOs (unlike Resources) are also usable as plain input carriers into services, which Resources aren't designed for.
*Alternative considered*: a single shared DTO base class with generic `fromModel()`/`toArray()` — rejected; each output DTO's fields and nested relations differ enough (e.g. `TransactionData` includes `category`, `DashboardSummaryData` is a hand-assembled aggregate with no backing model) that a generic base would add indirection without saving real duplication. A tiny marker interface (`Arrayable`-compatible `toArray(): array`) is enough consistency.

**5. Repositories return Eloquent models/collections (not DTOs); services convert to DTOs at the exit boundary.**
Repositories stay focused on persistence/queries. The service layer owns the model→DTO mapping since it already owns business rules and is the natural place to decide what shape leaves the domain layer.

**6. `DB::transaction()` orchestration moves into the Service layer, not the Repository layer.**
A service method spans multiple repository calls (e.g., `TransactionService::createRecurring()` creates a `RecurringTransaction` then a `Transaction`), so the transaction boundary belongs where the multi-step orchestration happens. Repositories stay single-responsibility (one model's persistence).

**7. Controllers keep: route-level validation (Form Requests, `$request->validate()`), mapping validated data to an input DTO, calling one service method, and passing the returned output DTO to `ApiResponse`.**
Controllers do not call repositories directly, do not construct Eloquent models, and do not contain `DB::transaction()`, `Carbon::` date math for business rules, or `Cache::` calls after this change — those move to services.

## Risks / Trade-offs

- [Risk: behavior drift during refactor, e.g. subtly changing an ownership check, query filter, or JSON field the DTO forgets to include] → Mitigation: migrate one controller at a time, run the full Feature test suite (`api/tests/Feature`) after each controller migration before moving to the next; assert exact response JSON shape stays identical (Feature tests already do this via existing assertions) — a missed DTO field will surface as a Feature test failure.
- [Risk: hand-rolled DTOs reproducing Eloquent's automatic casts/hidden-field behavior incorrectly, e.g. decimal casting on `amount`, or accidentally exposing a field the model hid] → Mitigation: build each output DTO directly from the model's already-cast attributes (`$model->amount`, not raw DB values), and diff the DTO's `toArray()` output against the previous raw-model JSON response in the relevant Feature test before considering that controller done.
- [Risk: `DashboardController`'s Redis cache-tag logic is easy to lose in translation, and caching a DTO vs. an array changes serialization inside the cache store] → Mitigation: cache the DTO's `toArray()` result (plain array), not the DTO object itself, keeping the same cache key format so existing cached entries and the `Transaction::booted()` tag-flush hook keep working unchanged.
- [Risk: over-abstracting with a `BaseRepository` or a shared DTO base could hide aggregate-specific behavior] → Mitigation: keep both minimal (a few shared methods/one interface method), push anything shape-specific into the concrete class.
- [Risk: partial migration (5 of 13 controllers) leaves an inconsistent codebase in the interim] → Mitigation: explicitly documented as accepted scope in proposal.md; unmigrated controllers are functionally untouched, so there's no correctness risk, only a stylistic inconsistency until a follow-up change extends the pattern.

## Migration Plan

1. Scaffold `app/Repositories/BaseRepository.php`, the `AuthorizesOwnership` trait, and the `app/DataTransferObjects/{Input,Output}` directories first — no behavior change, additive only.
2. Migrate one controller/domain at a time, in this order: Category → Budget → RecurringTransaction → Transaction → Dashboard (increasing complexity; Category and Budget are simplest, Dashboard is most complex and benefits from patterns proven on the earlier ones).
3. Each domain's migration includes: repository, service, input DTO(s), output DTO(s), and the controller rewired to use them.
4. After each domain's migration: run `api/vendor/bin/pest` (or `php artisan test`) filtered to that domain's Feature tests, then the full suite, before moving to the next domain.
5. Add Unit tests for each new Service, Repository, and DTO as part of that domain's migration step, not as a separate final pass.
6. Rollback strategy: each domain migration is an independently revertible commit (controller + its service/repository/DTOs + its tests); since no schema or contract changes, reverting a single commit fully restores prior behavior with no data implications.

## Open Questions

(none — scope, style, and boundaries are settled per user decisions in this proposal)
