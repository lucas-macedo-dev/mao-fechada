## Why

The codebase has no enforced floor for type safety: `web/tsconfig.app.json` never sets `strict` (so `noImplicitAny`/`strictNullChecks` are off), ESLint's `@typescript-eslint/no-explicit-any` only warns (via `tseslint.configs.recommended`), and 3 call sites already bypass typing with `as any`. On the backend, none of the 49 PHP files under `api/app/` declare `strict_types=1`, and there is no static analysis tool (PHPStan/Larastan/Psalm) installed at all. Without a floor, type drift accumulates silently — for example, the `entrada`/`saida` vs `income`/`expense` mismatch found across the API and frontend (addressed separately) went unnoticed for a long time partly because nothing type-checks string literal contracts end-to-end. This change establishes and enforces a "no new `any`/untyped waste" floor, verified against the actual dev environment (not a partial local install) so scope estimates are accurate.

## What Changes

- Enable `"strict": true` in `web/tsconfig.app.json` and `web/tsconfig.node.json`. Verified inside the running `web` Docker container (the authoritative environment — a stale local `node_modules` outside Docker gave misleading results) that this surfaces **zero new compiler errors** today, so it is a safe, low-risk flip rather than a large migration.
- Escalate `@typescript-eslint/no-explicit-any` from its default `warn` to `error` in `web/eslint.config.js`, so `npm run lint` fails on any new `any`.
- Remove the 3 existing `(err as any)?.response?.data` casts in `LoginPage.tsx`, `RegisterPage.tsx`, and `ResetPasswordPage.tsx`, replacing them with the already-existing typed `extractApiError()` helper (`web/src/services/api.ts`) or an equivalently typed Axios error narrowing utility.
- Add `declare(strict_types=1);` as the first statement in every PHP file under `api/app/` (49 files today).
- Add PHPStan + Larastan (`phpstan/phpstan`, `larastan/larastan`) as dev dependencies in `api/composer.json`, with a config (`phpstan.neon`) set at a level the current codebase can pass (or an initial baseline file for pre-existing gaps), plus a `composer analyse` script.
- **BREAKING**: none for runtime behavior or the `/api/v1` contract — this is internal tooling/typing hygiene only.

## Capabilities

### New Capabilities
- `type-safety`: codebase-wide requirement that TypeScript and PHP code is strictly typed (no new implicit/explicit `any` or untyped PHP), enforced by compiler/linter/static-analysis configuration rather than convention alone.

### Modified Capabilities
(none — no existing spec currently governs typing standards)

## Impact

- `web/tsconfig.app.json`, `web/tsconfig.node.json`: add `strict: true`.
- `web/eslint.config.js`: escalate `no-explicit-any` to error.
- `web/src/pages/LoginPage.tsx`, `RegisterPage.tsx`, `ResetPasswordPage.tsx`: replace `as any` casts.
- `api/composer.json`, new `api/phpstan.neon` (+ optional baseline): add static analysis tooling.
- All 49 files under `api/app/`: add `declare(strict_types=1);`.
- No CI workflow currently runs `npm run lint` or a PHP static-analysis step (only `.github/workflows/deploy-*.yml` exist) — enforcement is manual (`make`/`npm`/`composer` commands) until/unless a CI check is added later; this proposal does not add CI, only the tooling and codebase changes needed for one.
