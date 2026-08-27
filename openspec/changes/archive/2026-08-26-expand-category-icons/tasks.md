## 1. Icon options

- [x] 1.1 Append new entries to `CATEGORY_ICON_OPTIONS` in `web/src/constants/categoryIcons.ts` covering: pets, utilities/bills, subscriptions, groceries, gifts, insurance, taxes, investments, family/children, personal care, donations, and fuel — reusing existing `categories.icon.<key>` naming, without touching any existing entry

## 2. Translations

- [x] 2.1 Add the corresponding `categories.icon.<key>` label for each new icon to the pt-BR translation block in `web/src/i18n/config.ts`
- [x] 2.2 Add the corresponding `categories.icon.<key>` label for each new icon to the en translation block in `web/src/i18n/config.ts`

## 3. Verification

- [x] 3.1 Run `npx tsc --noEmit` in `web/` to confirm no type errors
- [x] 3.2 Open the category create/edit form in both `pt-BR` and `en` locales and confirm every new icon renders with a translated label (no raw `categories.icon.*` key visible) and no console warnings from i18next
- [x] 3.3 Confirm an existing category with an old/legacy icon value still renders correctly (default tag fallback unaffected)
