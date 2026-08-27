## Why

`CATEGORY_ICON_OPTIONS` only offers 12 icons (tag, wallet, food, home, transport, health, education, shopping, salary, work, leisure, travel). Users creating custom categories for things like pets, utilities, subscriptions, kids, groceries, or investments are forced to pick an unrelated icon, which makes the category list harder to scan visually.

## What Changes

- Expand `CATEGORY_ICON_OPTIONS` in `web/src/constants/categoryIcons.ts` with a broader set of Font Awesome solid icons covering common personal-finance categories not currently represented (e.g. pets, utilities/bills, subscriptions/streaming, groceries, gifts, insurance, taxes, investments, children/family, personal care, donations, fuel/gas).
- Add matching `categories.icon.<key>` translation entries for both locales in `web/src/i18n/config.ts`.
- No change to how icons are selected or stored — `icon` remains a free-text className validated against the allow-list via `getCategoryIconClass`.

## Capabilities

### New Capabilities
- `category-icon-options`: defines the allow-listed set of icons users can pick when creating or editing a category, and how an unrecognized/legacy `icon` value falls back to the default.

### Modified Capabilities
(none — `default-category-seeding` seeds categories without icons and is unaffected)

## Impact

- Frontend: `web/src/constants/categoryIcons.ts` (icon option list), `web/src/i18n/config.ts` (new translation keys), `web/src/pages/CategoriesPage.tsx` (icon picker renders whatever is in `CATEGORY_ICON_OPTIONS`, no code change expected there).
- No backend or database changes — `icon` is already a free-text column validated client-side against the allow-list.
- No breaking changes — this only adds new valid icon values; existing categories keep their current icon.
