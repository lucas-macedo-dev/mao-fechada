## ADDED Requirements

### Requirement: Web frontend SHALL provide a single Mantine theme foundation
The `web/` application SHALL define and use one shared Mantine theme as the primary source of truth for colors, spacing, typography, and base radii used by migrated UI surfaces.

#### Scenario: App initializes with shared theme
- **WHEN** the frontend app is started
- **THEN** the root of the React tree uses a Mantine provider configured with a shared theme object

#### Scenario: Theme tokens are reused by migrated pages
- **WHEN** a page is migrated from custom CSS to Mantine
- **THEN** the page uses shared theme tokens instead of hardcoded per-page visual values

### Requirement: Web frontend SHALL provide reusable beginner-friendly Mantine styled building blocks
The `web/` application SHALL expose a small set of explicit reusable styled components built on Mantine primitives for common page structures, without advanced generic abstractions.

#### Scenario: Shared page structure can be composed without custom CSS classes
- **WHEN** a developer builds or migrates a page section
- **THEN** they can compose container/section/form/action layouts using reusable Mantine-based components with explicit props

#### Scenario: Reusable components remain simple to maintain
- **WHEN** a developer with beginner-level React/TypeScript updates styles
- **THEN** component code is explicit and readable without advanced typing patterns or helper abstractions

### Requirement: FontAwesome icon usage SHALL remain compatible with Mantine components
The `web/` application SHALL keep existing FontAwesome icons and integrate them with Mantine component slots where icon placement is needed.

#### Scenario: Icon-bearing controls are migrated
- **WHEN** a control requiring icons is implemented with Mantine components
- **THEN** existing FontAwesome icons are passed using supported icon sections (such as leftSection or rightSection)
