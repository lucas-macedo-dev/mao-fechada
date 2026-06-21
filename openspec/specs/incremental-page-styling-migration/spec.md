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
