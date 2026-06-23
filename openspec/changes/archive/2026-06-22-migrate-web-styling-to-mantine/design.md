## Context

The `web/` app currently relies on broad custom CSS files (`index.css`, `App.css`, `styles/layout.css`, `styles/pages.css`) that mix shared styles and page-specific rules. This makes consistency difficult and increases regression risk when changing UI.  
The migration target is Mantine for the styling layer only, while keeping the frontend production-safe after every increment. Backend (`api/`) and all business behavior are out of scope.

## Goals / Non-Goals

**Goals:**
- Introduce Mantine as the primary styling system in `web/` with a single shared theme.
- Keep code beginner-friendly: explicit components, minimal abstractions, and simple TypeScript usage.
- Migrate page by page in a deploy-safe sequence, starting with **Categorias** before higher-traffic pages.
- Preserve existing logic, API calls, routes, i18n keys, and FontAwesome icons.

**Non-Goals:**
- Rewriting domain logic, state management, API contracts, or backend code.
- Full redesign of UX/copy beyond equivalent style migration.
- Generic component frameworks, advanced typed utility layers, or new hook abstractions.

## Decisions

1. **Adopt Mantine through a single app-level provider and theme object.**  
   Rationale: this centralizes tokens (colors, spacing, typography, radius) and reduces CSS drift.

2. **Use incremental coexistence of Mantine and existing CSS during transition.**  
   Rationale: pages can be migrated one at a time without a risky big-bang rewrite.

3. **Define a small, explicit set of reusable UI building blocks from Mantine primitives.**  
   Rationale: avoids repetitive styling while keeping code easy for beginners to read and modify.

4. **Preserve FontAwesome icon usage and map icons via Mantine component slots.**  
   Rationale: keeps current icon assets and avoids unnecessary icon-library migration work.

5. **Migrate in this order: setup/theme → Categorias → shared layout surfaces → high-traffic pages (Início, Extrato) → remaining pages → CSS cleanup.**  
   Rationale: starts with low-risk validation and defers critical pages until the approach is proven.

6. **Keep styling-only scope strict.**  
   Rationale: separating visual migration from logic changes prevents accidental production behavior drift.

## Risks / Trade-offs

- **[Risk] Mixed styling systems during migration can create visual inconsistencies.** → **Mitigation:** maintain migration checklist per page and remove legacy selectors only when equivalent Mantine styling is in place.
- **[Risk] Global CSS selectors may still affect migrated Mantine components.** → **Mitigation:** reduce global selector usage incrementally and prefer component-level Mantine styling for migrated surfaces.
- **[Risk] Limited React/TypeScript familiarity can slow implementation.** → **Mitigation:** enforce explicit/simple patterns, avoid advanced abstractions, and keep component props and theme setup straightforward.
- **[Risk] High-traffic pages carry regression risk.** → **Mitigation:** postpone Início/Extrato until low-risk pages and shared primitives are stable.

## Migration Plan

1. Install Mantine packages in `web/` and wrap app root with `MantineProvider`.
2. Create one shared theme file with minimal token set and safe defaults.
3. Add a small set of explicit reusable styled components (container/section/form/action primitives), avoiding generic abstractions.
4. Migrate **Categorias** page styling first; keep logic intact and preserve icons.
5. Migrate shared layout-level surfaces that are low-risk and improve consistency.
6. Migrate high-traffic pages (**Início**, **Extrato**) after previous steps are stable.
7. Migrate remaining pages gradually; remove replaced CSS selectors in parallel.
8. Perform final CSS cleanup once all targeted pages are migrated.

## Open Questions

- Which exact color scale should be considered canonical for the initial Mantine theme (mapped from current production visuals)?
- Should layout migration include navigation/header/footer in the first shared-layout pass or only internal content wrappers?
