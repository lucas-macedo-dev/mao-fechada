## 1. Backend - Password & Rate Limiting

- [x] 1.1 Add regex complexity rule to `password` validation in `AuthController::register` (require at least one digit or special character alongside existing `min:8`)
- [x] 1.2 Apply `throttle:5,1` middleware to `POST /v1/auth/login` route in `api/routes/api.php`
- [x] 1.3 Apply `throttle:5,1` middleware to `POST /v1/auth/register` route in `api/routes/api.php`

## 2. Frontend - i18n Keys

- [x] 2.1 Add missing i18n keys to `pt-BR` in `web/src/i18n/config.ts`: `auth.register_failed`, `auth.field_required`, `auth.email_invalid`, `auth.password_too_short`, `auth.password_complexity`, `auth.password_mismatch`, `auth.password_hint`
- [x] 2.2 Add the same keys to the `en` translation in `web/src/i18n/config.ts`

## 3. Frontend - Login Form

- [x] 3.1 Replace raw `useState` fields in `LoginPage` with `useForm` from `@mantine/form`, defining `initialValues` and a `validate` object (email format, password min 8)
- [x] 3.2 Update `TextInput` and `PasswordInput` in `LoginPage` to use `form.getInputProps('email')` / `form.getInputProps('password')` so field errors render inline
- [x] 3.3 Update `handleSubmit` in `LoginPage` to call `form.validate()` first; if invalid, return early without calling the API
- [x] 3.4 In the `catch` block of `LoginPage::handleSubmit`, parse `err.response?.data?.errors` (Laravel validation shape) and call `form.setFieldError` per field; fall back to the `auth.failed` i18n Alert only for non-validation errors
- [x] 3.5 Remove the top-level `error` state and the bottom `Alert` that was driven by it; validation errors now live on form fields (non-validation errors still use an Alert)

## 4. Frontend - Register Form

- [x] 4.1 Replace raw `useState` fields in `RegisterPage` with `useForm` from `@mantine/form`, adding a `passwordConfirmation` field and a `validate` object (name required, email format, password min 8 + complexity hint, passwordConfirmation match)
- [x] 4.2 Add a `PasswordInput` for `passwordConfirmation` to the `RegisterPage` form UI, using `form.getInputProps('passwordConfirmation')`
- [x] 4.3 Add a password hint text below the password field in `RegisterPage` using the `auth.password_hint` i18n key (e.g., Mantine `Text` with `size="xs"` and `c="dimmed"`)
- [x] 4.4 Update `TextInput` and `PasswordInput` components in `RegisterPage` to use `form.getInputProps(...)` for inline error display
- [x] 4.5 Update `handleSubmit` in `RegisterPage` to call `form.validate()` first and return early if invalid; exclude `passwordConfirmation` from the API payload
- [x] 4.6 In the `catch` block of `RegisterPage::handleSubmit`, parse `err.response?.data?.errors` and call `form.setFieldError` per field; fall back to `auth.register_failed` i18n Alert for non-validation errors
- [x] 4.7 Remove the top-level `error` state and the generic `Alert` driven by it (replaced by field-level errors and the non-validation fallback Alert)

## 5. Verification

- [x] 5.1 Manually test login with wrong credentials and confirm the descriptive error appears below the email field
- [x] 5.2 Manually test register with a duplicate email and confirm the server error appears inline below the email field
- [x] 5.3 Manually test register with a weak password (e.g., "password") and confirm the API returns 422 and the error renders below the password field
- [x] 5.4 Manually test register with mismatched password confirmation and confirm the client-side error appears before the API is called
- [x] 5.5 Confirm all error strings display correctly in both `pt-BR` and `en` locales
