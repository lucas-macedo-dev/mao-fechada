## 1. Database Layer

- [x] 1.1 Create migration `create_activity_logs_table` with columns: `id` (bigIncrements), `event_type` (string), `user_id` (nullable unsignedBigInteger FK to users with onDelete set null), `metadata` (json, nullable), `created_at` (timestamp, no `updated_at`)
- [x] 1.2 Create `ActivityLog` Eloquent model (`app/Models/ActivityLog.php`) with `$timestamps = false`, `const UPDATED_AT = null`, fillable fields, and a `user()` belongsTo relation
- [x] 1.3 Run the migration and verify the table exists

## 2. ActivityLogger Service

- [x] 2.1 Create `app/Services/ActivityLogger.php` with a `log(string $event, ?int $userId, array $metadata = []): void` method
- [x] 2.2 Implement `sanitize(array $metadata): array` as a private method with the hardcoded blocklist: `password`, `ip`, `ip_address`, `email`, `name`, `token`, `secret`, `cpf`, `card`, `cvv`, `remember_token` — strip matching keys recursively (nested arrays too)
- [x] 2.3 Wrap the DB write in a try/catch; on failure, call `Log::error()` with the event type and exception message, then return without rethrowing
- [x] 2.4 Register `ActivityLogger` as a singleton in `AppServiceProvider::register()`

## 3. Auth Event Logging

- [x] 3.1 Inject `ActivityLogger` into `AuthController` and log `auth.register` (with `locale` in metadata) on successful registration
- [x] 3.2 Log `auth.login_success` (metadata: `{"provider": "sanctum"}`) on successful login
- [x] 3.3 Log `auth.login_failure` (no email/password in metadata; resolve user_id from email if user exists) on failed login
- [x] 3.4 Log `auth.logout` on token deletion in the logout method
- [x] 3.5 Log `auth.password_reset_requested` in `PasswordResetController` when the reset email is dispatched
- [x] 3.6 Log `auth.email_verified` in `EmailVerificationController` when the email is successfully verified

## 4. Billing & Webhook Event Logging

- [x] 4.1 Replace the existing `Log::channel('stack')->info(...)` call in `MercadoPagoWebhookController` with `ActivityLogger::log('billing.webhook_received', null, ['topic' => ..., 'id' => ...])`
- [x] 4.2 Log `billing.webhook_processed` after successful processing with the same topic/id metadata
- [x] 4.3 Add a try/catch around webhook processing logic and log `billing.webhook_failed` with `exception_class` and truncated `message` (max 500 chars) on failure

## 5. Global Exception Logging

- [x] 5.1 In `bootstrap/app.php` (or the registered exception handler), add a `withExceptions` callback that calls `ActivityLogger::log('system.exception', ...)` for any exception that results in a 5xx response
- [x] 5.2 Include in metadata: `exception_class`, `message` (truncated to 2000 chars), and `path` (request path without query string); never include the full stack trace

## 6. Verification

- [x] 6.1 Manually test login success and failure and confirm rows appear in `activity_logs` with no PII in `metadata`
- [x] 6.2 Confirm that passing a blocklisted key (e.g., `email`) to `ActivityLogger::log()` results in it being stripped from the stored metadata
- [x] 6.3 Confirm that a DB failure in `ActivityLogger` does not throw an exception to the caller (verify via unit test or by temporarily disabling the DB connection in a local test)
