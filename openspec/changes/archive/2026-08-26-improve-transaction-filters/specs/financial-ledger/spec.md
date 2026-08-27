## ADDED Requirements

### Requirement: Transaction list supports filtering by category, amount range, description, and date range
The system SHALL allow filtering the transaction list by `category_id`, by an amount range (`amount_min` and/or `amount_max`), by a case-insensitive partial match against `notes`, and by an explicit date range (`date_from` and/or `date_to`) independent of the `month` filter. All filters SHALL be optional and combinable with each other and with the existing `month`, `type`, `payment_method`, and `installment` filters.

#### Scenario: Filtering by category
- **WHEN** an authenticated user calls `GET /transactions?category_id=5`
- **THEN** the system returns only transactions with `category_id` equal to `5`, paginated in the standard format

#### Scenario: Filtering by amount range
- **WHEN** an authenticated user calls `GET /transactions?amount_min=50&amount_max=200`
- **THEN** the system returns only transactions whose `amount` is greater than or equal to `50` and less than or equal to `200`

#### Scenario: Filtering by amount range with only a minimum
- **WHEN** an authenticated user calls `GET /transactions?amount_min=50` without `amount_max`
- **THEN** the system returns only transactions whose `amount` is greater than or equal to `50`

#### Scenario: Rejecting an inverted amount range
- **WHEN** an authenticated user calls `GET /transactions` with `amount_min` greater than `amount_max`
- **THEN** the system rejects the request with a validation error

#### Scenario: Filtering by description text
- **WHEN** an authenticated user calls `GET /transactions?notes=uber`
- **THEN** the system returns only transactions whose `notes` field contains "uber", case-insensitively

#### Scenario: Filtering by an explicit date range
- **WHEN** an authenticated user calls `GET /transactions?date_from=2026-08-01&date_to=2026-08-15`
- **THEN** the system returns only transactions with `transacted_at` between those two dates inclusive, regardless of whether a `month` param is also present

#### Scenario: Combining multiple filters
- **WHEN** an authenticated user calls `GET /transactions` with `category_id`, `amount_min`, `notes`, and `date_from`/`date_to` set together
- **THEN** the system returns only transactions matching all provided filters simultaneously

### Requirement: Transactions page exposes the new filters in the UI
The transactions filters card SHALL let users set a category, an amount range, a description search, and a specific date range (in addition to the existing month and type filters), and SHALL provide a way to clear all active filters back to the default view.

#### Scenario: Selecting a category filter
- **WHEN** the user selects a category (or subcategory) in the filters card
- **THEN** the transaction list refetches and shows only transactions in that category

#### Scenario: Setting an amount range
- **WHEN** the user enters values in the minimum and/or maximum amount fields
- **THEN** the transaction list refetches and shows only transactions within that range

#### Scenario: Searching by description
- **WHEN** the user types text into the description filter field
- **THEN** the transaction list refetches and shows only transactions whose notes contain that text

#### Scenario: Clearing all filters
- **WHEN** the user clicks "Clear filters"
- **THEN** all filter inputs reset to their default state and the transaction list refetches accordingly
