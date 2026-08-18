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

### Requirement: HomePage SHALL display an expenses-per-day chart
The HomePage SHALL render a line chart showing daily total expenses for every calendar day in the selected month.

#### Scenario: Month has expense transactions on some days
- **WHEN** the user views the HomePage for a month with expense transactions
- **THEN** days with expenses show a non-zero point on the line; days with no expenses show a zero point

#### Scenario: Month has no expense transactions at all
- **WHEN** the selected month has no expense transactions
- **THEN** the chart shows an all-zero line or an empty-state message

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the expenses-per-day chart updates to reflect data for the new month

### Requirement: Chart data SHALL be sourced from dedicated API endpoints
The frontend SHALL fetch chart data from dedicated endpoints: one for category breakdown, one for daily breakdown, one for monthly income/expense comparison, one for expenses-to-date comparison, one for payment-method breakdown, and one for weekly expenses. Month-scoped endpoints SHALL be scoped to the authenticated user and the selected month; the weekly-expenses endpoint SHALL be scoped to the authenticated user and the current real calendar week.

#### Scenario: Frontend requests category chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/by-category?month=YYYY-MM` and uses the response to render the expenses-by-category donut and the main-categories bar chart

#### Scenario: Frontend requests daily chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/by-day?month=YYYY-MM` and uses the response's `expense` field to render the expenses-per-day chart

#### Scenario: API response for by-category
- **WHEN** `GET /dashboard/by-category?month=YYYY-MM` is called
- **THEN** the API returns an array of objects each with a `name` (main category name), `value` (total expense amount), and optionally `color` for the chart

#### Scenario: API response for by-day
- **WHEN** `GET /dashboard/by-day?month=YYYY-MM` is called
- **THEN** the API returns an array of objects each with a `day` (day of month integer), `income` (total income for that day), and `expense` (total expense for that day), covering all calendar days in the month

#### Scenario: Frontend requests monthly comparison chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/monthly-comparison?month=YYYY-MM` and uses the response to render the income-vs-expenses monthly comparison chart

#### Scenario: API response for monthly-comparison
- **WHEN** `GET /dashboard/monthly-comparison?month=YYYY-MM` is called
- **THEN** the API returns `{ "months": [...] }` with exactly 3 entries ordered oldest to newest — the selected month and the 2 calendar months immediately before it — each with `month` (string `YYYY-MM`), `income` (decimal), and `expense` (decimal)

#### Scenario: Frontend requests expenses-to-date comparison data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/expenses-mtd-comparison?month=YYYY-MM` and uses the response to render the expenses-to-date vs. previous-month indicator

#### Scenario: API response for expenses-mtd-comparison
- **WHEN** `GET /dashboard/expenses-mtd-comparison?month=YYYY-MM` is called
- **THEN** the API returns `{ "current": { "month", "through_day", "total" }, "previous": { "month", "through_day", "total" }, "change_percent" }`, where `change_percent` is a decimal or `null` when `previous.total` is `0`

#### Scenario: Frontend requests payment-method chart data
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/by-payment-method?month=YYYY-MM` and uses the response to render the expenses-by-payment-method chart

#### Scenario: API response for by-payment-method
- **WHEN** `GET /dashboard/by-payment-method?month=YYYY-MM` is called
- **THEN** the API returns an array of objects each with a `name` (canonical payment method code: `credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, or `bank_transfer`) and `value` (total expense amount for that payment method in the selected month)

#### Scenario: Frontend requests weekly expenses chart data
- **WHEN** the HomePage mounts
- **THEN** the frontend calls `GET /dashboard/weekly-expenses` (no `month` param) and uses the response to render the weekly expenses chart

#### Scenario: API response for weekly-expenses
- **WHEN** `GET /dashboard/weekly-expenses` is called
- **THEN** the API returns an array of exactly 7 objects, one per weekday of the current real calendar week (Monday through Sunday), each with `weekday` (integer `1`–`7`, Monday `1`), `date` (string `YYYY-MM-DD`), and `expense` (total expense amount for that day)

### Requirement: HomePage SHALL display an installment purchases summary card
The HomePage SHALL render a summary card showing the total expense amount from installment transactions for the currently selected month.

#### Scenario: Month has installment transactions
- **WHEN** the user views the HomePage for a month that contains at least one transaction with an `installment_group_id`
- **THEN** the installment summary card displays the sum of amounts for all installment-type expense transactions in that month

#### Scenario: Month has no installment transactions
- **WHEN** the selected month has no installment transactions
- **THEN** the installment summary card displays R$ 0,00 or an appropriate zero-state message

#### Scenario: Card updates when month changes
- **WHEN** the user changes the month selector on the HomePage
- **THEN** the installment summary card refreshes to reflect the installment total for the newly selected month

#### Scenario: Frontend fetches installment total from dedicated endpoint
- **WHEN** the HomePage mounts or the month changes
- **THEN** the frontend calls `GET /dashboard/installments-total?month=YYYY-MM` and uses the `total` field in the response to populate the installment card

#### Scenario: API response for installments-total
- **WHEN** `GET /dashboard/installments-total?month=YYYY-MM` is called by an authenticated user
- **THEN** the API returns an object with `month` (string YYYY-MM) and `total` (decimal, the sum of amounts of all installment expense transactions for that user in that month)

### Requirement: HomePage SHALL display an income-vs-expenses monthly comparison chart
The HomePage SHALL render a grouped bar chart comparing total income and total expenses across the currently selected month and the 2 calendar months immediately preceding it.

#### Scenario: All 3 months have transactions
- **WHEN** the user views the HomePage and each of the 3 months has income and/or expense transactions
- **THEN** the chart displays one grouped bar pair (income, expense) per month, ordered oldest to newest

#### Scenario: One of the 3 months has no transactions
- **WHEN** one of the 3 months has no transactions
- **THEN** that month's bars display as zero rather than being omitted from the chart

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the monthly comparison chart updates so the newly selected month becomes the most recent of the 3 shown

### Requirement: HomePage SHALL display an expenses-to-date vs. previous-month comparison indicator
The HomePage SHALL render a stat indicator showing the percent change between the selected month's expenses through a cutoff day and the previous calendar month's expenses through the same cutoff day. When the selected month is the current calendar month, the cutoff day SHALL be today's day-of-month; when the selected month is a past month, the cutoff day SHALL be that month's last day (a full-month comparison).

#### Scenario: Viewing the current month
- **WHEN** the user views the HomePage with the current calendar month selected
- **THEN** the indicator compares this month's expenses from day 1 through today against last month's expenses from day 1 through the same day-of-month, clamped to the previous month's length

#### Scenario: Viewing a past, fully-elapsed month
- **WHEN** the user views the HomePage with a past month selected
- **THEN** the indicator compares that month's full-month expense total against the immediately preceding month's full-month expense total

#### Scenario: Previous period has zero expenses
- **WHEN** the comparison period's previous-month total is `0`
- **THEN** the indicator displays a neutral/undefined state instead of a percentage (no divide-by-zero result is shown)

#### Scenario: Expenses increased or decreased vs. the comparison period
- **WHEN** the current period's total differs from the previous period's total
- **THEN** the indicator displays the signed percent change (e.g. "+12%" or "-8%")

### Requirement: HomePage SHALL display an expenses-by-payment-method chart
The HomePage SHALL render a donut chart showing the breakdown of total expenses per payment method for the currently selected month.

#### Scenario: Month has expenses across multiple payment methods
- **WHEN** the user views the HomePage for a month with expenses paid via multiple payment methods
- **THEN** the chart displays each payment method as a slice proportional to its share of total expenses for that month, labeled with the translated payment method name

#### Scenario: A payment method has no expenses in the selected month
- **WHEN** a payment method has zero expense transactions in the selected month
- **THEN** that payment method does not appear in the chart

#### Scenario: Month has no expense transactions
- **WHEN** the selected month has no expense transactions
- **THEN** the chart area shows an empty-state message instead of a chart

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the expenses-by-payment-method chart updates to reflect data for the new month

### Requirement: HomePage SHALL display a weekly expenses chart
The HomePage SHALL render a bar chart showing total expenses per day (Monday through Sunday) for the current real-world calendar week, independent of the HomePage month selector.

#### Scenario: Current week has expenses on some days
- **WHEN** the user views the HomePage during a week with expense transactions
- **THEN** days with expenses show a non-zero bar; days with no expenses show a zero-height bar

#### Scenario: Current week has no expense transactions
- **WHEN** the current calendar week has no expense transactions
- **THEN** the chart shows all-zero bars or an empty-state message

#### Scenario: HomePage month selector is changed
- **WHEN** the user changes the HomePage month selector
- **THEN** the weekly expenses chart does not change, since it always reflects the current real calendar week

### Requirement: HomePage SHALL display a main-categories bar chart
The HomePage SHALL render a vertical bar chart showing the same main-category expense breakdown as the existing expenses-by-category donut, displayed alongside it (not replacing it) for the currently selected month.

#### Scenario: Month has expense transactions across multiple main categories
- **WHEN** the user views the HomePage for a month with expenses in multiple main categories
- **THEN** the bar chart displays one bar per main category, sized by that category's total expense amount, using the same top-8-plus-"Outros" grouping as the category donut

#### Scenario: Month has no expense transactions
- **WHEN** the selected month has no expense transactions
- **THEN** the main-categories bar chart area shows an empty-state message instead of a chart

#### Scenario: User changes the month selector
- **WHEN** the user selects a different month
- **THEN** the main-categories bar chart updates to reflect data for the new month, consistent with the category donut
