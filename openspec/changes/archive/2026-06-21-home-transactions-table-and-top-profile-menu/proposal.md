## Why

The home dashboard's "Últimos lançamentos" section shows transactions as stacked cards, which is space-inefficient and not scalable as the list grows. The month filter is a bare text input with no visual hierarchy, making it easy to overlook. On mobile, the profile shortcut buried in the bottom nav lacks context and competes with navigation links — a dedicated top app bar gives users a clear identity anchor and quick access to account actions.

## What Changes

- Replace the stacked transaction cards in "Últimos lançamentos" with a responsive Mantine `Table`, showing up to 15 rows (currently capped at 5 cards), with columns for date, category, description, and amount
- Redesign the month filter into a prominent, labeled filter bar with a `MonthPickerInput` (or styled `<input type="month">`) accompanied by navigation arrows (prev/next month) so it feels purposeful and integrated
- Add a mobile-only top app bar to the `Layout`, containing the system logo on the left and a profile avatar button on the right; tapping the avatar opens a backdrop side-drawer with "My Profile" and "My Plan" links
- Remove the profile link from the mobile bottom navigation bar (it moves to the top app bar)

## Capabilities

### New Capabilities
- `home-transactions-table`: Responsive table in the home dashboard showing up to 15 recent transactions with date, category, amount, and type columns
- `home-month-filter-bar`: Redesigned month filter with prev/next month navigation arrows and a clear label, replacing the isolated bare text input
- `mobile-top-app-bar`: Mobile-only top app bar with system logo (left) and profile avatar menu (right) that opens a backdrop drawer with "My Profile" and "My Plan" options

### Modified Capabilities
- `incremental-page-styling-migration`: Layout component changes affect the mobile navigation structure documented in this spec

## Impact

- `web/src/pages/HomePage.tsx` — transactions section and month filter UI
- `web/src/components/Layout.tsx` — adds mobile top app bar, removes profile from bottom nav
- `web/src/hooks/api.ts` — `useTransactions` call on home page changes `per_page` from 5 to 15
- No API or backend changes required
