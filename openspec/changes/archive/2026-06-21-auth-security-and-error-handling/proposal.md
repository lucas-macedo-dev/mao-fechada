## Why

The login and register forms display generic, non-descriptive errors regardless of what went wrong (wrong password, duplicate email, weak password, etc.), because the frontend error extraction path (`error.message`) doesn't match the Laravel `ValidationException` response shape (`{message, errors}`). This makes it hard for users to understand what to fix, and the backend lacks brute-force protection on auth endpoints.

## What Changes

- Fix error extraction in login and register to correctly read Laravel validation error responses
- Add client-side field-level validation (email format, password minimum length) with errors shown inline per field before hitting the API
- Add password confirmation field to the register form to prevent accidental typos
- Strengthen backend password validation with complexity rules (min 8 chars, at least one number or special character)
- Add rate limiting to `/v1/auth/login` and `/v1/auth/register` endpoints to protect against brute-force attacks
- Replace hardcoded `'Registration failed'` English string in `RegisterPage` with i18n key
- Add missing i18n keys for new auth error messages in both `pt-BR` and `en`
- Use the existing `extractApiError` utility in auth pages instead of inline error extraction

## Capabilities

### New Capabilities

- `auth-form-validation`: Client-side validation and descriptive server-side error display for login and register forms, including per-field error messages, password confirmation, and i18n coverage for all auth errors.

### Modified Capabilities

<!-- No existing specs cover auth form behavior -->

## Impact

- `web/src/pages/LoginPage.tsx` — updated error handling and field validation
- `web/src/pages/RegisterPage.tsx` — password confirmation field, updated error handling and field validation
- `web/src/i18n/config.ts` — new auth error i18n keys (`pt-BR` and `en`)
- `api/app/Http/Controllers/Api/V1/AuthController.php` — stronger password validation rule on register
- `api/routes/api.php` — rate limiting middleware on auth routes
