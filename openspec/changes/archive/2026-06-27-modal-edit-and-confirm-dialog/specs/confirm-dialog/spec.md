## ADDED Requirements

### Requirement: Destructive actions use a custom confirm dialog
The system SHALL replace all `window.confirm` calls with a themed `ConfirmDialog` component. The dialog SHALL display a title, a descriptive message, and two buttons: a confirm action button and a cancel button.

#### Scenario: Opening the confirm dialog for deletion
- **WHEN** the user clicks the delete button on any list item
- **THEN** a modal confirmation dialog opens with a title and message describing what will be deleted

#### Scenario: Confirming the destructive action
- **WHEN** the confirm dialog is open and the user clicks the confirm button
- **THEN** the dialog closes and the destructive action (delete, bulk edit) is executed

#### Scenario: Cancelling the destructive action
- **WHEN** the confirm dialog is open and the user clicks the cancel button
- **THEN** the dialog closes and no action is taken

#### Scenario: Closing the confirm dialog with Escape
- **WHEN** the confirm dialog is open and the user presses Escape
- **THEN** the dialog closes and no action is taken

#### Scenario: Confirm button shows loading state during execution
- **WHEN** the user clicks the confirm button and the action is in progress
- **THEN** the confirm button displays a loading indicator and is disabled until the action completes or fails

### Requirement: ConfirmDialog is a shared reusable component
The system SHALL provide a `ConfirmDialog` component in `web/src/components/ConfirmDialog.tsx` that accepts `opened`, `title`, `message`, `onConfirm`, `onCancel`, and `loading` props.

#### Scenario: Dialog renders with title and message
- **WHEN** `ConfirmDialog` is rendered with `opened={true}`, a `title`, and a `message`
- **THEN** the dialog displays the provided title and message to the user

#### Scenario: Callbacks are triggered by button clicks
- **WHEN** the user clicks the confirm button, `onConfirm` is called; when the user clicks cancel or closes the modal, `onCancel` is called
- **THEN** the parent component handles the respective action
