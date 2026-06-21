## ADDED Requirements

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

### Requirement: Profile avatar button SHALL open a profile drawer on mobile
Tapping the profile avatar button in the top app bar SHALL open a Mantine `Drawer` from the right side with a backdrop. The drawer SHALL contain navigation links for "My Profile" and "My Plan".

#### Scenario: User taps the profile avatar button
- **WHEN** the user taps the profile avatar icon in the top app bar
- **THEN** a drawer slides in from the right with a backdrop overlay

#### Scenario: Drawer contains My Profile link
- **WHEN** the profile drawer is open
- **THEN** a "My Profile" link is visible and navigates to `/profile` when tapped, closing the drawer

#### Scenario: Drawer contains My Plan link
- **WHEN** the profile drawer is open
- **THEN** a "My Plan" link is visible and navigates to `/subscription` when tapped, closing the drawer

#### Scenario: Drawer closes on backdrop tap
- **WHEN** the user taps outside the drawer (on the backdrop)
- **THEN** the drawer closes

### Requirement: Mobile bottom nav SHALL NOT include Profile or Plan links
The mobile bottom navigation bar SHALL only contain the primary navigation links: Home, Categories, and Transactions. Profile and Plan SHALL be accessible exclusively via the top app bar profile drawer on mobile.

#### Scenario: Mobile bottom nav link count
- **WHEN** the mobile bottom nav is rendered
- **THEN** it contains Home, Categories, and Transactions links plus a logout button, and does NOT contain Profile or Subscription links
