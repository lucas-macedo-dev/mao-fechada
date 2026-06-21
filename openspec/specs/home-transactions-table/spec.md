## Purpose

Define how recent transactions are displayed on the home dashboard.

## Requirements

### Requirement: Home dashboard SHALL display recent transactions as a responsive table
The "Últimos lançamentos" section of the home dashboard SHALL render transactions in a Mantine `Table` wrapped in a `ScrollArea`, showing up to 15 rows. Columns SHALL be: Date, Category (with icon), and Amount. The table SHALL scroll horizontally on small screens rather than overflow or collapse.

#### Scenario: Transactions are present
- **WHEN** the dashboard loads with transactions for the selected month
- **THEN** a table is displayed with one row per transaction, up to 15 rows, with Date, Category, and Amount columns

#### Scenario: Amount is color-coded by type
- **WHEN** a transaction row is rendered
- **THEN** income amounts are displayed in green with a "+" prefix and expense amounts in red with a "−" prefix

#### Scenario: No transactions for the month
- **WHEN** the selected month has no transactions
- **THEN** an empty-state message is displayed in place of the table

#### Scenario: Table on a narrow screen
- **WHEN** the viewport is mobile-width
- **THEN** the table container scrolls horizontally without breaking the page layout

#### Scenario: Maximum row count
- **WHEN** the API returns more than 15 transactions
- **THEN** only 15 rows are displayed (request is made with `per_page=15`)
