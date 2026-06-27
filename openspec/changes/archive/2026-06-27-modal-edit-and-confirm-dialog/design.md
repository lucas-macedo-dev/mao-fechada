## Context

The frontend currently renders create forms as expanded panels (top or bottom of the page) and edit forms inline inside list rows for categories, subcategories, and transactions. This causes layout shifts and inconsistent UX. Confirmation dialogs use `window.confirm`, which cannot be styled with Mantine, is blocked in some headless environments, and does not support rich text or localized button labels.

The app uses **Mantine UI** throughout. Two reusable components are needed: a `FormModal` wrapper (handling both create and edit) and a `ConfirmDialog`.

## Goals / Non-Goals

**Goals:**
- Replace every inline create and edit form with a Mantine `Modal` opened by the respective action button
- Replace every `window.confirm` with a themed `ConfirmDialog` component
- Keep all business logic (API calls, validation, state) in the same page components — only move UI rendering into the modal
- Zero API changes

**Non-Goals:**
- Redesigning the forms themselves (only moving them into a modal)
- Adding new fields or validation rules

## Decisions

### 1. Shared `FormModal` wrapper vs. per-page modals
**Decision**: One generic `FormModal` component that receives `title`, `opened`, `onClose`, and `children`.

**Rationale**: Each page's form already manages its own state and submit handler. The modal is just a container. A generic wrapper avoids duplicating modal boilerplate in every page while keeping form logic colocated with its page.

**Alternatives**: Per-page modal components — more explicit but creates boilerplate for identical frame code.

### 2. Single modal for create and edit
**Decision**: Each page uses one `FormModal` instance for both create and edit actions. A local `formMode: 'create' | 'edit'` state (along with the item being edited) controls the title and the form content rendered as children.

**Rationale**: Reusing one modal avoids two overlapping modal trees and keeps open/close logic centralized. The form content already differs by what fields are pre-filled, not by JSX structure.

**Alternatives**: Separate create modal and edit modal per page — clearer separation but doubles boilerplate for nearly identical modal frames.

### 3. `ConfirmDialog` API
**Decision**: A component with `opened`, `title`, `message`, `onConfirm`, `onCancel`, and `loading` props. Each page holds a `confirmState: { opened, title, message, onConfirm } | null` in local state.

**Rationale**: This matches Mantine patterns and lets each page compose a confirm request before opening the dialog. It avoids a global context or imperative API.

**Alternatives**: 
- Imperative `useConfirm()` hook — cleaner at call-site but requires a context provider and ref-based promises.
- Mantine's `modals.openConfirmModal` — available but couples us to the Mantine modals manager global setup; current project does not use it.

### 4. State management for form modal
**Decision**: Each page keeps `formModal: null | { mode: 'create' } | { mode: 'edit'; item: T }` state. When non-null, `FormModal` is opened. Closing sets it to `null`.

**Rationale**: Encodes both the action intent (create vs edit) and the subject in one state slice, replacing the previous separate `editingId` and inline create-section flags.

## Risks / Trade-offs

- **Focus trap**: Mantine `Modal` handles focus automatically; forms inside it will work correctly.
- **Scroll position**: Opening a modal does not shift the list, which is an improvement over inline expansion.
- **Mobile**: Mantine modals are responsive by default; no extra work needed.
- **Testing**: Removing `window.confirm` makes tests simpler — `ConfirmDialog` state is observable via React state, not browser dialog.
