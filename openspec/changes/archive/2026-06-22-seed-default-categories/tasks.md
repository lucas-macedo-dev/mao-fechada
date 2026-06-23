## 1. DefaultCategorySeeder Service

- [x] 1.1 Create `app/Services/DefaultCategorySeeder.php` with a static `categories(string $locale): array` method returning the locale-aware list of `[name, type]` tuples
- [x] 1.2 Implement `seedFor(User $user): void` in the service — guard with `$user->categories()->doesntExist()`, then bulk-insert the default categories using `$user->categories()->createMany(...)`

## 2. Hook Registration

- [x] 2.1 In `AuthController::register()`, after `User::create()`, instantiate (or resolve via `app()`) `DefaultCategorySeeder` and call `seedFor($user)`

## 3. Artisan Backfill Command

- [x] 3.1 Generate the command: `php artisan make:command SeedDefaultCategories` → class `App\Console\Commands\SeedDefaultCategories`, signature `categories:seed-defaults`
- [x] 3.2 Implement the command: chunk through all users with `whereDoesntHave('categories')`, call `DefaultCategorySeeder::seedFor()` for each, then output `"Seeded {n} users. Skipped {m} users with existing categories."`
- [x] 3.3 Add a `--dry-run` option that reports how many users would be seeded without inserting any rows

## 4. Tests

- [x] 4.1 Unit test `DefaultCategorySeeder`: assert correct category names and types for `pt-BR` and `en` locales
- [x] 4.2 Unit test idempotency: calling `seedFor()` twice does not create duplicate categories
- [x] 4.3 Feature test registration endpoint: assert that after `POST /api/v1/auth/register` the new user has 8 categories (5 expense + 3 income)
- [x] 4.4 Feature test `categories:seed-defaults` command: seed two users with no categories and one with existing categories, run the command, assert only the two empty users received defaults

## 5. Backfill Production

- [x] 5.1 After deploying, run `php artisan categories:seed-defaults` on production to seed existing users with no categories
