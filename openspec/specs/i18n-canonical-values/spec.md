## Purpose

Codebase-wide requirement that domain enum values are canonical `en-US` strings, with translation applied only at the display/label layer, never to the underlying value used by logic, storage, or the API contract.

## Requirements

### Requirement: Domain enum values are canonical English
For any field with a fixed, code-like set of values (e.g. transaction `type`, `payment_method`), the system SHALL store, validate, and transmit only canonical `en-US` string identifiers. Translation to a human-readable string SHALL happen exclusively in the presentation layer, applied to the label shown to the user, never to the value used for storage, filtering, comparison, or the API contract.

#### Scenario: Backend rejects non-canonical values
- **WHEN** a client submits a request containing a non-English legacy value for a canonical enum field (e.g. `entrada`, `saida`, `cartao_credito`)
- **THEN** the system rejects the request with a validation error, the same as any other invalid value for that field

#### Scenario: Frontend option value is not translated
- **WHEN** the frontend renders a selectable option for a canonical enum field (e.g. a transaction type or payment method dropdown)
- **THEN** the option's `value` SHALL be the canonical English identifier, and only its displayed `label` SHALL be produced via translation (`t()`)

#### Scenario: API responses use canonical values
- **WHEN** an authenticated client requests a resource containing a canonical enum field
- **THEN** the field's value in the JSON response SHALL be the canonical English identifier, regardless of the requesting user's locale
