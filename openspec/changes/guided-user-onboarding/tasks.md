## 1. Backend — Database & Model

- [x] 1.1 Create migration to add nullable `tutorial_progress` JSON column to `users` table
- [x] 1.2 Add `tutorial_progress` to the `$fillable` / casts on the `User` model (cast as `array`)
- [x] 1.3 Include `tutorial_progress` in the `/api/user/me` response

## 2. Backend — API Endpoint

- [x] 2.1 Create `PUT /api/user/tutorial` endpoint to update tutorial progress (accepts `step_id`, `completed`, and optional `dismissed` fields)
- [x] 2.2 Add route to `routes/api.php` behind `auth:sanctum` middleware
- [x] 2.3 Add request validation (step_id must be a known string, completed is boolean, dismissed is boolean)

## 3. Frontend — Tutorial State & API Client

- [x] 3.1 Add `tutorial_progress` field to the `User` type in `api/types.ts`
- [x] 3.2 Add `updateTutorialProgress(payload)` method to `financeApi` calling `PUT /api/user/tutorial`
- [x] 3.3 Create a `useTutorial` hook that reads tutorial state from the user object and exposes: `steps`, `activeStep`, `completeStep(id)`, `dismiss()`, `restart()`

## 4. Frontend — Tutorial Steps Config

- [x] 4.1 Create `src/tutorial/steps.ts` defining the 4 steps as typed config objects: `{ id, title, description, page, anchorSelector }`
- [x] 4.2 Step IDs: `create-category`, `record-transaction`, `view-summary`, `set-budget`

## 5. Frontend — TutorialChecklist Component

- [x] 5.1 Create `src/components/tutorial/TutorialChecklist.tsx` — a Mantine Drawer/aside component listing all steps with completion indicators
- [x] 5.2 Show steps in order with a checkmark for completed ones and a highlight for the active step
- [x] 5.3 Include a "Dismiss" button that calls `dismiss()` and closes the checklist
- [x] 5.4 Include a note indicating which page to navigate to for the active step

## 6. Frontend — TutorialHint Component

- [x] 6.1 Create `src/components/tutorial/TutorialHint.tsx` — a Mantine Popover tooltip anchored to a `data-tutorial-step` attribute on target elements
- [x] 6.2 Add `data-tutorial-step="create-category"` attribute to the Add Category button in CategoriesPage
- [x] 6.3 Add `data-tutorial-step="record-transaction"` attribute to the transaction form submit button in TransactionsPage
- [x] 6.4 Add `data-tutorial-step="view-summary"` attribute to the monthly summary section in HomePage
- [x] 6.5 Add `data-tutorial-step="set-budget"` attribute to the budget form in HomePage or BudgetPage

## 7. Frontend — Auto-show & Persistent Help Button

- [x] 7.1 In the main `Layout.tsx`, auto-open TutorialChecklist if `tutorial_progress` is null or empty on first load
- [x] 7.2 Add a persistent "?" floating action button to the Layout that opens the TutorialChecklist on click
- [x] 7.3 Hide the auto-show logic if the user has previously dismissed or completed all steps

## 8. Frontend — Step Auto-completion Detection

- [x] 8.1 After a category is successfully created, call `completeStep('create-category')`
- [x] 8.2 After a transaction is successfully created, call `completeStep('record-transaction')`
- [x] 8.3 When the monthly summary section mounts with data, call `completeStep('view-summary')`
- [x] 8.4 After a budget is successfully saved, call `completeStep('set-budget')`

## 9. Frontend — Restart Tutorial in Profile

- [x] 9.1 Add a "Restart Tutorial" button to `ProfilePage.tsx` that calls `restart()` from `useTutorial`
- [x] 9.2 On restart, send `PUT /api/user/tutorial` with cleared steps and `dismissed: false`, then re-open the checklist
