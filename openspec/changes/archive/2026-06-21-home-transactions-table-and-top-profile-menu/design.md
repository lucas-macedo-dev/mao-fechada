## Context

The home dashboard (`HomePage.tsx`) currently renders recent transactions as stacked `<Paper>` cards limited to 5 items and filters by month via a bare `TextInput[type=month]` with no labeling or navigation. The `Layout.tsx` renders a mobile bottom nav bar that includes profile and subscription links alongside navigation links, with no top-level identity area on mobile. All UI is built on Mantine v7.

## Goals / Non-Goals

**Goals:**
- Replace the transaction card list with a Mantine `Table` showing up to 15 rows, responsive on all screen sizes
- Redesign the month filter into a labeled control with prev/next month arrows for ergonomic navigation
- Add a mobile-only top app bar to `Layout` with the system logo (left) and a profile avatar button (right) that opens a Mantine `Drawer` with "My Profile" and "My Plan" links
- Remove the profile AND subscription links from the mobile bottom nav (both move to the top app bar drawer)

**Non-Goals:**
- Changing any backend API endpoints or response shapes
- Adding pagination or infinite scroll to the home transactions list (that belongs on the Transactions page)
- Modifying the desktop sidebar layout
- Modifying auth pages, categories page, or transactions page

## Decisions

### 1. Mantine `Table` with `ScrollArea` for responsive layout
Use Mantine `<Table>` wrapped in `<ScrollArea>` so wide tables scroll horizontally on narrow screens rather than collapsing. The table will have columns: Date, Category, and Amount (colored by type). Striped rows improve scannability.

**Alternative considered**: Keep the card list and add a "show more" — rejected because cards are wasteful in vertical space and unreadable when there are 15 items.

### 2. Month filter bar with `ActionIcon` prev/next arrows
Keep `<input type="month">` natively (avoids adding `@mantine/dates` dependency if not already installed) but wrap it in a Mantine `Group` with `ActionIcon` chevron buttons that decrement/increment the month. Add a `Text` label above the group. This gives keyboard-friendly navigation without extra libraries.

**Alternative considered**: Mantine `MonthPickerInput` from `@mantine/dates` — viable if the package is already in use, but adds overhead if not; check `package.json` during implementation.

### 3. Mobile top app bar as a fixed `Box` in `Layout`
Add a Mantine `Box` with `position: fixed; top: 0` inside `Layout`, visible only on mobile (using `isMobile` state already present). It contains: logo image + app title on the left, and an `ActionIcon` with user avatar (or FA user icon fallback) on the right. Clicking the avatar opens a Mantine `Drawer` (position `right`, size `xs`) with two `NavLink` items: "My Profile" → `/profile` and "My Plan" → `/subscription`.

The main content area on mobile will need `paddingTop` equal to the app bar height (approx 56px) to avoid overlap.

**Alternative considered**: Mantine `AppShell` header — would require restructuring the entire Layout component; too high a blast radius for this change. A simple fixed Box is surgical.

### 4. Profile and Plan removed from mobile bottom nav
The bottom nav currently has 5 links + logout. Removing the Profile and Subscription links reduces it to 3 links (Home, Categories, Transactions) + logout. The top app bar profile drawer is the canonical entry point for both Profile and Plan on mobile.

## Risks / Trade-offs

- [Fixed top bar + fixed bottom nav] → Both bars are fixed-position on mobile, squeezing the scrollable content area. Mitigate by setting explicit `paddingTop` and `paddingBottom` on `main-content` for mobile.
- [isMobile via resize listener] → The existing `isMobile` boolean is already used in Layout; the new top bar reuses the same flag, so no duplication. Risk: SSR / very fast resize could flicker. Acceptable given this is a client-side SPA.
- [15 transactions limit] → Changing `per_page` from 5 to 15 increases payload on the home page load. The endpoint already supports this parameter and transactions are small JSON objects; impact is negligible.

## Migration Plan

1. Update `HomePage.tsx`: change `per_page: 5 → 15`, replace card list with `Table`, redesign month filter bar
2. Update `Layout.tsx`: add mobile top app bar component inline, remove Profile and Subscription links from bottom nav, adjust main content padding
3. No data migrations or feature flags needed — changes are purely additive UI

## Open Questions

- Is `@mantine/dates` already installed? If yes, `MonthPickerInput` is preferred over the native input for a more polished look. Check `package.json` during implementation.
