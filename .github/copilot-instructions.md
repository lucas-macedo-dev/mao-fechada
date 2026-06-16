# Copilot instructions for `pao_duro`

## Build, test, and lint commands

Use the Docker-first workflow from the repository root whenever possible.

| Area | Purpose | Command |
|---|---|---|
| Monorepo | Initial setup | `make setup` |
| Monorepo | Start stack | `make up` |
| Monorepo | Stop stack | `make down` |
| API (Laravel) | Install PHP deps | `make install` |
| API (Laravel) | Run migrations | `make migrate` |
| API (Laravel) | Run full tests | `docker compose exec api php artisan test` |
| API (Laravel) | Run one test file | `docker compose exec api php artisan test tests/Feature/ExampleTest.php` |
| API (Laravel) | Run one test by name | `docker compose exec api php artisan test --filter="returns a successful response"` |
| Web (React) | Install deps | `docker compose exec web npm install` |
| Web (React) | Dev server | `docker compose exec web npm run dev -- --host 0.0.0.0` |
| Web (React) | Build | `docker compose exec web npm run build` |
| Web (React) | Lint | `docker compose exec web npm run lint` |

Notes:
- There is currently no frontend test runner script in `web/package.json`.
- API tests run with Pest/Laravel test tooling and use in-memory SQLite in `api/phpunit.xml`.

## High-level architecture

This repository is a monorepo with two apps:

1. `api/`: Laravel 13 backend (PHP-FPM container), plus migrations/tests.
2. `web/`: React 19 + TypeScript + Vite frontend.

Local runtime is orchestrated by `docker-compose.yml`:

1. `web` runs Vite on `:5173`.
2. `nginx` exposes Laravel on `:8000` and mounts `api/`.
3. `api` (PHP-FPM) talks to `db` (MySQL 8) and `redis`.

Request flow in development: browser loads frontend from `http://localhost:5173`, and frontend calls backend using `VITE_API_URL` (default `http://localhost:8000/api` per root `readme.md` / compose env).

Backend routing is configured in `api/bootstrap/app.php` and currently wires only `routes/web.php` and `routes/console.php`; JSON exception rendering is forced for `/api/*` paths.

## Key conventions in this codebase

- Prefer root-level `make` targets for routine environment operations instead of ad hoc docker commands.
- Treat backend and frontend as separate processes/ports in development (`5173` for web, `8000` for API via nginx). Keep integrations through HTTP and `VITE_API_URL`.
- Backend tests are written in Pest style (`it(...)` / `test(...)`) with shared setup in `api/tests/Pest.php`; follow that style for new tests.
- For arbitrary Laravel commands, use `make artisan <args>` from repo root to execute inside the API container.
