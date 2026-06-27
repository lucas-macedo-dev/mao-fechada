## Purpose

Defines requirements for the shared `FormModal` component that wraps create and edit forms in a modal overlay across the application.

## Requirements

### Requirement: Create and edit forms open in a modal dialog
The system SHALL render all create and edit forms inside a Mantine `Modal` overlay instead of inline within the page or list rows. The modal SHALL be opened by the respective action button (Add / Edit) and closed by saving, cancelling, or pressing Escape.

#### Scenario: Opening the create modal
- **WHEN** the user clicks the "Add" or "New" button on a page that supports creating items
- **THEN** a modal dialog opens containing the create form with empty/default field values

#### Scenario: Opening the edit modal
- **WHEN** the user clicks the edit button on any list item (category, subcategory, transaction)
- **THEN** a modal dialog opens containing the edit form pre-populated with the item's current values

#### Scenario: Closing the modal on cancel
- **WHEN** the user clicks the Cancel button inside the modal
- **THEN** the modal closes without saving any changes and the list is unchanged

#### Scenario: Closing the modal on successful save
- **WHEN** the user submits the form with valid data and the API call succeeds
- **THEN** the modal closes and the list reflects the created or updated values

#### Scenario: Keeping the modal open on validation or API error
- **WHEN** the user submits the form with invalid data or the API returns an error
- **THEN** the modal remains open and an error message is displayed inside the form

#### Scenario: Closing the modal with Escape key
- **WHEN** the modal is open and the user presses the Escape key
- **THEN** the modal closes without saving changes

### Requirement: FormModal is a shared reusable component
The system SHALL provide a `FormModal` component in `web/src/components/FormModal.tsx` that accepts `title`, `opened`, `onClose`, and `children` props and wraps them in a Mantine `Modal`.

#### Scenario: Rendering children inside the modal
- **WHEN** `FormModal` is rendered with `opened={true}` and a form as children
- **THEN** the modal is visible and the form is rendered inside it

#### Scenario: Modal is hidden when closed
- **WHEN** `FormModal` is rendered with `opened={false}`
- **THEN** the modal overlay is not visible to the user
