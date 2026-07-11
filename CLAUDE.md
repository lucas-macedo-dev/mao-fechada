# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

"Mão Fechada" is a personal finance web app: users track categorized income/expense transactions, set monthly budgets, view dashboard summaries, generate reports (CSV/PDF), and manage a paid subscription via MercadoPago. It's a monorepo with a Laravel API and a React SPA, designed so the same backend can later serve a mobile client.

- `api/`: Laravel 13 backend (PHP 8.2, PHP-FPM).
- `web/`: React 19 + TypeScript + Vite frontend.
- `docker-compose.yml` + `Makefile`: local dev orchestration (Docker-first — no need to install PHP/Node/MySQL locally).
- `openspec/`: spec-driven change workflow (see "OpenSpec workflow" below) — has `specs/` (current truth per capability) and `changes/archive/` (history of past proposals). Use the `opsx:*` / `openspec-*` skills when asked to propose or implement a change this way.
- `docs/arquitetura-projeto.md` and `docs/guia-implementacao.md` (Portuguese): deeper architecture and "how to add a feature" walkthroughs than this file covers.

## Commands

Docker-first workflow from the repo root — prefer `make` targets over ad hoc docker commands.

```bash
make setup            # first-time setup: .env, build, composer install, key:generate, migrate
make up                # start all containers
make down              # stop all containers
make logs               # tail all logs
make logs-api           # tail php/nginx logs only
make migrate             # run migrations
make fresh                # drop + recreate DB with seed data (destroys data)
make shell-api          # shell inside the PHP container
make shell-db          # mysql CLI
make artisan <cmd>     # run any artisan command in the api container, e.g. make artisan migrate:status
```

Backend (Laravel / Pest), run inside the `api` container:

```bash
docker compose exec api php artisan test                                          # full suite
docker compose exec api php artisan test tests/Feature/FinanceApiTest.php         # one file
docker compose exec api php artisan test --filter="returns a successful response"  # by name
```

Tests use in-memory SQLite (`api/phpunit.xml`), not the dev MySQL container. Backend tests are written in Pest style (`it(...)` / `test(...)`) with shared setup (`RefreshDatabase`) in `api/tests/Pest.php` — follow that style for new tests.

Frontend (React), run inside the `web` container or locally in `web/`:

```bash
npm run dev -- --host 0.0.0.0   # vite dev server
npm run build                    # tsc -b && vite build
npm run lint                     # eslint
```

There is currently no frontend test runner configured in `web/package.json`.

## Architecture

### Local topology

```
Browser
  ├── http://localhost:5173  →  web container (Vite dev server, HMR)
  └── http://localhost:8000  →  nginx → PHP-FPM (api container) → MySQL (db) / Redis (redis)
```

Web and API never share a port, mirroring production where they'll be separate subdomains (`app.` / `api.`). The frontend talks to the backend only through `VITE_API_URL` (`web/.env`, default `http://localhost:8000/api`) — never assume a shared origin or cookies-based session.

### Backend (`api/`)

- All API routes live in `api/routes/api.php` under the `/v1` prefix (`Route::prefix('v1')`). `api/bootstrap/app.php` wires routing, middleware, and a global JSON exception renderer for any request matching `api/*` — validation, auth, authorization, not-found, and generic 4xx/5xx errors are all normalized there. 5xx (and forced-403) exceptions are also written to the `ActivityLog` via `App\Services\ActivityLogger`.
- Auth is Laravel Sanctum with Bearer tokens (no session cookies). Public routes: register/login/forgot-password (rate-limited `throttle:5,1`) and the MercadoPago webhook. Everything else requires `auth:sanctum`, and most business routes additionally require the `verified` alias (`App\Http\Middleware\EnsureEmailIsVerified`, mapped in `bootstrap/app.php`).
- **Response contract** (`App\Support\ApiResponse::data()`): success responses are always `{ data: ..., meta?: ... }`; errors are always `{ error: { type, message, details? } }`. Preserve this shape for any new endpoint — the frontend's `extractApiError()` depends on it.
- Every authenticated request runs through `App\Http\Middleware\ResolveApiLocale`, which sets the Laravel locale from the user's saved `locale` profile field (falling back to the `Accept-Language` header, then `pt-BR`). Only `pt-BR` and `en` are supported; translated strings live in `api/lang/{pt-BR,en}/messages.php`.
- Controllers are thin; validation lives in Form Requests (`api/app/Http/Requests/Api/V1/`); shared query/filter logic lives in `app/Support/` (e.g. `TransactionFilterQuery`, `TransactionTypeMapper`).
- Reports (`ReportController` + `GenerateReportJob`) are async: `POST /v1/reports` creates a `Report` row and dispatches a queued job that writes a CSV or PDF (`app/Support/Reports/*Writer.php`) to local storage, then the client polls `GET /v1/reports/{id}` / downloads via `GET /v1/reports/{id}/download`. **The `docker-compose.yml` dev stack has no queue worker service** (`QUEUE_CONNECTION=database`), so report jobs will sit `pending` unless you manually run `php artisan queue:work` (or `queue:listen`) inside the `api` container. Expired reports are purged by the `reports:purge-expired` scheduled command (`api/routes/console.php`), which also needs `schedule:work`/cron running to fire.
- Billing/subscriptions integrate with MercadoPago (`app/Services/MercadoPagoService.php`, `BillingController`, webhook in `api/routes/api.php`); subscription state lives on `UserSubscription`/`BillingEvent`/`PlanLimit` models.

### Frontend (`web/src`)

- Entry point is `main.tsx` → it renders `Router.tsx` (imported there as `App`), wrapped in `MantineProvider`.
- The API layer used by every page: `web/src/services/api.ts` (Axios client + all endpoint calls + `extractApiError`) and `web/src/types/api.ts` (shared response/entity types), consumed through React Query hooks in `web/src/hooks/api.ts`. Pattern for new data: add the call to `services/api.ts` → wrap it in a `useQuery`/`useMutation` hook in `hooks/api.ts` → invalidate related query keys on mutation success (e.g. creating a transaction invalidates `['transactions']` and `['dashboard']`).
- Routing (`Router.tsx`) has public routes (login, register, email verification, password reset) and protected routes wrapped in `PrivateRoute` + `Layout` (sidebar on desktop, bottom bar on mobile). New authenticated pages need both wrappers.
- i18n: `web/src/i18n/config.ts` holds all `pt-BR`/`en` strings centrally (no per-component translation files). Current UI language is persisted in `localStorage` under `app_locale` and set on boot in `Router.tsx`; the backend also stores a `locale` preference per user (see `ResolveApiLocale` above) — keep both in sync when changing language.
- Styling is Mantine (`@mantine/core`, `@mantine/charts`, `@mantine/form`) plus Tailwind v4 (via `@tailwindcss/vite`) and hand-written CSS in `index.css`/`App.css`/`styles/`. Icons are Font Awesome, loaded globally in `main.tsx`; category icon mapping lives in `constants/categoryIcons.ts`.
- Auth token: stored in `localStorage` as `auth_token`, applied to Axios via `setAuthToken()` on boot and after login/logout.

### End-to-end example (create a transaction)

Form in a page (e.g. `TransactionsPage.tsx`) → `useMutation` in `hooks/api.ts` calls `api.createTransaction()` in `services/api.ts` → `POST /v1/transactions` → `StoreTransactionRequest` validates → `TransactionController::store` checks category ownership/type match, applies `TransactionTypeMapper`, persists → `ApiResponse::data()` returns the new record → frontend invalidates `transactions` and `dashboard` query keys → UI updates without a full reload.

## Key conventions

- Don't put business rules in React components — validation/business logic belongs in the backend (Form Requests / controllers / support classes); components should just call hooks and render.
- Don't break the `/api/v1` contract or the `data`/`error` response envelope without a clear reason.
- When backend response shapes change, update `web/src/types/api.ts` alongside it — a resulting TypeScript error is a useful signal that the contract changed, not something to silence with `any`.
- New user-facing text always goes through `i18n/config.ts` for both locales, not hardcoded strings.
- Mutations must invalidate the React Query keys they affect (see the transaction example above).
