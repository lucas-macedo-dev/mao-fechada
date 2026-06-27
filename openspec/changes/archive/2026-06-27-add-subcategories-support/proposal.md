## Why

Categories already have a `parent_id` column and the backend fully enforces a 2-level hierarchy (root → child), but the frontend has no UI to create or assign subcategories. Additionally, deleting any category that has linked transactions throws a raw MySQL FK constraint error to the user — the system needs graceful handling that reassigns orphaned transactions to a fallback "Sem Categoria / No Category" catch-all.

## What Changes

- Add a **parent category selector** to the category creation form so users can optionally create a subcategory under an existing root category.
- Add **parent category editing** to the edit inline form so users can reassign or detach a subcategory.
- Update the `Category` TypeScript type to include `parent_id`, `parent`, and `children` fields that the API already returns.
- Fix **category deletion with transactions**: instead of crashing with a MySQL FK error, the backend finds or creates a "Sem Categoria / No Category" fallback category (matching the deleted category's type) for the user, reassigns all affected transactions to it, and then deletes the original category.
- Show a **confirmation alert** on the frontend before deletion when the category has transactions, informing the user that transactions will be moved to "Sem Categoria".
- Add **"Sem Categoria" (expense) and "Sem Categoria" (income)** to the default categories auto-seeded for new users.

## Capabilities

### New Capabilities
- `subcategory-management`: UI for creating subcategories under a parent category and editing/reassigning a category's parent, within the 2-level limit enforced by the backend.

### Modified Capabilities
- `default-category-seeding`: Add "Sem Categoria" (expense) and "Sem Categoria" (income) to the preset categories seeded for new users. Also add the corresponding English "No Category" variants.

## Impact

- **Backend**: `CategoryController::destroy()` (reassign transactions before delete), `DefaultCategorySeeder` (add fallback categories to seed list), language files `messages.php` (new error/info strings).
- **Frontend**: `web/src/api/types.ts` (Category type), `web/src/pages/CategoriesPage.tsx` (create form, edit form, deletion confirmation).
- **No breaking changes**: `parent_id` is optional; existing flat categories and transactions are unaffected.
