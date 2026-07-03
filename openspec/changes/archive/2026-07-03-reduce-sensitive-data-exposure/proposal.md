## Why

The API currently identifies users by their sequential, auto-increment primary key and exposes that integer directly in every authenticated response (`/auth/me`, `/auth/login`, `/auth/register`, profile/preferences/tutorial updates). A raw sequential ID lets an attacker estimate total user count and enumerate/guess other users' IDs. On top of that, these endpoints serialize the entire `User` model — including internal fields like timestamps and subscription bookkeeping that the client never uses — which is unnecessary over-exposure of account data. Neither issue is exploitable today for cross-user data access (that is already enforced), but both increase the blast radius of any future bug and leak information that has no reason to leave the server.

## What Changes

- Add a `uuid` column to the `users` table (unique, indexed, generated on creation) to serve as the externally-facing user identifier.
- Hide the internal auto-increment `id` from all API JSON responses; the `id` field returned to clients becomes the UUID string instead. **BREAKING**: the `id` field in `User`-shaped API responses changes type from number to string (UUID). Internal foreign keys and DB relations keep using the integer primary key unchanged.
- Introduce a single `UserResource` (Laravel API Resource) that defines the essential, minimal set of user fields returned to clients, and use it consistently in every endpoint that currently returns a raw `User` model: `AuthController::register/login/me/updateProfile/updatePreferences` and `TutorialController::update`.
- Drop non-essential fields from these responses (e.g. raw `created_at`/`updated_at`, and any other internal bookkeeping fields not needed by the frontend).

## Capabilities

### New Capabilities
(none — this extends existing authentication/account behavior)

### Modified Capabilities
- `account-and-access`: adds requirements that (1) users are identified externally by an opaque UUID rather than the internal primary key, and (2) authenticated user endpoints return only the minimal essential fields instead of the full user record.

## Impact

- **Database**: new migration adding `uuid` column + unique index to `users`; backfill for existing rows.
- **Backend**: `App\Models\User` (hide `id`, add `uuid` handling), new `App\Http\Resources\Api\V1\UserResource`, updates to `AuthController` (register, login, me, updateProfile, updatePreferences) and `TutorialController::update` to return the resource instead of the raw model.
- **Frontend**: `web/src/api/types.ts` `User.id` type changes from `number` to `string`. Confirmed zero logic depends on the numeric value (not used as a storage key, URL param, or comparison anywhere in `web/src`), so this is a low-risk type-only update.
- **No route changes**: no existing routes use the user ID as a URL parameter, so no route-model-binding changes are needed.
