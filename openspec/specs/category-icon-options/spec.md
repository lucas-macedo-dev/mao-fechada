## Purpose

Defines the allow-listed set of icons a user can choose when creating or editing a category, and how the system falls back when a stored `icon` value isn't in that allow-list.

## Requirements

### Requirement: Category icon picker offers a broad set of personal-finance icons
The system SHALL offer an allow-listed set of selectable icons covering common personal-finance categories, including at minimum: tag, wallet, food, home, transport, health, education, shopping, salary, work, leisure, travel, pets, utilities/bills, subscriptions, groceries, gifts, insurance, taxes, investments, family/children, personal care, donations, and fuel. Each icon option SHALL have a translated, human-readable label in every supported locale.

#### Scenario: User picks a newly added icon for a custom category
- **WHEN** the user opens the category creation form and selects the "Pets" icon
- **THEN** the form's icon value SHALL be set to the pets icon class, and submitting SHALL persist that value on the created category

#### Scenario: Icon picker renders a label per locale
- **WHEN** the icon picker is rendered with the user's locale set to `pt-BR` or `en`
- **THEN** every icon option SHALL display a translated label in that locale, with no missing translation keys

### Requirement: Unrecognized icon values fall back to the default icon
The system SHALL render the default tag icon when a category's stored `icon` value is null, empty, or not present in the current allow-list, rather than failing to render or displaying a broken icon.

#### Scenario: Category has a legacy or unknown icon value
- **WHEN** a category's `icon` field holds a value that is not in `CATEGORY_ICON_OPTIONS`
- **THEN** the system SHALL display the default tag icon for that category instead of erroring
