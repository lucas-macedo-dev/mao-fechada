## Context

The system is a Laravel 13 REST API + React/Vite SPA for personal finance management. It handles real user financial data and is migrating toward paid subscriptions via MercadoPago. Currently, the only logging call is a single `Log::channel('stack')->info` in `MercadoPagoWebhookController` — everything else is silent. File-based Laravel logs (`storage/logs/laravel.log`) exist but are unstructured, non-queryable, and not monitored. Debugging production issues requires either SSH + grep or guesswork.

LGPD (Brazil's data protection law) imposes strict restrictions: personal data (name, email, CPF, IP address, passwords, financial amounts tied to individuals) must not appear in logs unless legally justified and disclosed in the privacy policy. Since there is no existing log privacy policy and no DPO-approved purpose, the safest approach is to store **no personal data** in logs — only internal user IDs and non-identifying event metadata.

## Goals / Non-Goals

**Goals:**
- Introduce a queryable `activity_logs` database table for key domain events.
- Implement an `ActivityLogger` service that enforces a sanitization contract before writing any log entry.
- Log the minimum set of events needed to debug the most common production problems: auth failures, billing events, webhook processing, and unhandled exceptions.
- Ensure all log entries are LGPD-compliant by design: no IP, no email, no password, no payment tokens, no personal financial details.
- Replace the existing ad-hoc `Log::info` in `MercadoPagoWebhookController` with the new service.

**Non-Goals:**
- Full audit trail of every CRUD operation on transactions/budgets/categories (high volume, low debug value — can be added later).
- Real-time monitoring dashboard or alerting (out of scope; this change is storage/collection only).
- Retention policy enforcement or automatic log purging (future work).
- Frontend logging or client-side error tracking.
- Replacing Laravel's file logging for framework/infrastructure errors (keep both; DB logs for domain events, files for system errors).

## Decisions

### Decision 1: Database table over file-only logging for domain events

**Chosen**: A dedicated `activity_logs` table alongside the existing file logs.

**Why**: File logs cannot be queried without SSH access. A DB table enables `WHERE event_type = 'login_failure' AND user_id = ?` queries, making debugging feasible without server access. File logs are retained for infrastructure-level errors (Laravel exceptions, queue failures) where DB writes may not be possible.

**Alternative considered**: Structured JSON file logs (e.g., with a log aggregator). Rejected because the project has no log aggregation infrastructure and this adds operational complexity with no current benefit.

### Decision 2: ActivityLogger service with an explicit blocklist

**Chosen**: A dedicated `ActivityLogger` class with a typed `log(string $event, ?int $userId, array $metadata)` method. The service applies a `sanitize()` step that removes any key matching a blocklist (`password`, `ip`, `email`, `name`, `token`, `secret`, `cpf`, `card`, `cvv`).

**Why**: Sanitization must be enforced at the point of writing, not at the callsite. A shared service with a single sanitization function is the only way to guarantee compliance regardless of who adds a new log call in the future.

**Alternative considered**: Each callsite manually omits sensitive fields. Rejected — this is error-prone and fails silently.

### Decision 3: Synchronous writes, no queue

**Chosen**: Write log entries synchronously within the request lifecycle.

**Why**: Auth and billing events are low-frequency, and losing a log entry due to queue failure is worse than adding a few milliseconds of latency. Queued logging adds complexity (failed jobs, retry storms) with no benefit at this scale.

**Alternative considered**: Queued async logging. Deferred — can be added later if log volume grows.

### Decision 4: Minimal event set (auth + billing + unhandled exceptions)

**Chosen**: Log only these event types:
- `auth.login_success`, `auth.login_failure`, `auth.register`, `auth.logout`
- `auth.password_reset_requested`, `auth.email_verified`
- `billing.webhook_received`, `billing.webhook_processed`, `billing.webhook_failed`
- `system.exception` (unhandled exceptions via the global exception handler)

**Why**: These are the events most likely to need debugging in production. High-volume CRUD events (transactions, categories) would bloat the table without proportional debug value.

### Decision 5: No IP address storage (LGPD compliance)

**Chosen**: The `activity_logs` table has no `ip_address` column. The `ActivityLogger` blocklist explicitly strips any `ip` or `ip_address` key from metadata.

**Why**: Storing IP addresses constitutes processing of personal data under LGPD Art. 5. Without a documented legal basis and purpose, storage is non-compliant. Omitting IPs avoids this entirely with negligible impact on debuggability.

## Risks / Trade-offs

- **[Risk] Log entries are lost if the DB is unavailable** → Mitigation: wrap `ActivityLogger::log()` in a try/catch that silently falls back to a `Log::error()` file entry — never let a logging failure break the request.
- **[Risk] Future developers bypass the service and call `Log::info` with raw request data** → Mitigation: add a code review checklist item; the service is the enforced convention.
- **[Risk] `metadata` JSON column grows unbounded for exception logs** → Mitigation: truncate stack traces to the first 2000 characters before storing.
- **[Risk] The `activity_logs` table grows indefinitely** → Known limitation; a retention/pruning policy is out of scope for this change and should be a follow-up.

## Migration Plan

1. Add migration: `create_activity_logs_table` (columns: `id`, `event_type`, `user_id` nullable FK, `metadata` JSON, `created_at`).
2. Create `ActivityLog` Eloquent model.
3. Create `ActivityLogger` service with `log()` and `sanitize()` methods.
4. Update `AuthController` to call `ActivityLogger` on login success/failure, register, logout.
5. Update `EmailVerificationController` and `PasswordResetController` for auth.* events.
6. Refactor `MercadoPagoWebhookController` to replace ad-hoc `Log::info` with `ActivityLogger`.
7. Register a global exception handler hook to write `system.exception` entries.
8. Register `ActivityLogger` in the service container (`AppServiceProvider`).

**Rollback**: Drop the `activity_logs` table and revert the controller changes. No existing functionality depends on the new table.

## Open Questions

- Should `user_id` be included for `auth.login_failure` events where authentication fails (i.e., the user exists but credentials are wrong)? **Proposed answer**: Yes — resolve the user_id from the attempted email if a matching user exists. Log the ID, never the email, to avoid PII exposure while still enabling correlation.
- Should `system.exception` logs include the request path? **Proposed answer**: Yes — the HTTP path (e.g., `/api/v1/transactions`) is non-personal and critical for triage. Query parameters must be stripped.
