## Context

The app is a Laravel API consumed by a React frontend. Categories belong to users (`user_id`) and are typed as `income` or `expense`. The `categories` table also supports hierarchy (`parent_id`) and icons. New users are created in `AuthController::register()` with no follow-up seeding; existing users who registered before this change have no categories at all.

User locale is stored in the `users` table (`locale` column, values `pt-BR` or `en`), which makes locale-aware seeding straightforward.

## Goals / Non-Goals

**Goals:**
- Every new user gets default expense and income categories immediately after registration.
- Existing users with zero categories receive the same defaults via a backfill command.
- Default categories respect the user's locale (`pt-BR` vs `en`).
- Implementation is simple and contained to the API layer.

**Non-Goals:**
- Providing UI to manage or reset default categories (out of scope for this change).
- Translating categories a user has already created.
- Adding sub-categories / hierarchy to the defaults (flat list only for now).

## Decisions

### 1. Where to seed: `DefaultCategorySeeder` service class

A dedicated `App\Services\DefaultCategorySeeder` class holds the canonical list and a `seedFor(User $user): void` method that inserts the categories only if the user has none (`categories()->doesntExist()`).

**Why a service class over a DB Seeder:**
Laravel's `Database\Seeders` classes are designed for development/testing fixtures and are awkward to call from application code. A plain service class is easier to inject, test, and call from both the registration flow and an Artisan command.

### 2. Hook into registration: call service directly in `AuthController::register()`

After `User::create()`, resolve and call `DefaultCategorySeeder::seedFor($user)` inline.

**Why not a Model Observer or event listener:**
The registration path is a single, explicit endpoint. Observers fire on every `User::create()` (including factories, seeders, admin imports), which introduces hidden coupling. Calling the service directly in the controller keeps the side-effect visible and easy to suppress in tests.

**Why not a queued job:**
Category seeding is fast (< 10 inserts) and the frontend immediately redirects to the dashboard expecting categories to exist. Running it synchronously avoids a race condition.

### 3. Locale mapping

The service maps `pt-BR` → Portuguese names and everything else (including `en`) → English names. The `locale` column on the `User` model already carries this value.

### 4. Backfill via Artisan command: `php artisan categories:seed-defaults`

The command iterates all users whose `categories` count is 0 and calls `DefaultCategorySeeder::seedFor()` for each. Runs once, idempotent (the `doesntExist()` guard inside the service prevents double-seeding).

**Why not a migration:**
Migrations are for schema, not data. A targeted Artisan command is explicit, reviewable, and re-runnable safely.

## Risks / Trade-offs

- **User deletes all categories then re-registers** → Not possible (unique email). If a user deletes all their categories, they will not be re-seeded automatically. Acceptable for now; a future "restore defaults" UI action can call the same service.
- **Locale is null** → Falls back to English names.
- **Factory/test user creation triggers seeding** → Avoided by calling the service explicitly in the controller, not in an observer. Tests that go through the controller will get seeded categories (expected); tests using `User::factory()->create()` will not.

## Migration Plan

1. Deploy the code (service class + controller change + Artisan command).
2. Run `php artisan categories:seed-defaults` once on production to backfill existing users.
3. No schema changes, no rollback needed. If the command is run twice, the `doesntExist()` guard is a no-op.

## Open Questions

- Should the backfill command output progress (e.g., `--verbose`) or just a summary count? (Recommend: summary count, with `--dry-run` option for safety.)
