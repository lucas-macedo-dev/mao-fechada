## 1. Frontend: enable strict TypeScript

- [ ] 1.1 Add `"strict": true` to `web/tsconfig.app.json`
- [ ] 1.2 Add `"strict": true` to `web/tsconfig.node.json`
- [ ] 1.3 Run `tsc -b` (or `npm run build`) inside the `web` container to confirm zero new errors

## 2. Frontend: enforce no explicit `any`

- [ ] 2.1 In `web/eslint.config.js`, set `@typescript-eslint/no-explicit-any` to `"error"`
- [ ] 2.2 Replace `(err as any)?.response?.data` in `web/src/pages/LoginPage.tsx` with `extractApiError(err)`
- [ ] 2.3 Replace `(err as any)?.response?.data` in `web/src/pages/RegisterPage.tsx` with `extractApiError(err)`
- [ ] 2.4 Replace `(err as any)?.response?.data` in `web/src/pages/ResetPasswordPage.tsx` with `extractApiError(err)`
- [ ] 2.5 Run `npm run lint` inside the `web` container and confirm zero errors/warnings for `no-explicit-any`

## 3. Backend: enforce `strict_types` via Pint

- [ ] 3.1 Create `api/pint.json` enabling the `declare_strict_types` fixer (extend the existing `laravel` preset)
- [ ] 3.2 Run `vendor/bin/pint` inside the `api` container to auto-insert `declare(strict_types=1);` across all files under `api/app/`
- [ ] 3.3 Run `vendor/bin/pint --test` to confirm the ruleset now passes clean
- [ ] 3.4 Run the full Pest suite (`php artisan test`) inside the `api` container; investigate and fix any behavior change surfaced by strict type coercion (not revert the declaration)

## 4. Backend: add PHPStan/Larastan static analysis

- [ ] 4.1 Add `phpstan/phpstan` and `larastan/larastan` to `api/composer.json` `require-dev`
- [ ] 4.2 Create `api/phpstan.neon` targeting the highest level the codebase can reach without large-scale refactors
- [ ] 4.3 Add a `composer analyse` script (`vendor/bin/phpstan analyse`) to `api/composer.json`
- [ ] 4.4 Run `composer analyse`; for findings that require refactors out of scope for this change, generate `api/phpstan-baseline.neon` to grandfather them
- [ ] 4.5 Confirm `composer analyse` passes cleanly with the baseline in place

## 5. Verify

- [ ] 5.1 Re-run `npm run lint` and `tsc -b` in `web`, and `vendor/bin/pint --test`, `composer analyse`, `php artisan test` in `api` — all SHALL pass
- [ ] 5.2 Confirm no `entrada`/`saida` overlap was introduced or touched (that work is out of scope here, tracked by `standardize-i18n-canonical-values`)
