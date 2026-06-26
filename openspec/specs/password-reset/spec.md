## Purpose

Defines requirements for the password reset flow, covering how users request a reset link via email and how they submit a new password using the token received.

## Requirements

### Requirement: Users can request a password reset link
The system SHALL allow unauthenticated users to request a password reset email by providing their registered email address.

#### Scenario: Valid email submitted for password reset
- **WHEN** a user submits `POST /v1/auth/forgot-password` with a registered email address
- **THEN** the system sends a password reset email with a token-bearing link and returns HTTP 200

#### Scenario: Unknown email submitted for password reset
- **WHEN** a user submits `POST /v1/auth/forgot-password` with an email that does not match any account
- **THEN** the system returns HTTP 200 (indistinguishable from success to prevent email enumeration)

#### Scenario: Rate limiting on forgot-password requests
- **WHEN** more than 5 forgot-password requests are made from the same IP within 1 minute
- **THEN** the system returns HTTP 429 and rejects further requests until the window resets

### Requirement: Users can reset their password using a valid token
The system SHALL allow users to set a new password by submitting the reset token received via email.

#### Scenario: Valid token and matching passwords submitted
- **WHEN** a user submits `POST /v1/auth/reset-password` with a valid token, email, password, and password_confirmation
- **THEN** the system updates the user's password, invalidates the token, and returns HTTP 200

#### Scenario: Invalid or expired token submitted
- **WHEN** a user submits `POST /v1/auth/reset-password` with an expired or invalid token
- **THEN** the system returns HTTP 422 with error code `invalid_reset_token`

#### Scenario: Password confirmation mismatch
- **WHEN** a user submits `POST /v1/auth/reset-password` with password and password_confirmation that do not match
- **THEN** the system returns HTTP 422 with validation error on `password_confirmation`
