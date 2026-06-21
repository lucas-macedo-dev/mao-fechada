## Context

The app uses Laravel (API) + React/Mantine (frontend). Auth uses Laravel Sanctum tokens stored in `localStorage`. The `AuthController` throws `ValidationException` on login failure, which Laravel formats as `{message: "...", errors: {field: ["..."]}}`. The frontend attempts to read `err?.response?.data?.error?.message` — a path that never exists in Laravel validation responses — so every error silently falls back to the generic i18n string. An `extractApiError` utility already exists in `services/api.ts` but is unused by the auth pages.

## Goals / Non-Goals

**Goals:**
- Surface descriptive, field-level error messages from the API in both auth forms
- Add client-side validation so common mistakes are caught before the network round-trip
- Add a password confirmation field to the register form
- Strengthen password policy on the backend (complexity beyond length)
- Rate-limit auth endpoints to mitigate brute-force attacks
- Make all error strings i18n-aware in both `pt-BR` and `en`

**Non-Goals:**
- Migrating token storage from `localStorage` to `httpOnly` cookies (large scope, separate change)
- Email verification flow
- Two-factor authentication
- OAuth / social login

## Decisions

### 1. Client-side validation with Mantine's `useForm` + `@mantine/form`

**Decision:** Use `@mantine/form` for form state and validation instead of raw `useState` fields.

**Rationale:** The project already uses Mantine components. `@mantine/form` provides built-in `error` prop support per field, matching Mantine's `TextInput`/`PasswordInput` API exactly — no extra library needed. Avoids managing a separate `errors` state object.

**Alternative considered:** Zod + `react-hook-form` — overkill for two simple forms; introduces two dependencies not already in the project.

### 2. Map Laravel validation error responses to per-field form errors

**Decision:** After a failed API call, parse `err.response.data.errors` (Laravel's field-keyed map) and call `form.setFieldError(field, message)` to attach errors directly to form fields.

**Rationale:** Laravel's `ValidationException` always returns `{message, errors: {field: [msg, ...]}}`. Reading `errors.email[0]`, `errors.password[0]`, etc. gives the user the exact failure reason (e.g., "The email has already been taken."). The existing `extractApiError` utility is designed for a different error shape (`{error: {type, message}}`); it will not be used for validation errors — instead, a local helper `parseValidationErrors` handles the `errors` map.

### 3. Rate limiting via Laravel's built-in `throttle` middleware

**Decision:** Apply `throttle:5,1` (5 requests per minute per IP) to `POST /v1/auth/login` and `POST /v1/auth/register`.

**Rationale:** Laravel ships `ThrottleRequests` middleware; zero additional dependencies. 5 req/min is tight enough to prevent automated attacks while allowing legitimate rapid retries during testing.

**Alternative considered:** A dedicated package like `spatie/laravel-rate-limited-job-middleware` — unnecessary, this is a route-level concern.

### 4. Password complexity rule: `min:8` + regex for at least one digit or special character

**Decision:** Add `'password' => ['required', 'string', 'min:8', 'regex:/[0-9\W]/']` on register.

**Rationale:** Forces at least one non-letter character with minimal user friction. The frontend shows a hint below the password field so users know the rule before submitting.

**Alternative considered:** Laravel's `Password::defaults()` rule object — more expressive but adds config boilerplate; the inline regex is simpler and self-contained here.

### 5. Password confirmation field in register form only

**Decision:** Add a `passwordConfirmation` field to `RegisterPage` with a client-side match check, but do **not** send it to the API.

**Rationale:** The API doesn't require `password_confirmation` (and adding server-side confirmation validation would be redundant given client-side check). Keeps the API contract unchanged.

## Risks / Trade-offs

- **Risk:** Translated Laravel error messages may appear in the server's configured locale, not the user's locale.  
  → Mitigation: The user's `locale` is set at registration and used by Laravel; for unauthenticated requests the browser `Accept-Language` header can be read, but this is out of scope. Errors on login will be in the server default (`en`) — acceptable for now.

- **Risk:** `throttle:5,1` may block legitimate users who refresh quickly.  
  → Mitigation: 5/min is per IP; shared IPs (offices, NAT) could be affected. The limit can be raised to `10,1` if complaints arise, or switched to per-user throttling post-auth.

- **Risk:** `@mantine/form` migration changes the form state pattern, requiring rewrite of both pages.  
  → Mitigation: Both `LoginPage` and `RegisterPage` are small (~100 lines each); full rewrite is low risk.

## Open Questions

- Should validation errors returned from the API be translated on the frontend using i18n keys (requiring a mapping table) or displayed as-is from the server? For now: display server messages as-is, since they are already in the user's locale for register, and acceptable in English for login.
