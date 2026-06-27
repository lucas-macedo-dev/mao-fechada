## ADDED Requirements

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
