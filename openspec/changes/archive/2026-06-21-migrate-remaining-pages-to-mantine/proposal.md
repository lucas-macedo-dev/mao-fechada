## Why

The Categorias page was successfully migrated to Mantine and deployed to production, proving the migration strategy works. The remaining pages (Home, Transactions, Login, Register, Profile, Subscription) still rely on custom CSS files and need to be brought into the Mantine theme for visual consistency and reduced maintenance overhead.

## What Changes

- Migrate `HomePage.tsx` from `pages.css` to Mantine components and shared building blocks.
- Migrate `TransactionsPage.tsx` from `pages.css` to Mantine components and shared building blocks.
- Migrate `LoginPage.tsx` and `RegisterPage.tsx` from `auth.css` to Mantine components.
- Migrate `ProfilePage.tsx` and `SubscriptionPage.tsx` from their current custom CSS to Mantine components.
- Remove CSS selectors from `pages.css` and `auth.css` that are fully replaced page by page.
- Preserve all business logic, API contracts, routing, and i18n keys — this is a styling-only migration.

## Capabilities

### New Capabilities
- None.

### Modified Capabilities
- `incremental-page-styling-migration`: Extend migration scope to cover the remaining pages (Home, Transactions, Login, Register, Profile, Subscription) now that the Categorias prerequisite step is complete and deployed.

## Impact

- Affected area: `web/` only.
- Files touched: `HomePage.tsx`, `TransactionsPage.tsx`, `LoginPage.tsx`, `RegisterPage.tsx`, `ProfilePage.tsx`, `SubscriptionPage.tsx`, `pages.css`, `auth.css`.
- Existing reusable components (`PageContainer`, `SectionCard`, `FormRow`, `ActionBar`) and the shared theme from the previous migration are already in place and will be reused.
- No backend, API schema, or i18n changes.
