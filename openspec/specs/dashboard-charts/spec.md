## Purpose

Define the visual chart data and display requirements for the HomePage dashboard, scoped to the user's selected month.

## Requirements

### Requirement: HomePage SHALL display an expenses-by-category chart for the selected month
The HomePage SHALL render a donut (or pie) chart showing the breakdown of total spending per expense category for the currently selected month.

#### Scenario: Month has expense transactions across multiple categories
- **WHEN** the user views the HomePage for a month with expenses in multiple categories
- **THEN** the chart displays each category as a slice proportional to its share of total expenses for that month

#### Scenario: A category has no expenses in the selected month
- **WHEN** a category has zero expense transactions in the selected month
- **THEN** that category does not appear in the chart

#### Scenario: Month has no expense transactions
- **WHEN** the selected month has no expense transactions
- **THEN** the chart area shows an empty-state message instead of a chart

#### Scenario: Chart is capped at a maximum number of slices
- **WHEN** the number of distinct expense categories exceeds 8
- **THEN** the top 8 categories by spend are shown individually, and the remainder are grouped into a single "Other" slice

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the expenses-by-category chart updates to reflect data for the new month

### Requirement: HomePage SHALL display an income-vs-expenses-per-day chart for the selected month
The HomePage SHALL render a bar chart showing daily income and daily expenses side-by-side for every calendar day in the selected month.

#### Scenario: Month has transactions on some days
- **WHEN** the user views the HomePage for a month with transactions
- **THEN** days with transactions show bars for income and/or expenses; days with no transactions show zero-height bars

#### Scenario: A day has only income
- **WHEN** a day has income transactions but no expense transactions
- **THEN** only the income bar is non-zero for that day

#### Scenario: A day has only expenses
- **WHEN** a day has expense transactions but no income transactions
- **THEN** only the expense bar is non-zero for that day

#### Scenario: Month has no transactions at all
- **WHEN** the selected month has no transactions
- **THEN** the chart shows all-zero bars or an empty-state message

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the income-vs-expenses-per-day chart updates to reflect data for the new month

### Requirement: Chart data SHALL be sourced from dedicated API endpoints
The frontend SHALL fetch chart data from two dedicated endpoints: one for category breakdown and one for daily breakdown. Chart data SHALL be scoped to the authenticated user and the selected month.

#### Scenario: Frontend requests category chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/by-category?month=YYYY-MM` and uses the response to render the expenses-by-category chart

#### Scenario: Frontend requests daily chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/by-day?month=YYYY-MM` and uses the response to render the income-vs-expenses-per-day chart

#### Scenario: API response for by-category
- **WHEN** `GET /dashboard/by-category?month=YYYY-MM` is called
- **THEN** the API returns an array of objects each with a `name` (category name), `value` (total expense amount), and optionally `color` for the chart

#### Scenario: API response for by-day
- **WHEN** `GET /dashboard/by-day?month=YYYY-MM` is called
- **THEN** the API returns an array of objects each with a `day` (day of month integer), `income` (total income for that day), and `expense` (total expense for that day), covering all calendar days in the month
