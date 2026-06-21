## Purpose

Define the mobile-only top app bar in the layout, including the profile menu and bottom nav structure.

## Requirements

### Requirement: Layout SHALL display a top app bar on mobile viewports
On mobile viewports, the layout SHALL render a fixed top app bar containing the system logo and app title on the left side, and a profile avatar button on the right side. The top app bar SHALL NOT be visible on desktop viewports.

#### Scenario: Top app bar is visible on mobile
- **WHEN** the viewport width is below the mobile breakpoint (768px)
- **THEN** a fixed top app bar is displayed at the top of the screen with logo on the left and profile button on the right

#### Scenario: Top app bar is hidden on desktop
- **WHEN** the viewport width is at or above the mobile breakpoint
- **THEN** the top app bar is not rendered and the sidebar is shown instead

#### Scenario: Main content is not obscured by the top bar
- **WHEN** the top app bar is visible
- **THEN** the page main content area has sufficient top padding to avoid being hidden behind the fixed bar

### Requirement: Profile avatar button SHALL open a dropdown menu on mobile
Tapping the profile avatar button in the top app bar SHALL open a dropdown menu (with backdrop) showing the user's name, profile navigation links, and a logout action.

#### Scenario: User taps the profile avatar button
- **WHEN** the user taps the profile avatar icon in the top app bar
- **THEN** a dropdown menu opens with a backdrop overlay

#### Scenario: Menu contains My Profile link
- **WHEN** the profile menu is open
- **THEN** a "My Profile" link is visible and navigates to `/profile` when tapped, closing the menu

#### Scenario: Menu contains My Plan link
- **WHEN** the profile menu is open
- **THEN** a "My Plan" link is visible and navigates to `/subscription` when tapped, closing the menu

#### Scenario: Menu contains Logout action
- **WHEN** the profile menu is open
- **THEN** a "Logout" action is visible (highlighted in red) and logs the user out when tapped

#### Scenario: Menu closes on backdrop tap
- **WHEN** the user taps outside the menu (on the backdrop)
- **THEN** the menu closes

### Requirement: Mobile bottom nav SHALL contain only primary navigation links
The mobile bottom navigation bar SHALL only contain the primary navigation links: Home, Categories, and Transactions. Profile, Plan, and Logout SHALL be accessible exclusively via the top app bar profile menu on mobile.

#### Scenario: Mobile bottom nav link count
- **WHEN** the mobile bottom nav is rendered
- **THEN** it contains exactly Home, Categories, and Transactions links, and does NOT contain Profile, Subscription, or Logout
