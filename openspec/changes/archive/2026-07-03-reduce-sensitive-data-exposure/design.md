## Context

The `users` table uses a sequential auto-increment integer as its primary key, and that integer is currently the `id` returned in every authenticated response involving the user (`/auth/register`, `/auth/login`, `/auth/me`, `PATCH /users/me`, `PATCH /users/me/preferences`, `PUT /users/me/tutorial`). These endpoints return the raw `User` Eloquent model, so beyond the ID they also expose internal fields the frontend never consumes (`created_at`/`updated_at`, `plan`, `subscription_status` — confirmed unused by grepping the live frontend). No route uses the ID as a URL parameter, so this is purely a response-body exposure problem, not a route-model-binding one.

## Goals / Non-Goals

**Goals:**
- Give the API an external user identifier (UUID) that is not sequential/guessable, without disturbing the internal integer primary key used by all foreign keys, Sanctum tokens, and relations.
- Return only the essential fields the frontend actually consumes from user-serializing endpoints.
- Keep the change additive at the database level (new column, not a PK swap) to avoid touching every foreign key in the schema.

**Non-Goals:**
- Not replacing the internal auto-increment `id` as the Eloquent primary key or route-model-binding key (no `HasUuids` trait).
- Not changing authentication/token behavior (Sanctum tokens keep referencing the integer PK internally).
- Not touching `web/src/App.tsx` / `web/src/api/*` (confirmed dead scaffold code, unreachable from the running app).

## Decisions

**UUID storage**: plain `string(36)` column named `uuid`, generated via `Str::uuid()` (random UUIDv4), not Laravel's `HasUuids` trait or an ordered/time-based UUID. Rationale: `HasUuids` changes primary-key/route-key semantics, which is more than needed and risks touching FK behavior; a random (not time-ordered) UUID avoids leaking row-creation ordering, which an ordered UUID would partially expose. This also matches an existing idiom already used in this codebase (`Str::uuid()->toString()` for `installment_group_id` in `TransactionController`).

**Backfill strategy**: single migration, nullable+unique column first, PHP-side backfill loop (`Str::uuid()` per row), then tighten to `NOT NULL` via a raw `DB::statement` ALTER (no `doctrine/dbal` dependency is installed, and the driver is MySQL, so `Blueprint::change()` is unavailable). This mirrors the existing precedent of combining schema change + data fix in one migration (`2026_06_25_000001_verify_existing_users.php`).

**Hiding the internal ID**: add `'id'` to the model's `#[Hidden]` attribute (defense-in-depth), and additionally control the exposed shape explicitly through a new `UserResource` (first Resource class in this codebase, under `App\Http\Resources\Api\V1`). The Resource is the primary mechanism; hiding `id` on the model prevents any raw-model serialization (present or future) from accidentally leaking it.

**Essential-fields allowlist**: `id` (= uuid), `name`, `email`, `email_verified` (boolean), `locale`, `profile_photo_url`, `tutorial_progress`. Determined by grepping every real (non-dead-code) usage of the `User` object in `web/src`. `email_verified_at` becomes a boolean (`email_verified`) since the exact verification timestamp isn't consumed anywhere, only its presence/absence (3 call sites do a truthy check). `plan`/`subscription_status` are dropped entirely — confirmed unused; `SubscriptionPage.tsx` sources that data from the separate `/subscription` endpoint instead.

**Single Resource, reused everywhere**: rather than each controller building its own array, one `UserResource` is used in `AuthController::register/login/me/updateAuthenticatedUser` and `TutorialController::update`, so the essential-fields contract has one source of truth.

## Risks / Trade-offs

- [Breaking change] `id` in user API responses changes type from number to string → Mitigation: confirmed zero frontend logic depends on the numeric value (not used as a storage key, URL param, or comparison); only a type-level update plus 3 mechanical `email_verified_at`→`email_verified` renames are needed.
- [Migration on large tables] Row-by-row PHP backfill could be slow on a very large `users` table → Mitigation: this is a low-user-count application at this stage; if it ever becomes a concern, the same migration can be adapted to chunk in batches.
- [Missed serialization site] A future controller could return a raw `User` model again, bypassing `UserResource` → Mitigation: `#[Hidden(['id', ...])]` on the model is a defense-in-depth backstop so even a raw-model leak wouldn't expose the internal ID.

## Migration Plan

1. Add and run the `uuid` migration (additive, backfills existing rows, safe to run without downtime).
2. Ship model + `UserResource` + controller changes together (single deploy) since they're interdependent.
3. Ship the frontend type/field changes in the same release to avoid a window where the frontend expects fields the API no longer sends.
4. Rollback: the migration's `down()` drops the `uuid` column; reverting the app code to the previous commit restores raw-model responses. No data loss risk since the internal `id` is untouched.

## Open Questions

None outstanding — field allowlist and `email_verified` boolean conversion were confirmed with the user during planning.
