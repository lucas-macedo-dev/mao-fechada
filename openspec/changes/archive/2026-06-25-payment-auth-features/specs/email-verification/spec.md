## ADDED Requirements

### Requirement: Users must verify their email address after registration
The system SHALL send a verification email upon successful registration and MUST block access to protected routes until the user's email is verified.

#### Scenario: Verification email sent on registration
- **WHEN** a new user completes registration
- **THEN** the system sends an email to the registered address containing a signed verification link valid for 60 minutes

#### Scenario: Accessing protected routes before verification
- **WHEN** an authenticated but unverified user sends a request to a protected endpoint
- **THEN** the system returns HTTP 403 with error code `email_not_verified`

#### Scenario: Successful email verification
- **WHEN** a user clicks the signed verification link before it expires
- **THEN** the system sets `email_verified_at` on the user record and returns HTTP 200 confirming verification

#### Scenario: Expired verification link
- **WHEN** a user clicks a verification link after it has expired
- **THEN** the system returns HTTP 400 with error code `verification_link_expired`

#### Scenario: Resend verification email
- **WHEN** an authenticated unverified user requests a new verification email via `POST /v1/auth/email/resend`
- **THEN** the system sends a fresh signed verification link and returns HTTP 200

#### Scenario: Already verified user requests resend
- **WHEN** an already-verified user requests a new verification email
- **THEN** the system returns HTTP 422 with error code `already_verified`
