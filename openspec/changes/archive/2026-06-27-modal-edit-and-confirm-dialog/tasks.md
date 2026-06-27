## 1. Shared Components

- [x] 1.1 Create `web/src/components/FormModal.tsx` — Mantine `Modal` wrapper accepting `title`, `opened`, `onClose`, `children`
- [x] 1.2 Create `web/src/components/ConfirmDialog.tsx` — Mantine `Modal` with `opened`, `title`, `message`, `onConfirm`, `onCancel`, `loading` props

## 2. i18n Keys

- [x] 2.1 Add translation keys for modal titles (create/edit category, create/edit transaction) and confirm/cancel button labels to `web/src/i18n/config.ts` (pt-BR and en)

## 3. CategoriesPage — Form Modal

- [x] 3.1 Replace inline create form section with a "New category" button that sets `formModal` state to `{ mode: 'create' }`
- [x] 3.2 Replace inline edit form expansion (category rows and subcategory rows) with edit buttons that set `formModal` state to `{ mode: 'edit', item: category }`
- [x] 3.3 Render a single `FormModal` instance in `CategoriesPage` containing the category form; form content (fields, defaults) switches based on `formModal.mode`
- [x] 3.4 On successful create or save, close the modal and invalidate category query cache

## 4. CategoriesPage — Confirm Dialog

- [x] 4.1 Replace `window.confirm` in `handleDeleteCategory` with a `ConfirmDialog` driven by local `confirmState`
- [x] 4.2 Wire `onConfirm` to execute the delete mutation and `onCancel` to clear `confirmState`

## 5. TransactionsPage — Form Modal

- [x] 5.1 Replace the inline create transaction section with a "New transaction" button that sets `formModal` state to `{ mode: 'create' }`
- [x] 5.2 Replace inline edit form expansion in transaction rows with edit buttons that set `formModal` state to `{ mode: 'edit', item: transaction }`
- [x] 5.3 Render a single `FormModal` instance in `TransactionsPage` containing the transaction form; form content switches based on `formModal.mode`
- [x] 5.4 On successful create or save, close the modal and invalidate transaction query cache

## 6. TransactionsPage — Confirm Dialog

- [x] 6.1 Replace `window.confirm` in `handleUpdate` (installment cascade confirm) with a `ConfirmDialog`
- [x] 6.2 Replace `window.confirm` in `handleDelete` with a `ConfirmDialog`
- [x] 6.3 Wire `onConfirm` to execute the respective mutation and `onCancel` to clear `confirmState`

## 7. Cleanup

- [x] 7.1 Remove unused inline form state variables (`editingCategoryId`, `editingTransactionId`, and create-section expansion flags) from both pages
- [x] 7.2 Verify no `window.confirm` references remain in the frontend codebase
