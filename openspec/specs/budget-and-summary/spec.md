## Purpose

Defines requirements for monthly budget management and financial summary reporting, including category-level budget variance.

## Requirements

### Requirement: Users can define monthly budgets by category
The system SHALL allow authenticated users to set and update monthly budget amounts per category for a selected month and year.

#### Scenario: Setting a monthly category budget
- **WHEN** an authenticated user submits a valid budget amount for a category and month
- **THEN** the system creates or updates that budget entry for the user and target month

### Requirement: Users can view monthly financial summary and budget variance
The system SHALL provide a monthly summary with total income, total expenses, net balance, and per-category variance between budgeted and actual expenses.

#### Scenario: Getting monthly summary
- **WHEN** an authenticated user requests summary data for a specific month
- **THEN** the system returns totals for income, expenses, net balance, and category-level budget variance for that user

#### Scenario: Summary with no transactions
- **WHEN** an authenticated user requests summary data for a month with no transactions
- **THEN** the system returns zero totals and empty or zeroed category variance without failure
