## Context

The category hierarchy is fully implemented on the backend: `parent_id` column with a self-referencing FK (`nullOnDelete`), `parent()` / `children()` Eloquent relationships, `scopeRootsWithChildren`, and request validation that enforces exactly 2 levels (a child's parent must be a root, same type, same owner). The `services/api.ts` and `hooks/api.ts` on the frontend already pass `parent_id` to the API.

Two gaps exist:
1. **Frontend UX**: The create form has no parent selector, the edit form has no parent field, and the `Category` TypeScript type doesn't declare `parent_id`, `parent`, or `children`.
2. **Deletion crash**: `category_id` on `transactions` is NOT NULL with `restrictOnDelete`, so deleting a category that has transactions throws a raw MySQL FK constraint error surfaced directly to the user.

## Goals / Non-Goals

**Goals:**
- Add an optional parent category selector to the category creation form.
- Add parent category editing to the inline edit form (assign, change, or detach parent).
- Extend the `Category` type to include `parent_id`, `parent?`, and `children?`.
- Fix category deletion: reassign transactions to a per-type fallback "Sem Categoria / No Category" before deleting, with a user-facing confirmation.
- Seed the "Sem Categoria" fallback categories (expense and income) for all new users.

**Non-Goals:**
- 3+ nesting levels — the backend already rejects them.
- Drag-and-drop reordering or reparenting.
- Moving transactions when a category is reparented (only on deletion).
- Exposing subcategory nesting in any screen other than `CategoriesPage`.

## Decisions

### 1. Two fallback categories per user, one per type

When a category is deleted, its transactions must go somewhere. Because categories have a fixed `type` (income / expense), a single "Sem Categoria" category can't absorb both income and expense transactions without violating the type invariant.

Decision: maintain two fallback categories per user — `{ name: 'Sem Categoria', type: 'expense' }` and `{ name: 'Sem Categoria', type: 'income' }` (English: "No Category"). Both are seeded at registration.

The existing unique constraint is `(user_id, parent_id, name)`. MySQL treats NULLs as distinct in unique indexes, so two rows `(user_id=X, NULL, 'Sem Categoria')` with different types do **not** violate it. No migration needed for the unique constraint.

**Alternative considered:** one nullable `category_id` on transactions (FK `SET NULL`), showing "Sem Categoria" as a UI label for null. Rejected because it requires a DB migration, changes the transaction model contract, and every query/display needs a null guard.

### 2. Find-or-create fallback inside `CategoryController::destroy()`

Before deleting, the controller calls a `FallbackCategoryResolver::findOrCreate(User, string $type): Category` helper. This helper looks up `{name: 'Sem Categoria'/'No Category', type: $type}` for the user's locale, creating it if absent, then reassigns `transactions.category_id` for all affected transactions in a single `UPDATE` query. The original category is then safely deleted.

The fallback name is locale-aware (same locale logic as `DefaultCategorySeeder`).

**Alternative considered:** handle inside a service class. Reasonable, but the logic is simple enough to warrant a small resolver; it avoids growing `CategoryController` with service injection.

### 3. Frontend confirmation on deletion with transactions

When a category has transactions, the frontend delete handler uses a custom modal (or a second `window.confirm`) to warn: "Esta categoria possui X transações. Ao deletar, elas serão movidas para 'Sem Categoria'. Deseja continuar?" The transaction count is returned from the API in a new `DELETE /categories/{id}` response body when transactions exist, OR queried from cached data on the frontend.

Decision: return the count from the backend in a pre-flight `GET /categories/{id}` that already returns `children` — re-use the `show` endpoint which loads the category; the frontend counts `category.transactions_count` if included. Simpler: just always send the confirm and let the backend handle reassignment silently (trusting the backend message translated via `lang/messages.php`).

**Simplest path chosen:** backend does reassignment silently; the frontend always shows a single confirmation dialog that mentions "transactions will be moved to Sem Categoria" regardless of count, matching the user expectation without a pre-flight request.

### 4. Parent selector placement in the create form

Show an optional "Categoria pai" (`Select`) after the type field. Only root categories of the currently selected type are shown. When a parent is selected, the `type` field becomes read-only (inherits parent type) to prevent a confusing mismatch error from the API.

Clearing the parent selector restores the type field to editable.

### 5. Parent editing in the edit form

Add the same optional parent selector to the inline edit form with a "Nenhuma / Nenhum pai" (root) option to detach. The selector is filtered to root categories of the same type as the category being edited.

## Risks / Trade-offs

- **Fallback category appears in category list** → "Sem Categoria" will show up in dropdowns for transaction creation. This is acceptable (users may intentionally assign transactions there). It can be visually distinguished in a follow-up.
- **Type constraint when reassigning child category type** → already enforced by existing controller; no new risk.
- **Parent selector filtered by type** → if the user changes the `type` field after selecting a parent, the parent is automatically cleared (frontend state). This edge case must be handled in the `onChange` handler of the type select.
- **Existing users without fallback categories** → they won't have "Sem Categoria" until they hit a deletion. The find-or-create pattern in `FallbackCategoryResolver` handles this transparently.

## Migration Plan

1. **Backend**: Create `FallbackCategoryResolver` service; update `CategoryController::destroy()`; update `DefaultCategorySeeder` with fallback entries; add translation strings.
2. **Frontend**: Update `Category` type; update `CategoriesPage` create + edit forms; update delete confirmation message.
3. No DB schema changes required.
4. Existing users are unaffected until they delete a category — fallback is created on demand.
5. Rollback: revert the four files (controller, seeder, types, page).

## Open Questions

- Should "Sem Categoria" be locked/non-deletable to avoid re-exposing the FK error? Decision deferred to a follow-up; the find-or-create pattern recreates it on next deletion if absent.
