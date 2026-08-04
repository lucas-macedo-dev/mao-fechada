## Purpose

Defines requirements for personal financial transaction management, including creation, editing, listing, deletion, and validation rules.

## Requirements

### Requirement: Users can manage personal financial transactions
The system SHALL allow authenticated users to create, edit, list, and delete personal income and expense transactions with date, amount, category, and optional notes. When a transaction belongs to an installment group, edit and delete operations SHALL cascade to all transactions in the group. Create and edit interactions SHALL be presented in a modal dialog rather than inline within the page.

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

#### Scenario: Create form opens in a modal
- **WHEN** the user clicks the "New transaction" button
- **THEN** a `FormModal` opens containing the create transaction form with default values

#### Scenario: Edit form opens in a modal
- **WHEN** the user clicks the edit button on a transaction row
- **THEN** a `FormModal` opens containing the edit form pre-populated with the transaction's current values

#### Scenario: Installment group edit requires custom confirmation
- **WHEN** the user attempts to edit or delete a transaction that belongs to an installment group
- **THEN** a `ConfirmDialog` opens (replacing `window.confirm`) describing the cascade impact before proceeding

### Requirement: Transaction list supports filtering by installment type
The system SHALL allow filtering the transaction list to return only installment-type expense transactions.

#### Scenario: Filtering to installment transactions
- **WHEN** an authenticated user calls `GET /transactions?installment=true`
- **THEN** the system returns only transactions where `installment_group_id` is not null, paginated in the standard format

#### Scenario: Non-installment filter returns all transactions
- **WHEN** an authenticated user calls `GET /transactions` without the `installment` param
- **THEN** the system returns all user transactions regardless of installment status

### Requirement: Transaction type and payment method use canonical English values
Transaction and category `type` SHALL only accept `income` or `expense`. Transaction `payment_method` SHALL only accept `credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, or `bank_transfer`. No Portuguese alias (`entrada`, `saida`, `cartao_credito`, `cartao_debito`, `dinheiro`, `boleto`, `ted`) SHALL be accepted as valid input for either field.

#### Scenario: Rejecting a legacy type alias
- **WHEN** a client submits a transaction or category with `type` set to `entrada` or `saida`
- **THEN** the system rejects the request with a validation error

#### Scenario: Rejecting a legacy payment method
- **WHEN** a client submits a transaction with `payment_method` set to any pre-migration Portuguese value (e.g. `cartao_credito`, `dinheiro`, `boleto`)
- **THEN** the system rejects the request with a validation error

#### Scenario: Existing transactions are migrated to canonical values
- **WHEN** the `payment_method` migration runs against existing transaction rows
- **THEN** every row's `payment_method` SHALL be rewritten from its Portuguese value to the corresponding canonical English value, with `pix` left unchanged, and no data loss

#### Scenario: CSV report exports canonical values
- **WHEN** a transaction report is exported to CSV
- **THEN** the `Type` and `Payment Method` columns SHALL contain the canonical English value stored on the transaction, not a translated or aliased form
