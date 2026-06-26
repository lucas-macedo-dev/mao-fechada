## Context

The API is a Laravel application using Sanctum for token-based auth. The `User` model already has `email_verified_at` cast to datetime and the `MustVerifyEmail` interface is present but commented out. The frontend is a React + TypeScript SPA. There is no current mechanism to send transactional emails or reset passwords. A future subscription flow (already scaffolded with `plan` and `subscription_status` columns) requires Mercado Pago, but payment enforcement is not in scope now.

## Goals / Non-Goals

**Goals:**
- Email verification gate: newly registered users must verify email before accessing protected routes
- Password reset via email: request link → validate token → submit new password
- Mercado Pago skeleton: SDK installed, webhook route registered (no-op), Payment Methods settings page shell in the frontend

**Non-Goals:**
- Enforcing payment or subscriptions via Mercado Pago
- Social login / OAuth
- Email template design (functional plain-text emails are sufficient)
- Queued email delivery (synchronous for now, can be made async later)

## Decisions

### 1. Use Laravel's built-in `MustVerifyEmail` + custom API notification
**Decision**: Uncomment the `MustVerifyEmail` interface on `User` and override `sendEmailVerificationNotification()` to dispatch a JSON-friendly verification URL instead of the default Blade email. The verification endpoint will be a signed route at `GET /v1/auth/email/verify/{id}/{hash}`.

**Why over Fortify**: Fortify adds significant overhead and is designed for full-stack Blade apps. A thin custom implementation keeps the API lean and avoids pulling in an opinionated package.

### 2. Custom password reset table + controller
**Decision**: Use Laravel's built-in `password_reset_tokens` table (already present in default migrations) and implement `POST /v1/auth/forgot-password` and `POST /v1/auth/reset-password` controllers manually, reusing `Illuminate\Auth\Passwords\PasswordBroker`.

**Why**: Avoids adding Fortify/Breeze. The password broker handles token hashing and expiry natively.

### 3. Email verification blocks protected routes via middleware
**Decision**: Add `verified` middleware (Laravel built-in) to the `auth:sanctum` group. Unverified users get a `403` with a clear error code (`email_not_verified`).

**Trade-off**: Existing test users without verified emails will be locked out. A migration or seeder fix will be needed for local dev.

### 4. Mercado Pago SDK installed but not enforced
**Decision**: Install `mercadopago/dx-php` (official SDK). Create a `MercadoPagoService` class that reads credentials from config. Register a `POST /v1/webhooks/mercadopago` route that logs the payload and returns `200` immediately (stub). Add a `PaymentMethods` page to the frontend settings that displays the skeleton UI.

**Why skeleton over nothing**: Sets up the wiring (config keys, service provider, route) so future work only needs to fill in logic, not restructure.

### 5. No new DB columns for password reset
The `password_reset_tokens` table (`email`, `token`, `created_at`) is created by Laravel's default migrations and should already exist. No new migration needed.

## Risks / Trade-offs

- **Verified middleware locks existing dev accounts** → Mitigation: document the fix (`php artisan tinker` to set `email_verified_at`) and seed a pre-verified test user.
- **Synchronous email sending can time out under load** → Acceptable for MVP; add queue later when email volume warrants it.
- **Mercado Pago webhook receives no signature validation yet** → The stub logs and acknowledges all payloads. This is intentional for the skeleton phase; signature validation is a task in the payment enforcement milestone.

## Migration Plan

1. Run new migration to ensure `password_reset_tokens` table exists (no-op if already present)
2. Deploy API changes (new routes are additive, no breaking changes)
3. Set `email_verified_at` for all existing users in the deployment script so they are not locked out
4. Deploy frontend changes
5. Enable MAIL_* env vars in production before going live with verification emails

## Open Questions

- Which transactional email provider? (Mailgun, SES, Resend) — must be set before prod deploy; local dev uses `MAIL_MAILER=log`
- Should unverified users be able to use the app in read-only mode, or fully blocked? (Current design: fully blocked)
