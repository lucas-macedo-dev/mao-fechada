---
name: test-db-config-cache-gotcha
description: Running Feature tests wiped the real MySQL DB because cached config bypassed phpunit's sqlite setting
metadata:
  type: project
---

In `api/`, `php artisan test` Feature tests use `RefreshDatabase` (runs `migrate:fresh`). On 2026-06-21 this dropped all rows in the real MySQL `mao_fechada` DB twice.

**Root cause:** `bootstrap/cache/config.php` was cached with `database.default = mysql`. When config is cached, Laravel ignores `env()` AND the `<env>` values in `phpunit.xml` (which set `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:`). So tests ran against MySQL instead of in-memory SQLite. `force="true"` in phpunit.xml does NOT help while config is cached — cached config bypasses env entirely.

**Fix:** `docker-compose exec api php artisan config:clear`. After clearing, the test env correctly resolves to sqlite `:memory:` (verified via a no-RefreshDatabase diagnostic test reading `config('database.default')`).

**How to apply:** Never run `php artisan config:cache` in the dev/Docker environment. If tests ever start hitting MySQL again, first check `bootstrap/cache/config.php` exists and run `config:clear`. To diagnose safely WITHOUT wiping data, run a test that `uses(Tests\TestCase::class)` WITHOUT `RefreshDatabase` and dumps `config('database.default')`. A durable safeguard (rejected so far) would be overriding `createApplication()` in `tests/TestCase.php` to force sqlite before RefreshDatabase runs.
