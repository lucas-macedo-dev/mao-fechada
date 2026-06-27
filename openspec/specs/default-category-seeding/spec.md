## Purpose

Defines the rules for automatically seeding a predefined set of locale-aware categories for new users upon registration, and for backfilling existing users who have no categories.

## Requirements

### Requirement: New users receive default categories on registration
The system SHALL automatically create a predefined set of expense and income categories for every new user immediately after their account is created. Categories SHALL be locale-aware: users with locale `pt-BR` receive Portuguese names; all other locales receive English names.

**Default expense categories (type = expense):**
| pt-BR | en |
|---|---|
| Veículo | Vehicle |
| Habitação | Housing |
| Alimentação | Food |
| Compras | Shopping |
| Lazer | Leisure |
| Sem Categoria | No Category |

**Default income categories (type = income):**
| pt-BR | en |
|---|---|
| Salário | Salary |
| Investimentos | Investments |
| Ticket Alimentação | Food Vouchers |
| Sem Categoria | No Category |

#### Scenario: New user with pt-BR locale gets Portuguese default categories
- **WHEN** a new user registers with `locale = pt-BR`
- **THEN** the system SHALL create 6 expense categories named Veículo, Habitação, Alimentação, Compras, Lazer, Sem Categoria and 4 income categories named Salário, Investimentos, Ticket Alimentação, Sem Categoria — all owned by that user

#### Scenario: New user with en locale gets English default categories
- **WHEN** a new user registers with `locale = en`
- **THEN** the system SHALL create 6 expense categories named Vehicle, Housing, Food, Shopping, Leisure, No Category and 4 income categories named Salary, Investments, Food Vouchers, No Category — all owned by that user

#### Scenario: New user with no locale defaults to English categories
- **WHEN** a new user registers without specifying a locale
- **THEN** the system SHALL create the English default categories including the "No Category" fallbacks

### Requirement: Default categories are user-owned and editable
Default categories SHALL be stored as regular user-scoped categories (with `user_id` set). Users SHALL be able to rename, delete, or add sub-categories to them freely after creation.

#### Scenario: User can delete a default category
- **WHEN** a user deletes a category that was created as a default
- **THEN** the category is removed with no restrictions beyond normal delete rules (e.g., cascade to transactions if configured)

### Requirement: Default seeding is idempotent
The seeding logic SHALL only create default categories for a user if that user currently has zero categories. Re-running seeding for a user who already has categories SHALL have no effect.

#### Scenario: User already has categories — seeding is skipped
- **WHEN** the default seeding routine is invoked for a user who already has at least one category
- **THEN** no categories are created or modified

### Requirement: Backfill command seeds existing users with no categories
The system SHALL provide an Artisan command (`categories:seed-defaults`) that finds all existing users who have zero categories and seeds the default set for each.

#### Scenario: Backfill command seeds users without categories
- **WHEN** `php artisan categories:seed-defaults` is executed
- **THEN** every user with zero categories receives the locale-appropriate default categories

#### Scenario: Backfill command skips users who already have categories
- **WHEN** `php artisan categories:seed-defaults` is executed
- **THEN** users who already have at least one category are not modified

#### Scenario: Backfill command outputs a summary
- **WHEN** the command completes
- **THEN** the command outputs the count of users seeded and users skipped
