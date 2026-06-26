## MODIFIED Requirements

### Requirement: Users can authenticate and access only their data
The system SHALL require authentication for protected finance endpoints, MUST enforce user-level data isolation across transactions, budgets, and summaries, and MUST additionally require email verification before granting access to protected routes.

#### Scenario: Accessing protected endpoint without authentication
- **WHEN** a request is sent to a protected finance endpoint without valid authentication
- **THEN** the system denies access with an authentication error response

#### Scenario: Preventing cross-user data access
- **WHEN** an authenticated user attempts to read or modify another user's finance resource
- **THEN** the system denies access and does not reveal unauthorized resource data

#### Scenario: Accessing protected endpoint without email verification
- **WHEN** an authenticated but unverified user sends a request to a protected finance endpoint
- **THEN** the system returns HTTP 403 with error code `email_not_verified`
