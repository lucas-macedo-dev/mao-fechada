## Purpose

TBD — Defines the rules for creating, editing, and deleting subcategories (two-level category hierarchy), including parent assignment validation, UI filtering, and safe deletion with transaction reassignment.

## Requirements

### Requirement: User can create a subcategory under a root category
The system SHALL allow a user to create a category with an optional `parent_id` pointing to an existing root category owned by the same user. The subcategory's type MUST match the parent's type. The parent MUST be a root category (depth 1); assigning a subcategory as parent SHALL be rejected.

#### Scenario: Creating a subcategory with valid parent
- **WHEN** the user submits a category creation form with a valid root `parent_id` and a matching `type`
- **THEN** the system SHALL create the category with `parent_id` set and return it with status 201

#### Scenario: Creating a subcategory with type mismatch
- **WHEN** the user submits a category creation form with a `parent_id` whose category type differs from the submitted `type`
- **THEN** the system SHALL return a validation error on the `type` field

#### Scenario: Creating a subcategory under another subcategory
- **WHEN** the user submits a `parent_id` that points to a category that itself has a `parent_id`
- **THEN** the system SHALL return a validation error on the `parent_id` field indicating the parent must be a root category

### Requirement: User can assign or change a category's parent via the edit UI
The system SHALL allow a user to edit an existing category and optionally assign or change its `parent_id` to any root category of the same type owned by the user, or clear it to make the category a root.

#### Scenario: Reassigning a subcategory to a different root parent
- **WHEN** the user edits a subcategory and selects a different root parent of the same type
- **THEN** the category's `parent_id` SHALL be updated and the API SHALL return the updated category with the new parent loaded

#### Scenario: Detaching a subcategory (making it a root)
- **WHEN** the user edits a subcategory and clears the parent selector
- **THEN** the category's `parent_id` SHALL be set to null, making it a root category

#### Scenario: Assigning parent to a root category that has children
- **WHEN** a root category has children and the user tries to assign it a parent
- **THEN** the system SHALL reject the request with a validation error (cannot turn a parent into a child)

### Requirement: Category creation form shows parent selector filtered by type
The category creation UI SHALL display an optional parent category selector. The selector SHALL only show root categories (categories with no `parent_id`) matching the currently selected type. Categories that already have a `parent_id` SHALL NOT appear in the selector. When a parent is selected, the type field SHALL become read-only.

#### Scenario: User selects a parent in the create form
- **WHEN** the user selects a parent category from the selector
- **THEN** the type field SHALL become disabled and SHALL display the parent's type

#### Scenario: User clears the parent selection
- **WHEN** the user clears the parent selector
- **THEN** the type field SHALL become editable again

#### Scenario: Subcategories are excluded from the parent selector
- **WHEN** the parent selector is rendered
- **THEN** only categories with `parent_id = null` SHALL be listed; categories that already belong to a parent SHALL not appear as selectable options

### Requirement: Category deletion reassigns transactions to "Sem Categoria" fallback
When a user deletes a category that has linked transactions, the system SHALL NOT throw a database constraint error. Instead, the system SHALL find or create a fallback category named "Sem Categoria" (pt-BR) or "No Category" (en) of the same type for the user, reassign all affected transactions to it, and then delete the original category.

#### Scenario: Deleting a category with transactions
- **WHEN** the user confirms deletion of a category that has one or more linked transactions
- **THEN** the system SHALL reassign all those transactions to the matching "Sem Categoria" fallback category and delete the original, returning HTTP 204

#### Scenario: Deleting a category with no transactions
- **WHEN** the user confirms deletion of a category with no linked transactions
- **THEN** the system SHALL delete the category normally and return HTTP 204

#### Scenario: Fallback category does not yet exist at deletion time
- **WHEN** the fallback "Sem Categoria" category does not exist for the user and the user deletes a category with transactions
- **THEN** the system SHALL create the fallback category, reassign the transactions, and delete the original in a single atomic operation

### Requirement: Frontend deletion dialog informs user of transaction reassignment
The category deletion confirmation UI SHALL inform the user that deleting a category will move its transactions to "Sem Categoria".

#### Scenario: User initiates category deletion
- **WHEN** the user clicks the delete button for any category
- **THEN** the UI SHALL display a confirmation dialog stating that transactions will be moved to "Sem Categoria / No Category" before proceeding
