## 1. Mantine Setup

- [x] 1.1 Install Mantine core packages and dependencies in `web/package.json`
- [x] 1.2 Wrap the app root in `MantineProvider` in `web/src/main.tsx`

## 2. Theme Foundation

- [x] 2.1 Create `web/src/styles/theme.ts` with a shared Mantine theme object (colors, spacing, typography, radius, defaults)
- [x] 2.2 Pass the shared theme object to `MantineProvider`

## 3. Reusable Building Blocks

- [x] 3.1 Create `web/src/components/ui/PageContainer.tsx` — a simple page-level wrapper using Mantine primitives
- [x] 3.2 Create `web/src/components/ui/SectionCard.tsx` — a card/section wrapper using Mantine primitives
- [x] 3.3 Create `web/src/components/ui/FormRow.tsx` — a form row layout using Mantine primitives
- [x] 3.4 Create `web/src/components/ui/ActionBar.tsx` — an action bar using Mantine primitives

## 4. Categorias Page Migration

- [x] 4.1 Rewrite `web/src/pages/CategoriesPage.tsx` styling using Mantine components and reusable building blocks
- [x] 4.2 Pass FontAwesome icons through Mantine `leftSection`/`rightSection` slots where applicable in CategoriesPage
- [x] 4.3 Remove CSS selectors from `web/src/styles/pages.css` that are fully replaced by the Categorias migration

## 5. Validation

- [x] 5.1 Verify app builds without errors after Mantine setup (`npm run build`)
- [x] 5.2 Verify CategoriesPage renders correctly and business behavior (API calls, i18n, routing) is unchanged
