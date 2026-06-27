## Purpose

Define requirements for registering and managing credit card expenses split into installments, including automatic multi-record creation, group-linked cascade operations, and form-level UX controls.

## Requirements

### Requirement: User can register a credit card expense as installments
The system SHALL allow an authenticated user to register a credit card expense as an installment purchase, providing the current installment number and total installments. On save, the system SHALL automatically create one transaction record per installment across the corresponding months, all linked by a shared group identifier.

#### Scenario: Installment toggle is only available for credit card expenses
- **WHEN** the user selects `cartao_credito` as payment method AND `expense` (saída) as transaction type in the new transaction form
- **THEN** the form reveals an installment toggle (on/off)

#### Scenario: Installment toggle hidden for non-credit-card or income
- **WHEN** the user selects any payment method other than `cartao_credito`, OR selects `income` (entrada) as type
- **THEN** the installment toggle is hidden and installment fields are not submitted

#### Scenario: Installment fields appear when toggle is on
- **WHEN** the installment toggle is set to on
- **THEN** the form shows two numeric inputs: current installment number and total installments

#### Scenario: System creates all installment records on submission
- **WHEN** the user submits the form with installment toggle on, current installment = N, total = T, and date = D
- **THEN** the system creates T transaction records, each sharing the same amount, category, payment method, and notes, with `transacted_at` offset by month so that record N falls on month D, records 1..N-1 fall on prior months, and records N+1..T fall on subsequent months

#### Scenario: All installment records share a group identifier
- **WHEN** an installment purchase is created
- **THEN** all generated transaction records have the same `installment_group_id` UUID, plus their individual `installment_number` and `installment_total`

#### Scenario: Editing one installment cascades to all in the group
- **WHEN** the user edits any field (amount, category, notes, payment method) on a transaction that belongs to an installment group
- **THEN** the system updates all transactions in that group with the same values, preserving each record's individual `transacted_at` date and `installment_number`

#### Scenario: User is warned before editing an installment
- **WHEN** the user initiates an edit on a transaction that belongs to an installment group
- **THEN** the frontend displays a confirmation dialog informing that all installments in the group will be updated

#### Scenario: Deleting one installment deletes all in the group
- **WHEN** the user deletes a transaction that belongs to an installment group
- **THEN** the system deletes all transactions sharing the same `installment_group_id`

#### Scenario: User is warned before deleting an installment
- **WHEN** the user initiates a delete on a transaction that belongs to an installment group
- **THEN** the frontend displays a confirmation dialog informing that all installments in the group will be deleted

#### Scenario: Validation rejects invalid installment numbers
- **WHEN** the user submits with current installment number ≤ 0, > total installments, or total installments > 60
- **THEN** the system rejects the request with descriptive validation errors and creates no records
