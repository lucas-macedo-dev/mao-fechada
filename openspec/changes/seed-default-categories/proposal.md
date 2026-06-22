## Why

New users currently start with an empty category list, creating friction before they can log any transaction. Providing a sensible set of default categories lets users start tracking expenses and income immediately, and existing users who never created categories should receive the same benefit retroactively.

## What Changes

- A `DefaultCategorySeeder` (or equivalent service) is introduced that defines a canonical list of default expense and income categories.
- User registration triggers automatic seeding of these defaults for every new account.
- A one-time migration command / seeder run applies the defaults to all existing users who have zero categories.
- Default categories are user-owned (scoped to `user_id`), so users can edit or delete them freely afterward.

**Default Expense categories** (`type = expense`):
- Veículo / Vehicle
- Habitação / Housing
- Alimentação / Food
- Compras / Shopping
- Lazer / Leisure

**Default Income categories** (`type = income`):
- Salário / Salary
- Investimentos / Investments
- Ticket Alimentação / Food Vouchers

## Capabilities

### New Capabilities
- `default-category-seeding`: Logic to seed a predefined set of expense and income categories for a user if they have none; triggered on registration and available as a backfill command for existing users.

### Modified Capabilities
<!-- No existing spec-level requirements are changing. -->

## Impact

- **API (Laravel)**: New seeder class, user registration hook (Observer or `RegisteredUserCreatedListener`), and an Artisan command for backfilling existing users.
- **Database**: No schema changes — uses existing `categories` table (`user_id`, `name`, `type`, `parent_id`, `icon`).
- **No breaking changes** — categories remain fully user-editable after seeding.
