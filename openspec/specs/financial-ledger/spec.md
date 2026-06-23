## Purpose

Defines requirements for personal financial transaction management, including creation, editing, listing, deletion, and validation rules.

## Requirements

### Requirement: Users can manage personal financial transactions
The system SHALL allow authenticated users to create, edit, list, and delete personal income and expense transactions with date, amount, category, and optional notes.

#### Scenario: Creating an expense transaction
- **WHEN** an authenticated user submits a valid expense with amount, date, and category
- **THEN** the system stores the transaction under that user account and returns the created record

#### Scenario: Listing only user-owned transactions
- **WHEN** an authenticated user requests the transaction list
- **THEN** the system returns only transactions owned by that user, ordered by transaction date descending by default

#### Scenario: Rejecting invalid transaction payload
- **WHEN** a user submits a transaction with missing required fields or non-positive amount
- **THEN** the system rejects the request with validation errors and does not persist data
