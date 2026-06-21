## Context

The Mantine foundation is already in place: `MantineProvider` wraps the app root, the shared theme is defined in `web/src/styles/theme.ts`, and four reusable building blocks (`PageContainer`, `SectionCard`, `FormRow`, `ActionBar`) exist in `web/src/components/ui/`. The Categorias page was successfully migrated and deployed, validating the approach.

The remaining pages that still use custom CSS are:
- `HomePage.tsx` — uses `pages.css`
- `TransactionsPage.tsx` — uses `pages.css`
- `LoginPage.tsx` — uses `auth.css`
- `RegisterPage.tsx` — uses `auth.css`
- `ProfilePage.tsx` — uses custom CSS
- `SubscriptionPage.tsx` — uses custom CSS

## Goals / Non-Goals

**Goals:**
- Migrate each remaining page to Mantine using the same pattern proven in CategoriesPage.
- Reuse existing building blocks; add new ones only if a clear gap exists.
- Remove fully replaced CSS selectors from `pages.css` and `auth.css` as each page is done.
- Keep each page independently deployable after its migration.

**Non-Goals:**
- Redesigning page UX, layouts, or visual branding beyond equivalent restyling.
- Touching backend, API contracts, routing, or i18n keys.
- Introducing new generic abstractions or advanced TypeScript patterns.
- Migrating the app shell (nav, header, footer) if not already targeted.

## Decisions

1. **Reuse existing building blocks without creating a new abstraction layer.**  
   Rationale: `PageContainer`, `SectionCard`, `FormRow`, and `ActionBar` already cover the patterns seen in CategoriesPage. If a remaining page needs a different primitive, add it as a new explicit component — don't generalize existing ones.

2. **Migrate pages in priority order: HomePage → TransactionsPage → LoginPage → RegisterPage → ProfilePage → SubscriptionPage.**  
   Rationale: Home and Transactions are the most-used authenticated pages and benefit most from visual consistency. Auth pages (Login, Register) are simpler and share a CSS file, so doing them together reduces leftover CSS cleanup. Profile and Subscription are lower-traffic.

3. **Remove replaced CSS selectors incrementally, page by page — not in a final batch.**  
   Rationale: removes dead code immediately after each migration, keeps CSS files accurate, and avoids a large unmaintainable cleanup task at the end.

4. **Auth pages (Login, Register) share `auth.css` — target both before deleting the file.**  
   Rationale: deleting `auth.css` is only safe once all pages that import it are migrated. Migrating both in sequence allows the file to be fully removed after RegisterPage.

## Risks / Trade-offs

- **[Risk] HomePage and TransactionsPage are high-traffic and more complex.** → **Mitigation:** validate builds and functional behavior (API calls, form interactions, routing) after each page migration before deploying.
- **[Risk] Residual global CSS from `pages.css` may still affect migrated Mantine components.** → **Mitigation:** remove page-specific selectors immediately after each migration, and test for visual regressions before deployment.
- **[Risk] New reusable components added ad-hoc may introduce inconsistency.** → **Mitigation:** only add a new building block if at least two pages share the same structure need; otherwise inline Mantine primitives directly.
