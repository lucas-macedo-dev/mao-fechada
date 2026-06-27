## 1. Backend — Fallback Category Service

- [x] 1.1 Create `app/Services/FallbackCategoryResolver.php` with a `findOrCreate(User $user, string $type): Category` method that looks up or creates the locale-aware "Sem Categoria" / "No Category" root category for the given type
- [x] 1.2 Update `CategoryController::destroy()` to call `FallbackCategoryResolver::findOrCreate()`, reassign all `transactions.category_id` pointing to the deleted category, then delete it — wrapped in a DB transaction
- [x] 1.3 Add translation strings to `lang/pt-BR/messages.php` and `lang/en/messages.php`: `category_fallback_name` ("Sem Categoria" / "No Category") and `category_delete_with_transactions` (confirmation message shown on the frontend)

## 2. Backend — Default Category Seeding

- [x] 2.1 Add `{ name: 'Sem Categoria', type: 'expense' }` and `{ name: 'Sem Categoria', type: 'income' }` to the `pt-BR` array in `DefaultCategorySeeder::categories()`
- [x] 2.2 Add `{ name: 'No Category', type: 'expense' }` and `{ name: 'No Category', type: 'income' }` to the English/default array in `DefaultCategorySeeder::categories()`
- [x] 2.3 Update `DefaultCategorySeederTest` and `DefaultCategorySeederFeatureTest` to assert the new fallback categories are seeded (6 expense + 4 income per locale)

## 3. Frontend — TypeScript Types

- [x] 3.1 Extend the `Category` type in `web/src/api/types.ts` to add `parent_id: number | null`, `icon: string | null`, `parent?: Category | null`, and `children?: Category[]`
- [x] 3.2 Update `createCategory` signature in `web/src/api/finance.ts` to accept `icon?: string` and `parent_id?: number | null` (currently typed without these fields)

## 4. Frontend — Category Create Form

- [x] 4.1 Add a `parentId` state variable and an optional `Select` for "Categoria pai / Parent category" to the create form in `CategoriesPage.tsx`, populated with root categories matching the currently selected type
- [x] 4.2 When a parent is selected, make the type `Select` read-only and set its value to the parent's type; when the parent is cleared, restore the type `Select` to editable
- [x] 4.3 When the type changes in the create form, clear `parentId` state (prevents stale parent from mismatched type being submitted)
- [x] 4.4 Include `parent_id` in the `createMutation.mutateAsync()` payload

## 5. Frontend — Category Edit Form

- [x] 5.1 Add a `editParentId` state variable and an optional `Select` for "Categoria pai" to the inline edit form, populated with root categories of the same type as the category being edited (excluding itself)
- [x] 5.2 Add a "Nenhuma (raiz)" / "None (root)" option as the first item in the parent selector to allow detaching
- [x] 5.3 Initialize `editParentId` from `category.parent_id` in `handleStartEdit()`
- [x] 5.4 Include `parent_id` (or `null` for detach) in the `updateMutation.mutateAsync()` payload

## 6. Frontend — Deletion Confirmation

- [x] 6.1 Replace the current `window.confirm` in `handleDelete()` with a message that explicitly states transactions will be moved to "Sem Categoria / No Category"
