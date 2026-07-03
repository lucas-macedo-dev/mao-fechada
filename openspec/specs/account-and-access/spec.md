## Purpose

Defines authentication requirements and account-level access control for finance features, including user data isolation and subscription-readiness fields.

## Requirements

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

### Requirement: Accounts support future subscription-based access
The system SHALL persist account plan and subscription status fields that can be used to gate premium features in future releases.

#### Scenario: Creating account with default plan state
- **WHEN** a new user account is created
- **THEN** the system assigns default plan and subscription status values required for future paid feature checks

### Requirement: Users are identified externally by an opaque UUID
The system SHALL assign every user a UUID at creation time and MUST use that UUID as the only user identifier exposed in API responses. The internal auto-increment primary key MUST NOT appear in any API response.

#### Scenario: Fetching the authenticated user's profile
- **WHEN** an authenticated client requests `GET /api/v1/auth/me`
- **THEN** the response's `id` field is a UUID string and the internal integer primary key is not present anywhere in the response

#### Scenario: Registering a new account
- **WHEN** a new user account is created via `POST /api/v1/auth/register`
- **THEN** the system generates a unique UUID for the account and returns that UUID as the user's `id` in the response

### Requirement: Authenticated user endpoints return only essential fields
The system SHALL return only the minimal set of user fields required by clients (`id`, `name`, `email`, `email_verified`, `locale`, `profile_photo_url`, `tutorial_progress`) from any endpoint that serializes the authenticated user, and MUST NOT include internal or unused bookkeeping fields (e.g. password, remember token, internal storage paths, timestamps, unused plan/subscription bookkeeping fields).

#### Scenario: Response shape is minimal
- **WHEN** any endpoint that returns the authenticated user's data responds (`/auth/register`, `/auth/login`, `/auth/me`, `/users/me`, `/users/me/preferences`, `/users/me/tutorial`)
- **THEN** the response contains exactly the essential user fields and no other user attributes
