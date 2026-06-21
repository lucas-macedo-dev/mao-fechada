## 1. HomePage — Month Filter Bar

- [x] 1.1 Wrap the existing `TextInput[type=month]` in a Mantine `Group` with prev/next `ActionIcon` (chevron-left / chevron-right) buttons
- [x] 1.2 Implement `prevMonth` / `nextMonth` handlers that decrement/increment the `month` state by one calendar month
- [x] 1.3 Add a `Text` label ("Mês de referência" or i18n key) above the filter group to make it visually prominent
- [x] 1.4 Verify the filter bar renders correctly on mobile (no overflow) and that prev/next buttons update the dashboard data

## 2. HomePage — Transactions Table

- [x] 2.1 Change the `useTransactions` call on the home page from `per_page: 5` to `per_page: 15`
- [x] 2.2 Replace the `<Stack>` of `<Paper>` cards with a Mantine `<Table>` (striped, with `ScrollArea` wrapper) having columns: Date, Category (icon + name), Amount
- [x] 2.3 Apply green/red color and +/− prefix to Amount based on transaction type (reuse existing `normalizeType` helper)
- [x] 2.4 Keep the empty-state `<Text>` message when no transactions exist
- [x] 2.5 Verify the table scrolls horizontally on mobile without breaking the page layout

## 3. Layout — Mobile Top App Bar + Profile Menu

- [x] 3.1 Add mobile-only fixed top app bar with system logo (left) and profile `Menu` trigger (right)
- [x] 3.2 Profile button opens a Mantine `Menu` dropdown (with backdrop) — not a Drawer/sidebar
- [x] 3.3 Menu contains: user name label, My Profile → `/profile`, My Plan → `/subscription`, divider, Logout (red)
- [x] 3.4 Avatar shown in trigger if user has `profile_photo_url`, otherwise FA user icon fallback
- [x] 3.5 Add top padding to `main-content` on mobile (≈ 56px) to prevent content hiding behind fixed bar
- [x] 3.6 Read-only props lint warning fixed on Layout component

## 4. Layout — Mobile Bottom Nav Cleanup

- [x] 4.1 Remove Profile link from mobile bottom nav
- [x] 4.2 Remove Subscription link from mobile bottom nav
- [x] 4.3 Remove Logout button from mobile bottom nav (moved to profile menu)
- [x] 4.4 Bottom nav now contains only: Home, Categories, Transactions

## 5. Verification

- [x] 5.1 Vite HMR picked up all three changed files with no runtime errors
- [x] 5.2 Pre-existing `@mantine/core` TS2307 error is root-owned node_modules issue, not introduced by this change
- [x] 5.3 Manual browser verification: transactions table, month filter arrows, profile menu on mobile
