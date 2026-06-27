## Why

The system currently has no structured logging strategy — only a single ad-hoc `Log::info` call in the MercadoPago webhook handler, with no log table or auditable trail for key system events. This makes debugging production issues, tracing billing problems, and auditing security-relevant actions extremely difficult. Implementing structured logging now is critical as the system grows toward paid subscriptions and real user financial data.

## What Changes

- Introduce a dedicated `activity_logs` database table to store structured, queryable log records for key domain events.
- Implement a `LogService` (or equivalent) responsible for writing sanitized log entries — stripping or hashing all LGPD-sensitive fields before persistence.
- Add automatic logging for high-value system events: auth actions, billing/subscription changes, MercadoPago webhook processing, and critical errors.
- Define a clear LGPD-compliant data policy for logs: no raw IPs, no passwords, no sensitive personal financial data, with user-id-only references.
- Replace the existing ad-hoc `Log::channel('stack')->info` call in `MercadoPagoWebhookController` with the new structured logging mechanism.

## Capabilities

### New Capabilities

- `system-activity-logging`: Structured, LGPD-compliant activity log system — database table, log service, sanitization rules, and logging of key domain events (auth, billing, webhooks, errors).

### Modified Capabilities

<!-- No existing spec-level requirements are changing — this is purely additive. -->

## Impact

- **Database**: New `activity_logs` migration; no changes to existing tables.
- **API (Laravel)**: New `LogService`, new `ActivityLog` model, updates to `AuthController`, `BillingController`, `MercadoPagoWebhookController`, and a global exception handler hook.
- **LGPD compliance**: All log entries must omit raw IPs, passwords, payment tokens, and full card data. User references use only internal UUIDs/IDs.
- **No frontend changes required.**
- **No breaking changes** to existing API contracts.
