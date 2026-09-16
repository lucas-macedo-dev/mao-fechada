## ADDED Requirements

### Requirement: User can convert an existing transaction into a recurring rule
The system SHALL allow an authenticated user to convert one of their existing, non-recurring, non-installment `Transaction` records into the seed of a recurring series. On conversion, the system SHALL create a `RecurringTransaction` rule in `active` status using the transaction's own category, type, amount, payment method, and notes, with day-of-month derived from the transaction's date, and SHALL link the existing transaction to that new rule. The system SHALL NOT create an additional `Transaction` record as part of this conversion.

#### Scenario: Converting an eligible transaction creates a rule and links it back
- **WHEN** the user converts an existing transaction with category C, amount A, type T, payment method P, and date D that is not already recurring and not part of an installment group
- **THEN** the system creates a `RecurringTransaction` rule with status `active` and day-of-month derived from D, and updates the existing transaction to reference that rule, without creating any new transaction

#### Scenario: Converting a transaction dated in the current cycle does not immediately regenerate
- **WHEN** the user converts a transaction dated within the current calendar month
- **THEN** the scheduled relaunch process does not generate an additional transaction for that rule during the same calendar month

#### Scenario: Converting a transaction dated in a past cycle generates the current cycle's instance on the next relaunch
- **WHEN** the user converts a transaction dated in a prior calendar month
- **THEN** the next run of the scheduled relaunch process generates exactly one new transaction for the current cycle, linked to the new rule

#### Scenario: Converting an already-recurring transaction is rejected
- **WHEN** the user attempts to convert a transaction that is already linked to a recurring rule
- **THEN** the system rejects the request with a descriptive validation error and makes no change

#### Scenario: Converting an installment transaction is rejected
- **WHEN** the user attempts to convert a transaction that belongs to an installment group
- **THEN** the system rejects the request with a descriptive validation error and makes no change

#### Scenario: Converting a transaction the user does not own is rejected
- **WHEN** the user attempts to convert a transaction belonging to another user
- **THEN** the system rejects the request and makes no change
