## ADDED Requirements

### Requirement: Frontend TypeScript strict mode
The `web/` project SHALL compile with `strict` mode enabled in both `tsconfig.app.json` and `tsconfig.node.json`.

#### Scenario: Building the frontend project
- **WHEN** `tsc -b` (or `npm run build`) is run against the `web` project
- **THEN** it SHALL complete with zero type errors under `strict` mode

### Requirement: No explicit `any` in frontend code
The frontend ESLint configuration SHALL treat `@typescript-eslint/no-explicit-any` as an error, and `web/src` SHALL contain no `any`-typed values or `as any` casts.

#### Scenario: Introducing a new explicit `any`
- **WHEN** a contributor adds a new `any`-typed value or `as any` cast anywhere in `web/src`
- **THEN** `npm run lint` SHALL fail

#### Scenario: Existing casts removed
- **WHEN** `npm run lint` is run against the current codebase after this change is applied
- **THEN** no `as any` casts SHALL remain in `web/src`

### Requirement: PHP strict types declaration
Every PHP source file under `api/app/` SHALL declare `strict_types=1` as its first statement, and this SHALL be enforced automatically by tooling (Pint's `declare_strict_types` fixer), not left to convention or code review.

#### Scenario: Auditing PHP files for strict types
- **WHEN** every file matching `api/app/**/*.php` is inspected
- **THEN** each file SHALL contain `declare(strict_types=1);` immediately after the opening `<?php` tag

#### Scenario: New file missing the declaration
- **WHEN** `vendor/bin/pint --test` is run against a file under `api/app/` that lacks `declare(strict_types=1);`
- **THEN** it SHALL fail, and plain `vendor/bin/pint` SHALL auto-insert the missing declaration

### Requirement: PHP static analysis gate
The `api/` codebase SHALL be checked by PHPStan (via Larastan), configured with an explicit level and a baseline file for pre-existing findings.

#### Scenario: Running static analysis on the existing codebase
- **WHEN** `composer analyse` is run against the current codebase
- **THEN** it SHALL pass, with any pre-existing findings above the configured level suppressed only via the committed baseline file

#### Scenario: New violation introduced
- **WHEN** a contributor introduces a new type violation that is not covered by the existing baseline
- **THEN** `composer analyse` SHALL fail
