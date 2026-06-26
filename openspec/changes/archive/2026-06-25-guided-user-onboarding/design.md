## Context

Mão Fechada is a personal finance app (React + TypeScript frontend, Laravel + Sanctum backend). New users register and land directly in the dashboard with no guidance — they must discover how to add categories, transactions, budgets, and read the summary on their own. The app already has distinct pages (HomePage, TransactionsPage, CategoriesPage) and a Mantine-based UI system, so a tutorial layer can be added without restructuring the app.

## Goals / Non-Goals

**Goals:**
- Guide new users through the core flows: add a category, record a transaction, view the monthly summary, set a budget
- Persist tutorial progress per-user so completion survives page reloads and devices
- Allow users to dismiss or restart the tutorial at any time
- Fit naturally into the existing Mantine component system

**Non-Goals:**
- Video walkthroughs or animated demo data
- Multi-language tutorial content (deferred to localization work)
- Mandatory onboarding that blocks app usage
- Analytics/funnel tracking of tutorial completion

## Decisions

### 1. Tutorial state stored as a JSON column on `users` table

**Decision:** Add a nullable `tutorial_progress` JSON column to `users` instead of a separate `user_tutorial_steps` table.

**Rationale:** Tutorial state is an attribute of the user, not an entity with its own relationships. A JSON column keeps it simple — one migration, one field on the User model. A separate table would add a join to every `/api/user/me` response with no benefit at this scale.

**Alternative considered:** Separate `user_tutorial_progress` table with one row per step. Rejected because it over-engineers a simple boolean-per-step state bag.

---

### 2. Frontend: custom checklist + Mantine Popover tooltips (no third-party tour library)

**Decision:** Implement a `TutorialChecklist` sidebar/drawer component and per-step `TutorialHint` tooltips using Mantine's `Popover` and `Drawer`, rather than a library like React Joyride or Shepherd.js.

**Rationale:** Third-party tour libraries are opinionated about DOM positioning and often conflict with Mantine's Portal-based rendering. A lightweight custom component gives full control over UX and avoids adding a heavy dependency. The tutorial has only 4 steps — the complexity doesn't justify a library.

**Alternative considered:** React Joyride. Rejected because it uses its own overlay/portal system that can conflict with Mantine Modal and Drawer stacking contexts.

---

### 3. Tutorial triggers automatically for users with no completed steps

**Decision:** On first authenticated load, if `tutorial_progress` is empty/null, show the tutorial checklist automatically. After any step is dismissed or completed, the checklist becomes opt-in (accessible via a persistent "?" button in the layout).

**Rationale:** Auto-show on first login catches users before they wander. Once a user has interacted with the tutorial (even to dismiss it), they've signaled awareness, so future visits should not re-interrupt.

---

### 4. Tutorial steps are defined client-side; backend only stores completion state

**Decision:** Step definitions (title, description, target page, hint anchor) live in a TypeScript config file. The backend stores only which step IDs have been `completed` and whether the tutorial has been `dismissed`.

**Rationale:** Step content is UI concern — keeping it in the frontend avoids an API round-trip to render the tutorial. The backend is authoritative only for persistence, not presentation.

## Risks / Trade-offs

- **Risk: JSON column is hard to migrate if steps change** → Mitigation: store step IDs as strings (not indexes); adding/removing steps never invalidates existing completion records. Deprecated step IDs are simply ignored.
- **Risk: Tutorial state out of sync between tabs** → Mitigation: refresh tutorial state on each focus/navigate event; acceptable UX trade-off for this feature's scope.
- **Risk: Tutorial hints anchored to DOM elements may break if layout changes** → Mitigation: use stable `data-tutorial-step` attributes on target elements rather than CSS selectors; decoupled from styling changes.

## Migration Plan

1. Add `tutorial_progress` JSON column (nullable, default null) to `users` table via a new migration
2. Expose tutorial state on the `/api/user/me` response (already consumed by the frontend)
3. Add `PUT /api/user/tutorial` endpoint to update step completion and dismiss state
4. Ship frontend `TutorialChecklist` and `TutorialHint` components behind a feature check (`tutorial_progress === null` = show automatically)
5. No rollback concern — the column is nullable and additive; removing the frontend components leaves the column inert

## Open Questions

- Should the tutorial include a "reset tutorial" option in Profile settings, or is a "?" button in the main layout sufficient entry point?
- Are the 4 core steps (category → transaction → summary → budget) the right order, or should budget come before transaction?
