## Why

The frontend styling is spread across large custom CSS files, which makes visual consistency hard to maintain and risky to evolve. Migrating incrementally to Mantine gives us a single, explicit theme and reusable UI building blocks while keeping the production app stable at every deployment step.

## What Changes

- Add Mantine packages to `web/` and wire the app with `MantineProvider`.
- Define one simple shared theme (colors, spacing, radius, typography sizing, and defaults) as the new styling source of truth.
- Introduce a small set of reusable styled building blocks using Mantine primitives (for page containers, cards/sections, form rows, and action bars) with explicit props and no advanced abstractions.
- Migrate styling incrementally, page by page, starting with the isolated **Categorias** page.
- Keep FontAwesome icons and pass them through Mantine component slots (`leftSection`/`rightSection`) where relevant.
- Preserve business logic, API contracts, routing behavior, and i18n keys; this change is UI/styling only.
- Keep legacy CSS in place during migration, removing only selectors that are fully replaced to avoid regressions.

## Capabilities

### New Capabilities
- `mantine-theme-foundation`: Establish a single Mantine-based visual theme and reusable low-complexity UI building blocks for the web frontend.
- `incremental-page-styling-migration`: Migrate frontend pages from custom CSS to Mantine in a controlled order, with deployable intermediate states.

### Modified Capabilities
- None.

## Impact

- Affected area: `web/` only (React + TypeScript + Vite frontend).
- Main files likely touched: app bootstrap, theme/config files, page components, and legacy CSS files (`index.css`, `App.css`, `styles/layout.css`, `styles/pages.css`).
- New dependency footprint in `web/package.json`: Mantine core packages (and peer styling helpers if required by Mantine version in use).
- No backend (`api/`) changes, no API schema changes, and no i18n key/value changes.
