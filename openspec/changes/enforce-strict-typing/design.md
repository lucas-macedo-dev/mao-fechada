## Context

Verified current state (2026-07-29):
- `web/tsconfig.app.json` / `tsconfig.node.json` have no `strict` and no individual strict flags (`noImplicitAny`, `strictNullChecks`, etc.) set.
- `web/eslint.config.js` only extends `tseslint.configs.recommended`, under which `@typescript-eslint/no-explicit-any` is `warn`, not `error`.
- Exactly 3 explicit `any` casts exist in `web/src`, all the same pattern: `(err as any)?.response?.data` in `LoginPage.tsx`, `RegisterPage.tsx`, `ResetPasswordPage.tsx`.
- Running `tsc --noEmit --strict` **inside the real `web` Docker container** (where `node_modules` is complete) produces **zero errors**, identical to the non-strict run. A local, partially-installed `web/node_modules` outside Docker gave misleading `TS7006`/`TS2307` noise from missing `@mantine/*` type declarations — that was an artifact of a stale local install, not a real strict-mode gap, and is not indicative of the Docker dev environment.
- 0 of the 49 PHP files under `api/app/` declare `strict_types=1`.
- No PHPStan/Larastan/Psalm is installed; `api/composer.json` only has Pint (formatting) and Pest (testing) as dev tooling.
- No CI workflow runs tests, lint, or static analysis today (`.github/workflows/` only has `deploy-api.yml` / `deploy-web.yml`).

## Goals / Non-Goals

**Goals:**
- Close the gap between "TypeScript project" and "actually strict TypeScript": turn on `strict` mode now that it's confirmed to be free (zero fallout), and make new `any` a lint error instead of a warning.
- Remove the 3 known `any` casts using the codebase's existing typed error-handling helper rather than inventing a new pattern.
- Give the PHP side an equivalent floor: `strict_types=1` everywhere, plus static analysis (Larastan) so type mistakes are caught before runtime.
- Make the outcome durable — the point isn't a one-time cleanup, it's a floor that stops regressing.

**Non-Goals:**
- Not adding a CI workflow to run lint/`analyse`/tests on every push — no such workflow exists for anything today (only deploy workflows), so adding one is a separate, larger decision left for later. This change only produces the tooling/config a future CI step would call.
- Not touching the `entrada`/`saida` vs `income`/`expense` value inconsistency — that's a data-contract concern handled by the separate `standardize-i18n-canonical-values` change.
- Not chasing the highest possible Larastan level or 100% type-coverage in one pass — see the baseline decision below.
- Not adding runtime validation libraries (e.g. zod) on the frontend; Form Requests already own validation per `CLAUDE.md`, and that boundary isn't changing here.

## Decisions

**1. Enable full `strict: true` in one step, not flag-by-flag.**
Alternative considered: turn on `noImplicitAny`, `strictNullChecks`, etc. incrementally to limit blast radius. Rejected — since the verified blast radius in the real environment is already zero, incremental rollout only adds process overhead for no benefit. Turn it on fully now, before any drift makes it non-zero.

**2. Escalate `@typescript-eslint/no-explicit-any` to `"error"`; leave other recommended rules untouched.**
Keeps the change focused on the stated premise (stop new `any`) without unrelated lint-rule churn.

**3. Replace the 3 `as any` casts by reusing `extractApiError()` (`web/src/services/api.ts`), not a new helper.**
That function already exists and is used elsewhere in the codebase for the same "pull a message out of an Axios error" job. Introducing a second, parallel helper would be exactly the kind of premature abstraction to avoid.

**4. `declare(strict_types=1);` added to all 49 existing `api/app/` files as a single mechanical change; enforced automatically going forward via Pint's built-in `declare_strict_types` fixer, not just convention.**
This is a non-behavior-changing declaration for files with no scalar-typed parameters, and a real (desirable) tightening for files that do — PHP will stop silently coercing e.g. `"5"` to `5` at typed function boundaries. Pest suite run afterward is the safety net. Enforcement doesn't need a new tool: Laravel Pint (already a dev dependency, built on PHP-CS-Fixer) ships a `declare_strict_types` fixer. Adding `api/pint.json` with that rule enabled means `vendor/bin/pint --test` fails on any file missing the declaration, and plain `vendor/bin/pint` auto-inserts it — so this is tool-enforced immediately, without waiting on a CI workflow.

**5. Add Larastan with a baseline file, targeting a level the codebase can reasonably reach — not the lowest level, not the highest.**
Alternative considered: pick the lowest passing level to minimize noise. Rejected — that would make the tool nearly meaningless for new code. Alternative considered: max level immediately. Rejected — with zero prior static analysis, this would likely surface a large, unscoped backlog unrelated to this change's intent. Instead: install Larastan, run it, choose the highest level that doesn't require large-scale refactors to reach, and use `phpstan-baseline.neon` to grandfather the remainder so pre-existing gaps stay visible (not silenced project-wide) while new code is held to the bar immediately. The exact level is an implementation-time decision (see tasks.md), not guessed here.

## Risks / Trade-offs

- **[Risk]** Larastan may surface a nontrivial number of latent PHP typing issues, since no static analysis has ever run against this code → **Mitigation**: baseline file grandfathers pre-existing findings; this change ships without requiring a big-bang bugfix pass, while still holding new code to the configured level.
- **[Risk]** `declare(strict_types=1)` can change behavior where code relied on PHP's loose type coercion at a typed function boundary (e.g. a numeric string silently becoming an int) → **Mitigation**: run the full Pest suite after adding the declaration to every file; treat any resulting failure as a real bug to fix, not a reason to revert the declaration.
- **[Risk]** Without a CI gate, the new `error`-level lint rule and `composer analyse` can still be skipped locally and never actually block anything → **Mitigation**: explicitly called out as a Non-Goal here rather than assumed solved; a follow-up change can wire these into CI once one exists for anything else.

## Migration Plan

1. Set `"strict": true` in `web/tsconfig.app.json` and `web/tsconfig.node.json`; run `tsc -b` inside the `web` container to reconfirm zero errors.
2. Escalate the ESLint rule; run `npm run lint`; fix the 3 `any` casts via `extractApiError()`.
3. Add `api/pint.json` enabling the `declare_strict_types` fixer, run `vendor/bin/pint` to auto-insert `declare(strict_types=1);` across all 49 files under `api/app/`, then run `php artisan test` (full Pest suite) inside the `api` container; fix any real behavior change surfaced.
4. Add `phpstan/phpstan` + `larastan/larastan` to `api/composer.json` dev deps, add `phpstan.neon`, run it, generate `phpstan-baseline.neon` for anything above the chosen level, add a `composer analyse` script.
5. No rollback complexity: every change here is additive/tightening. If a future contributor's branch breaks under strict mode, the expectation is to fix forward, not to loosen the flags back.

## Open Questions

None outstanding. Both prior open questions are resolved (confirmed): `strict_types=1` is enforced automatically via Pint's `declare_strict_types` fixer (Decision 4), not left to convention; and CI wiring for any of this tooling explicitly waits for a dedicated CI-setup change (per Non-Goals).
