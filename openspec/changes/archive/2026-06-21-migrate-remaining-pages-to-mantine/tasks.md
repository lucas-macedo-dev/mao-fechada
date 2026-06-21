## 1. HomePage Migration

- [x] 1.1 Rewrite `web/src/pages/HomePage.tsx` styling using Mantine components and existing building blocks (`PageContainer`, `SectionCard`, etc.)
- [x] 1.2 Pass FontAwesome icons through Mantine `leftSection`/`rightSection` slots where applicable in HomePage
- [x] 1.3 Remove CSS selectors from `web/src/styles/pages.css` that are fully replaced by the HomePage migration
- [x] 1.4 Verify HomePage builds and all business behavior (API calls, month filter, i18n, routing) is unchanged

## 2. TransactionsPage Migration

- [x] 2.1 Rewrite `web/src/pages/TransactionsPage.tsx` styling using Mantine components and existing building blocks
- [x] 2.2 Pass FontAwesome icons through Mantine slots where applicable in TransactionsPage
- [x] 2.3 Remove CSS selectors from `web/src/styles/pages.css` that are fully replaced by the TransactionsPage migration
- [x] 2.4 Verify TransactionsPage builds and all business behavior (CRUD operations, filters, form interactions, i18n) is unchanged
- [x] 2.5 If `pages.css` is now fully replaced, delete the file and remove its import from any remaining files

## 3. LoginPage Migration

- [x] 3.1 Rewrite `web/src/pages/LoginPage.tsx` styling using Mantine components (form, inputs, button, logo area)
- [x] 3.2 Verify LoginPage builds and auth behavior (login flow, error display, redirect) is unchanged

## 4. RegisterPage Migration

- [x] 4.1 Rewrite `web/src/pages/RegisterPage.tsx` styling using Mantine components
- [x] 4.2 Verify RegisterPage builds and registration behavior (form validation, submission, redirect) is unchanged
- [x] 4.3 Remove fully replaced selectors from `web/src/styles/auth.css`; delete `auth.css` and its imports if no other pages use it

## 5. ProfilePage Migration

- [x] 5.1 Rewrite `web/src/pages/ProfilePage.tsx` styling using Mantine components and existing building blocks
- [x] 5.2 Verify ProfilePage builds and profile behavior (display, edit, API calls) is unchanged

## 6. SubscriptionPage Migration

- [x] 6.1 Rewrite `web/src/pages/SubscriptionPage.tsx` styling using Mantine components
- [x] 6.2 Verify SubscriptionPage builds and subscription behavior is unchanged

## 7. Final Validation

- [x] 7.1 Verify full app builds without errors (`npm run build` in `web/`)
- [x] 7.2 Verify no remaining imports of `pages.css` or `auth.css` exist in any page file
- [x] 7.3 Confirm all pages use shared theme tokens and no hardcoded per-page style values remain
