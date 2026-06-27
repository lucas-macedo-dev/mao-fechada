## MODIFIED Requirements

### Requirement: Users can manage personal financial transactions
The system SHALL allow authenticated users to create, edit, list, and delete personal income and expense transactions with date, amount, category, and optional notes. When a transaction belongs to an installment group, edit and delete operations SHALL cascade to all transactions in the group.

#### Scenario: Creating an expense transaction
- **WHEN** an authenticated user submits a valid expense with amount, date, and category
- **THEN** the system stores the transaction under that user account and returns the created record

#### Scenario: Listing only user-owned transactions
- **WHEN** an authenticated user requests the transaction list
- **THEN** the system returns only transactions owned by that user, ordered by transaction date descending by default

#### Scenario: Rejecting invalid transaction payload
- **WHEN** a user submits a transaction with missing required fields or non-positive amount
- **THEN** the system rejects the request with validation errors and does not persist data

#### Scenario: Editing a transaction in an installment group cascades
- **WHEN** an authenticated user updates a transaction that has an `installment_group_id`
- **THEN** the system applies the same field changes (amount, category_id, payment_method, notes, type) to all transactions sharing that `installment_group_id`, leaving each record's `transacted_at` and `installment_number` unchanged

#### Scenario: Deleting a transaction in an installment group cascades
- **WHEN** an authenticated user deletes a transaction that has an `installment_group_id`
- **THEN** the system deletes all transactions sharing that `installment_group_id` in a single operation

## ADDED Requirements

### Requirement: Transaction list supports filtering by installment type
The system SHALL allow filtering the transaction list to return only installment-type expense transactions.

#### Scenario: Filtering to installment transactions
- **WHEN** an authenticated user calls `GET /transactions?installment=true`
- **THEN** the system returns only transactions where `installment_group_id` is not null, paginated in the standard format

#### Scenario: Non-installment filter returns all transactions
- **WHEN** an authenticated user calls `GET /transactions` without the `installment` param
- **THEN** the system returns all user transactions regardless of installment status
