## Why

The app currently allows sign-up without email verification and has no way to recover a lost password, which are baseline security expectations for any user-facing product. Additionally, a Mercado Pago payment integration is needed to support future subscription plans (flagged in `account-and-access` spec), but the infrastructure must be in place before it can be required.

## What Changes

- Add email verification flow: after sign-up users receive a verification email and must confirm before accessing the app
- Add password reset flow: users can request a reset link via email and set a new password
- Introduce Mercado Pago integration scaffold: SDK setup, webhook handler stub, and payment method UI shell — no payment will be enforced yet

## Capabilities

### New Capabilities
- `email-verification`: Verification token generation, email dispatch, and confirmation endpoint; blocks unverified users from accessing protected routes
- `password-reset`: Forgot-password request (sends email), token validation, and new-password submission endpoints with matching UI screens
- `mercadopago-payment`: SDK/client initialization, webhook ingestion endpoint (stub), and a Payment Methods settings page skeleton with no active enforcement

### Modified Capabilities
- `account-and-access`: Users table gains `email_verified_at` and `password_reset_token` / `password_reset_expires_at` columns; authentication guard checks email verification status

## Impact

- **Backend (api/)**: new auth endpoints (`/auth/verify-email`, `/auth/forgot-password`, `/auth/reset-password`), Mercado Pago webhook route, email-sending service, DB migrations for new user columns
- **Frontend (web/)**: VerifyEmail page, ForgotPassword page, ResetPassword page, PaymentMethods settings page (skeleton)
- **Dependencies**: email transport (SMTP / transactional email service), Mercado Pago Node SDK
- **No breaking changes** — existing authenticated sessions remain valid; email verification only gates new or unverified accounts
