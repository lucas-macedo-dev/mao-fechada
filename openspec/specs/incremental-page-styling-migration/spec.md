## Purpose

Define how frontend styling migration is performed incrementally and safely while preserving production behavior.

## Requirements

### Requirement: Styling migration SHALL be incremental and deploy-safe
The frontend styling migration from custom CSS to Mantine SHALL be executed in incremental, page-scoped steps so the application remains working and deployable after each step.

#### Scenario: A migration step is completed
- **WHEN** one migration increment is merged
- **THEN** the app remains functional in production without requiring a full styling rewrite to be complete

#### Scenario: Legacy CSS coexists during transition
- **WHEN** only part of the app has been migrated
- **THEN** existing CSS and Mantine styles may coexist until all targeted pages are migrated

### Requirement: Categorias page SHALL be migrated before high-traffic pages
The first page-level migration SHALL target the isolated low-traffic Categorias page before styling migration begins on high-traffic pages like Início and Extrato.

#### Scenario: First migrated page is selected
- **WHEN** page-by-page migration starts
- **THEN** the Categorias page is migrated before Início and Extrato

#### Scenario: High-traffic pages are deferred
- **WHEN** Categorias migration is not yet complete
- **THEN** migration work does not start on Início or Extrato

### Requirement: Styling migration SHALL not change business behavior contracts
The migration SHALL be restricted to the UI/styling layer and SHALL NOT alter API contracts, business logic outcomes, or i18n keys.

#### Scenario: Migrated page renders and interacts with data
- **WHEN** a page has been restyled with Mantine
- **THEN** request/response contracts and business actions remain behaviorally equivalent to pre-migration behavior

#### Scenario: Internationalized text is preserved
- **WHEN** a component is migrated
- **THEN** existing i18n keys continue to be used without key renames or semantic changes

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
