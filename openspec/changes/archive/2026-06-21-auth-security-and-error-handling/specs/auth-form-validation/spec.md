## ADDED Requirements

### Requirement: Login form shows descriptive field-level errors
The login form SHALL display specific error messages returned by the API per field (e.g., invalid email format, wrong credentials) rather than a single generic fallback string. Error messages SHALL appear inline below the respective field.

#### Scenario: Wrong credentials
- **WHEN** the user submits the login form with a valid email but incorrect password
- **THEN** the form SHALL display the server's error message (e.g., "These credentials do not match our records.") below the email field

#### Scenario: Network or unknown error
- **WHEN** the API returns a non-validation error (5xx or network failure)
- **THEN** the form SHALL display a generic fallback error message using the `auth.failed` i18n key in an Alert component

### Requirement: Register form shows descriptive field-level errors
The register form SHALL display specific API validation errors per field (email already taken, password too weak, etc.) inline below the relevant input. The generic fallback SHALL use an i18n key, not a hardcoded English string.

#### Scenario: Duplicate email on registration
- **WHEN** the user submits the register form with an email that already exists
- **THEN** the form SHALL display the server's validation message below the email field (e.g., "The email has already been taken.")

#### Scenario: Weak password on registration
- **WHEN** the user submits the register form with a password that fails complexity rules
- **THEN** the form SHALL display the server's validation message below the password field

#### Scenario: Generic fallback error
- **WHEN** the API returns a non-validation error during registration
- **THEN** the form SHALL display a localized generic error using the `auth.register_failed` i18n key

### Requirement: Client-side validation before API call
Both login and register forms SHALL validate inputs on the client before submitting to the API, surfacing errors immediately without a network round-trip.

#### Scenario: Empty email field on login submit
- **WHEN** the user clicks the login button with an empty email field
- **THEN** the form SHALL display a required-field error below the email input and NOT submit to the API

#### Scenario: Invalid email format on login submit
- **WHEN** the user clicks the login button with a malformed email (e.g., "notanemail")
- **THEN** the form SHALL display an invalid-email error below the email input and NOT submit to the API

#### Scenario: Password too short on login submit
- **WHEN** the user clicks the login button with a password shorter than 8 characters
- **THEN** the form SHALL display a minimum-length error below the password input and NOT submit to the API

#### Scenario: Empty name field on register submit
- **WHEN** the user clicks the register button with an empty name field
- **THEN** the form SHALL display a required-field error below the name input and NOT submit to the API

### Requirement: Password confirmation field in register form
The register form SHALL include a "Confirm password" field. The form SHALL NOT submit if the confirmation does not match the password. The confirmation value SHALL NOT be sent to the API.

#### Scenario: Mismatched password confirmation
- **WHEN** the user fills in the password and confirm-password fields with different values and clicks register
- **THEN** the form SHALL display a mismatch error below the confirm-password field and NOT submit to the API

#### Scenario: Matching password confirmation
- **WHEN** the user fills in matching password and confirm-password values
- **THEN** the form SHALL submit normally without a confirmation error

### Requirement: Password strength hint on register form
The register form SHALL display a hint below the password field describing the minimum requirements (at least 8 characters, at least one number or special character) so users know the rules before submitting.

#### Scenario: Hint visible while filling in password
- **WHEN** the user focuses the password field on the register form
- **THEN** a hint text SHALL be visible below the field describing the password requirements

### Requirement: Backend password complexity validation on register
The register API endpoint SHALL reject passwords that do not contain at least one digit or non-letter character, in addition to the existing 8-character minimum.

#### Scenario: Password with only letters is rejected
- **WHEN** a registration request is sent with a password of 8+ letters but no digits or special characters (e.g., "password")
- **THEN** the API SHALL return HTTP 422 with a validation error on the `password` field

#### Scenario: Password with digit is accepted
- **WHEN** a registration request is sent with a password that meets the minimum length and contains at least one digit (e.g., "password1")
- **THEN** the API SHALL return HTTP 201 and create the user

### Requirement: Rate limiting on auth endpoints
The login and register API endpoints SHALL enforce a rate limit to prevent brute-force attacks. Clients that exceed the limit SHALL receive an HTTP 429 response.

#### Scenario: Exceeding login rate limit
- **WHEN** a client sends more than 5 POST requests to `/v1/auth/login` within one minute from the same IP
- **THEN** the API SHALL respond with HTTP 429 and reject further requests until the window resets

#### Scenario: Exceeding register rate limit
- **WHEN** a client sends more than 5 POST requests to `/v1/auth/register` within one minute from the same IP
- **THEN** the API SHALL respond with HTTP 429

### Requirement: All auth error messages are i18n-covered
Every error string shown in the login and register forms SHALL be defined as an i18n key in both `pt-BR` and `en` translations. No hardcoded English strings SHALL remain in auth form error handling.

#### Scenario: Portuguese locale shows translated auth errors
- **WHEN** the app locale is `pt-BR` and a login error occurs
- **THEN** the displayed error message SHALL be in Portuguese

#### Scenario: English locale shows translated auth errors
- **WHEN** the app locale is `en` and a registration error occurs
- **THEN** the displayed error message SHALL be in English
