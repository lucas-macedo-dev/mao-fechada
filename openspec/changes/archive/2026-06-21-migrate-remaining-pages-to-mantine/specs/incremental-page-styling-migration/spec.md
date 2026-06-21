## ADDED Requirements

### Requirement: Remaining pages SHALL be migrated after Categorias is deployed
After the Categorias page migration is deployed to production, the migration SHALL continue to cover HomePage, TransactionsPage, LoginPage, RegisterPage, ProfilePage, and SubscriptionPage in the order defined in the design.

#### Scenario: HomePage is migrated
- **WHEN** the HomePage migration is complete
- **THEN** the page renders using Mantine components and the shared theme without importing `pages.css`

#### Scenario: TransactionsPage is migrated
- **WHEN** the TransactionsPage migration is complete
- **THEN** the page renders using Mantine components and the shared theme without importing `pages.css`

#### Scenario: Auth pages are migrated
- **WHEN** both LoginPage and RegisterPage migrations are complete
- **THEN** both pages render using Mantine components and the shared theme without importing `auth.css`, and `auth.css` may be fully removed

#### Scenario: Profile and Subscription pages are migrated
- **WHEN** ProfilePage and SubscriptionPage migrations are complete
- **THEN** both pages render using Mantine components and the shared theme without any custom CSS file imports

### Requirement: Custom CSS files SHALL be cleaned up as pages are migrated
As each page migration is completed, the CSS selectors exclusively used by that page SHALL be removed from `pages.css` and `auth.css`. CSS files SHALL be deleted entirely once all pages that import them are migrated.

#### Scenario: A CSS selector is removed after page migration
- **WHEN** a page is fully migrated to Mantine
- **THEN** selectors that were only used by that page are removed from the shared CSS file in the same increment

#### Scenario: A CSS file is deleted after all its consumers are migrated
- **WHEN** the last page importing a CSS file (e.g., `auth.css`) is migrated
- **THEN** that CSS file is deleted from the codebase
