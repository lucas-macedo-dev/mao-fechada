## ADDED Requirements

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
