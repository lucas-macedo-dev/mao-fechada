## Why

New users land in the app without guidance, leaving them unsure how to create their first transaction, configure categories, or read the dashboard. A structured onboarding tutorial reduces drop-off and helps users reach the "aha moment" faster by walking them through the core flows step by step.

## What Changes

- Add an interactive tutorial/checklist component that appears for new users after first login
- Track per-user tutorial progress (which steps have been completed) in the backend
- Show contextual tooltip-style hints anchored to key UI elements (transaction form, categories page, dashboard charts)
- Allow users to dismiss or restart the tutorial at any time from the settings or a persistent help button

## Capabilities

### New Capabilities

- `user-onboarding-tutorial`: Interactive step-by-step tutorial that guides new users through: adding their first transaction, setting up categories, viewing the dashboard summary, and setting a budget. Tracks completion state per user and can be dismissed or restarted.

### Modified Capabilities

<!-- No existing spec-level requirements are changing -->

## Impact

- **Frontend (web/)**: New tutorial overlay/checklist component; contextual tooltip anchors on existing pages (HomePage, CategoriesPage, transaction form)
- **Backend (api/)**: New `user_tutorial_progress` tracking — likely a JSON column on the users table or a dedicated table; new API endpoints to read and update tutorial state
- **No breaking changes** to existing APIs or flows — tutorial is additive and opt-out
