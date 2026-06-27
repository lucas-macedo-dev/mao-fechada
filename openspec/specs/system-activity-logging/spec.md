## Purpose

The system SHALL provide a structured, LGPD-compliant activity logging capability that persists domain events to the database through a single, controlled interface. All sensitive personal data is stripped before storage, and logging failures are isolated so they never break the original request.

---

## Requirements

### Requirement: Activity log entries are stored in the database
The system SHALL persist domain event log entries in an `activity_logs` database table containing: `id`, `event_type` (string), `user_id` (nullable integer FK to users), `metadata` (JSON), and `created_at` (timestamp). There SHALL be no `updated_at` column — log entries are immutable once written.

#### Scenario: Auth event creates a log entry
- **WHEN** a user successfully logs in
- **THEN** a new row is written to `activity_logs` with `event_type = 'auth.login_success'`, the resolved `user_id`, and a `metadata` JSON object (e.g., `{"provider": "sanctum"}`)

#### Scenario: Log entry contains no sensitive personal data
- **WHEN** any log entry is written
- **THEN** the `metadata` JSON MUST NOT contain any of the following keys: `password`, `ip`, `ip_address`, `email`, `name`, `token`, `secret`, `cpf`, `card`, `cvv`, `remember_token`

---

### Requirement: ActivityLogger service is the sole log-writing interface
The system SHALL expose a single `ActivityLogger` service class with a `log(string $event, ?int $userId, array $metadata = [])` method as the only authorized way to write domain events to `activity_logs`. Direct `Log::info` / `DB::table('activity_logs')->insert()` calls for domain events are prohibited.

#### Scenario: Metadata is sanitized before persistence
- **WHEN** `ActivityLogger::log()` is called with a `metadata` array that contains a blocked key (e.g., `'password'`)
- **THEN** the blocked key is removed from the stored metadata and the log entry is written without it

#### Scenario: DB failure does not break the request
- **WHEN** the database is unavailable and `ActivityLogger::log()` is called
- **THEN** the exception is caught, a fallback entry is written to the Laravel file log via `Log::error()`, and the original request continues normally without throwing

---

### Requirement: Authentication events are logged
The system SHALL log the following authentication events via `ActivityLogger`:
- `auth.login_success` — on successful credential validation
- `auth.login_failure` — on failed credential validation (wrong password or user not found)
- `auth.register` — on new user registration
- `auth.logout` — on token deletion
- `auth.password_reset_requested` — when a password reset email is triggered
- `auth.email_verified` — when a user's email is marked as verified

#### Scenario: Login failure logs without exposing email
- **WHEN** a login attempt fails because the password is incorrect
- **THEN** an `auth.login_failure` entry is written with `user_id` set to the ID of the matching user (if one exists), and `metadata` MUST NOT include the attempted email or password

#### Scenario: Register logs new user without PII
- **WHEN** a new user registers
- **THEN** an `auth.register` entry is written with the new user's `user_id` and `metadata` containing only non-identifying fields (e.g., `{"locale": "pt-BR"}`)

---

### Requirement: Billing and webhook events are logged
The system SHALL log the following MercadoPago billing events via `ActivityLogger`:
- `billing.webhook_received` — when the webhook endpoint receives a request
- `billing.webhook_processed` — when the webhook payload is successfully handled
- `billing.webhook_failed` — when processing the webhook throws an exception

#### Scenario: Webhook received is logged with topic type only
- **WHEN** the MercadoPago webhook endpoint receives a request
- **THEN** a `billing.webhook_received` entry is written with `user_id = null` and `metadata` containing the webhook `topic` and `id` fields only (no payment tokens, no card data)

#### Scenario: Webhook failure is logged with error context
- **WHEN** processing the webhook throws an exception
- **THEN** a `billing.webhook_failed` entry is written with `metadata` containing the exception class and a truncated message (max 500 characters), and no stack trace

---

### Requirement: Unhandled exceptions are logged
The system SHALL register a global exception handler hook that writes a `system.exception` entry to `activity_logs` for any unhandled exception that produces a 5xx HTTP response.

#### Scenario: Unhandled exception creates a log entry
- **WHEN** an unhandled exception escapes the request lifecycle and results in a 500 response
- **THEN** a `system.exception` entry is written with `user_id` set to the authenticated user's ID (if available, otherwise null), and `metadata` containing: `exception_class`, `message` (truncated to 2000 chars), and `path` (HTTP path without query string)

#### Scenario: Stack trace is never stored in the database
- **WHEN** a `system.exception` entry is written
- **THEN** the `metadata` JSON MUST NOT contain a full stack trace

---

### Requirement: LGPD compliance is enforced by design
The `ActivityLogger` service SHALL maintain a hardcoded blocklist of metadata keys that are always stripped before writing. The blocklist SHALL include at minimum: `password`, `ip`, `ip_address`, `email`, `name`, `token`, `secret`, `cpf`, `card`, `cvv`, `remember_token`. This blocklist cannot be overridden at the callsite.

#### Scenario: Blocklisted keys are stripped even if passed explicitly
- **WHEN** a caller passes `['email' => 'user@example.com', 'event_id' => 42]` as metadata
- **THEN** the stored metadata is `{"event_id": 42}` — the `email` key is silently removed

#### Scenario: Nested blocklisted keys are also stripped
- **WHEN** metadata contains a nested structure with a blocklisted key (e.g., `['user' => ['email' => '...', 'id' => 5]]`)
- **THEN** the nested `email` key is removed and the sanitized metadata is `{"user": {"id": 5}}`
