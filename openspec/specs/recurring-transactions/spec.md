## Purpose

Lets users define an open-ended recurring transaction (e.g. a streaming subscription or recurring rent payment) that automatically generates a new transaction each month until the user cancels it, and gives them a dedicated place to review and manage all such recurring series.

## Requirements

### Requirement: User can create a recurring transaction rule
The system SHALL allow an authenticated user to mark a transaction as recurring at creation time, providing category, type, amount, payment method, an optional note, and the day of month on which it should relaunch. On save, the system SHALL create a `RecurringTransaction` rule in `active` status and the first `Transaction` record for the current cycle, linked to that rule.

#### Scenario: Recurring toggle available regardless of type or payment method
- **WHEN** the user opens the new transaction form
- **THEN** a recurring toggle (on/off) is available regardless of the selected transaction type or payment method

#### Scenario: Recurring and installment toggles are mutually exclusive
- **WHEN** the recurring toggle is switched on
- **THEN** the installment toggle is hidden (and vice versa), so a single transaction cannot be both recurring and an installment

#### Scenario: Creating a recurring transaction creates a rule and the first transaction
- **WHEN** the user submits the form with the recurring toggle on, category C, amount A, type T, payment method P, and date D
- **THEN** the system creates a `RecurringTransaction` rule with status `active`, day-of-month derived from D, and a `Transaction` record dated D linked to that rule

#### Scenario: Validation rejects invalid recurring configuration
- **WHEN** the user submits a recurring transaction with a non-positive amount or a category whose `type` does not match the transaction `type`
- **THEN** the system rejects the request with descriptive validation errors and creates neither the rule nor a transaction

### Requirement: System relaunches active recurring transactions monthly
The system SHALL run a scheduled process that, for each `active` `RecurringTransaction` rule whose next relaunch date has arrived, creates exactly one new `Transaction` record for the current cycle linked to that rule, dated on the rule's configured day of month (or the last day of the month if the month is shorter).

#### Scenario: Relaunch creates one transaction per due rule
- **WHEN** the scheduled process runs and an active rule's next relaunch date falls on or before the current date
- **THEN** the system creates one new transaction record with the rule's category, type, amount, payment method, and notes, dated on the rule's day of month for the current cycle

#### Scenario: Relaunch is idempotent per cycle
- **WHEN** the scheduled process runs more than once within the same monthly cycle for the same rule
- **THEN** the system creates at most one transaction for that rule for that cycle

#### Scenario: Relaunch skips cancelled rules
- **WHEN** the scheduled process runs
- **THEN** rules with status `cancelled` are not evaluated and no new transaction is created for them

#### Scenario: Relaunch handles short months
- **WHEN** a rule's configured day of month is 29, 30, or 31 and the current cycle's month has fewer days
- **THEN** the system dates the generated transaction on the last day of that month

#### Scenario: Relaunch invalidates the dashboard summary cache
- **WHEN** the scheduled process generates one or more transactions for a user
- **THEN** the system invalidates that user's cached dashboard summary so subsequently requested summaries reflect the newly generated transactions

### Requirement: User can view and manage recurring transactions in a dedicated menu
The system SHALL provide a dedicated page listing all of the authenticated user's recurring transaction rules, showing category, amount, day of month, status, and the date of the most recently generated transaction.

#### Scenario: Listing recurring rules
- **WHEN** an authenticated user opens the recurring transactions page
- **THEN** the system displays all of that user's recurring rules (active and cancelled), ordered with active rules first

#### Scenario: Distinguishing active and cancelled rules
- **WHEN** the recurring transactions page renders a rule
- **THEN** it visually indicates whether the rule is `active` or `cancelled`

### Requirement: User can cancel a recurring transaction
The system SHALL allow an authenticated user to cancel one of their active recurring transaction rules. Cancelling SHALL set the rule's status to `cancelled` and prevent any future relaunch, without modifying or deleting transactions already generated from that rule.

#### Scenario: Cancelling stops future relaunches
- **WHEN** an authenticated user cancels an active recurring rule
- **THEN** the rule's status becomes `cancelled` and the scheduled relaunch process no longer generates transactions for it in any future cycle

#### Scenario: Cancelling preserves transaction history
- **WHEN** an authenticated user cancels a recurring rule that has already generated transactions
- **THEN** all previously generated transactions remain unchanged and continue to appear in the transaction list

#### Scenario: Cancelling a rule the user does not own is rejected
- **WHEN** an authenticated user attempts to cancel a recurring rule belonging to another user
- **THEN** the system rejects the request and makes no change

#### Scenario: Cancelling an already-cancelled rule is a no-op
- **WHEN** an authenticated user cancels a rule that is already `cancelled`
- **THEN** the system leaves the rule's status unchanged and returns a successful response

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
