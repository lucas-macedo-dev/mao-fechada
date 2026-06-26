## 1. Database & Backend Setup

- [x] 1.1 Verify `password_reset_tokens` table exists; add migration if missing
- [x] 1.2 Add `email_verified_at` column migration if not already present on `users` table
- [x] 1.3 Uncomment `MustVerifyEmail` interface on the `User` model
- [x] 1.4 Add `email_verified_at` to `#[Fillable]` if not present

## 2. Email Verification – Backend

- [x] 2.1 Override `sendEmailVerificationNotification()` on `User` to send API-friendly signed URL (pointing to frontend, not Laravel route)
- [x] 2.2 Create `GET /v1/auth/email/verify/{id}/{hash}` route (signed) that marks the user verified
- [x] 2.3 Create `POST /v1/auth/email/resend` route (auth:sanctum) that resends the verification email
- [x] 2.4 Add `verified` middleware to the `auth:sanctum` route group, returning `403 email_not_verified` for unverified users
- [x] 2.5 Configure MAIL env vars in `.env.example` (MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS)

## 3. Password Reset – Backend

- [x] 3.1 Create `POST /v1/auth/forgot-password` controller action using Laravel's `PasswordBroker`
- [x] 3.2 Create `POST /v1/auth/reset-password` controller action to validate token and update password
- [x] 3.3 Customize the password reset email notification to use a frontend URL
- [x] 3.4 Apply `throttle:5,1` middleware to `forgot-password` route

## 4. Mercado Pago Skeleton – Backend

- [x] 4.1 Add `mercadopago/dx-php` to `composer.json` and run `composer require`
- [x] 4.2 Add `MERCADOPAGO_ACCESS_TOKEN` and `MERCADOPAGO_PUBLIC_KEY` to `config/services.php` and `.env.example`
- [x] 4.3 Create `MercadoPagoService` class that initializes the SDK client from config
- [x] 4.4 Register `POST /v1/webhooks/mercadopago` route (unauthenticated) that logs payload and returns `200`

## 5. Email Verification – Frontend

- [x] 5.1 Create `VerifyEmailPage` component shown after registration, prompting user to check their email
- [x] 5.2 Create `EmailVerificationCallback` route handler that reads the signed URL params and calls the verify endpoint
- [x] 5.3 Show success/error feedback after verification attempt
- [x] 5.4 Add a "Resend verification email" button on the verify email page
- [x] 5.5 Add route guard: redirect unverified users to the verify email page when API returns `403 email_not_verified`

## 6. Password Reset – Frontend

- [x] 6.1 Create `ForgotPasswordPage` with email input form and submission to `POST /v1/auth/forgot-password`
- [x] 6.2 Show confirmation message after successful request (do not reveal whether email exists)
- [x] 6.3 Create `ResetPasswordPage` that reads token/email from URL params, accepts new password and confirmation
- [x] 6.4 On successful reset, redirect user to login with a success toast
- [x] 6.5 Add "Forgot password?" link on the login page pointing to `ForgotPasswordPage`

## 7. Mercado Pago Skeleton – Frontend

- [x] 7.1 Create `PaymentMethodsPage` component under settings with a placeholder ("Payment options coming soon")
- [x] 7.2 Add "Payment Methods" entry to the settings navigation

## 8. Existing-User Compatibility

- [x] 8.1 Write a one-time migration or seeder helper that sets `email_verified_at = now()` for all existing users without a verified email (dev + staging)
- [x] 8.2 Document the manual fix command in `api/README.md` for local dev environments
