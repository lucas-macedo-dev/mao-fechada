## Context

`CATEGORY_ICON_OPTIONS` (`web/src/constants/categoryIcons.ts`) is a static array of `{ key, className }` consumed by `CategoriesPage.tsx`'s icon grid and by `getCategoryIconClass()` for rendering. Translation labels live inline in `web/src/i18n/config.ts` as flat `categories.icon.<key>` keys per locale (pt-BR, en). There is no backend allow-list — `icon` is stored as a free-text string (`max:100`) and validated only client-side against this array.

## Goals / Non-Goals

**Goals:**
- Expand icon coverage without touching how icons are selected, stored, or rendered.
- Keep every option translated in both locales — no key may be added to one locale and forgotten in the other.

**Non-Goals:**
- No icon search/filter UI for the (now larger) picker grid — the existing grid layout is left as-is.
- No backend validation of `icon` against the allow-list — unchanged from today.

## Decisions

- **Append new entries to `CATEGORY_ICON_OPTIONS` rather than reorganizing existing ones**, and never change or reuse an existing `key`/`className` pair, since `icon` values are already persisted on existing categories (`getCategoryIconClass` falls back to the default tag icon for anything not in the array — renaming or removing an in-use value would silently reset users' existing categories to the tag icon).
- **New icon keys use the same `categories.icon.<key>` naming convention** and are added to both the pt-BR and en blocks in `web/src/i18n/config.ts` in the same commit, so `i18next` never falls back to a raw key string.

## Risks / Trade-offs

- [A new `className` value could be added without a matching translation key in one locale, showing the raw i18n key in the UI] → Mitigated by adding both locale entries together in this change and eyeballing the rendered picker in both locales before merging (no automated i18n-completeness check exists in this repo currently).
