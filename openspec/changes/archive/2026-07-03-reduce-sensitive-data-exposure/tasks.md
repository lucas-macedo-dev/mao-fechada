## 1. Database

- [x] 1.1 Create migration `add_uuid_to_users_table` adding nullable+unique `uuid` string(36) column after `id`
- [x] 1.2 Backfill `uuid` for existing rows in the same migration (PHP-side `Str::uuid()` per row)
- [x] 1.3 Tighten `uuid` to `NOT NULL` via raw `DB::statement` ALTER (no doctrine/dbal dependency)
- [x] 1.4 Add symmetric `dropColumn('uuid')` in `down()`

## 2. Model

- [x] 2.1 Add `'id'` to `User`'s `#[Hidden]` attribute list
- [x] 2.2 Add `booted()` with a `static::creating` hook to auto-generate `uuid` via `Str::uuid()`
- [x] 2.3 Confirm `uuid` is not in `#[Fillable]` (system-generated only)

## 3. API response shape

- [x] 3.1 Create `App\Http\Resources\Api\V1\UserResource` returning the essential-fields allowlist (`id`=uuid, `name`, `email`, `email_verified` boolean, `locale`, `profile_photo_url`, `tutorial_progress`)
- [x] 3.2 Update `AuthController::register` to wrap the user in `UserResource`
- [x] 3.3 Update `AuthController::login` to wrap the user in `UserResource`
- [x] 3.4 Update `AuthController::me` to wrap the user in `UserResource`
- [x] 3.5 Update `AuthController::updateAuthenticatedUser` (used by `updateProfile`/`updatePreferences`) to wrap the user in `UserResource`
- [x] 3.6 Update `TutorialController::update` (both reset and normal-update return paths) to wrap the user in `UserResource`

## 4. Frontend

- [x] 4.1 Update `web/src/types/api.ts` `User` type: `id` to `string`, replace `email_verified_at` with `email_verified: boolean`, remove unused `plan`/`subscription_status` fields
- [x] 4.2 Update `RegisterPage.tsx`, `LoginPage.tsx`, `PrivateRoute.tsx` to use `email_verified` instead of `email_verified_at`

## 5. Tests

- [x] 5.1 Update `FinanceApiTest.php` assertion on `data.id` to compare against `$user->uuid`
- [x] 5.2 Add feature tests asserting `uuid` is generated, unique, and valid-format on user creation
- [x] 5.3 Add feature tests asserting `register`/`login`/`me`/`updateProfile`/`updatePreferences`/tutorial `update` responses expose the uuid as `id` and contain exactly the essential-fields allowlist (no password, remember_token, plan, subscription_status, raw email_verified_at, or numeric id)

## 6. Verification

- [x] 6.1 Run `php artisan migrate` and spot-check a created user has a `uuid`
- [x] 6.2 Run the backend test suite (Pest) — all tests green
- [x] 6.3 Run `tsc -b`/`npm run build` in `web/` — type checks pass
- [x] 6.4 Manually verify `/api/v1/auth/me` response contains only the allowlisted fields with `id` as a UUID string
