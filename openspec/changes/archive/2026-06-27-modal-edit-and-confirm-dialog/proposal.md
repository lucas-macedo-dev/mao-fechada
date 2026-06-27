## Why

Create and edit forms currently appear inline — creation sections are expanded panels at the top/bottom of the page, edit forms expand inside list rows. This clutters layouts, causes unpredictable height shifts, and creates inconsistency between pages. Browser-native `window.confirm` dialogs cannot be themed, localized with styled buttons, or tested reliably in headless environments. Consolidating all create/edit interactions into modals and replacing confirms with a custom dialog brings visual consistency and a better UX across the whole app.

## What Changes

- All inline create forms (new category, new transaction) are moved into a `FormModal` that opens when the user clicks the "Add" / "New" button
- All inline edit forms (categories, subcategories, transactions) are moved into the same `FormModal` triggered by the edit button
- `window.confirm` calls in `CategoriesPage` and `TransactionsPage` are replaced by a reusable `ConfirmDialog` component built on Mantine's `Modal`
- Each page's state management is updated to track `formModal` state (mode: create|edit, item?) and `confirmState` instead of inline expansion flags

## Capabilities

### New Capabilities
- `form-modal`: Reusable modal wrapper that hosts any create or edit form. Triggered by an add or edit action; closes on save, cancel, or Escape.
- `confirm-dialog`: Reusable confirmation dialog component to replace `window.confirm`. Supports custom title, message, and confirm/cancel actions.

### Modified Capabilities
- `financial-ledger`: Transaction create and edit flows change from inline forms to modal.

## Impact

- **Files changed**: `web/src/pages/CategoriesPage.tsx`, `web/src/pages/TransactionsPage.tsx`
- **New components**: `web/src/components/FormModal.tsx`, `web/src/components/ConfirmDialog.tsx`
- **i18n**: New translation keys for dialog titles and confirm/cancel labels
- **No API changes**; purely frontend UI refactor
