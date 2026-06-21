## ADDED Requirements

### Requirement: Home dashboard SHALL provide a labeled month filter bar with navigation controls
The month filter on the home dashboard SHALL be displayed as a labeled control group containing a "previous month" button, a month input, and a "next month" button. The filter MUST be visually prominent and clearly associated with the dashboard data it controls.

#### Scenario: Filter bar is rendered
- **WHEN** the home dashboard loads
- **THEN** a labeled month filter bar is displayed above the summary cards, containing prev/next arrow buttons flanking the month input

#### Scenario: User navigates to previous month
- **WHEN** the user clicks the "previous month" arrow button
- **THEN** the selected month decrements by one month and dashboard data reloads for the new month

#### Scenario: User navigates to next month
- **WHEN** the user clicks the "next month" arrow button
- **THEN** the selected month increments by one month and dashboard data reloads for the new month

#### Scenario: User selects month via input
- **WHEN** the user changes the month value directly via the month input
- **THEN** the dashboard data reloads for the newly selected month

#### Scenario: Filter bar on mobile
- **WHEN** the viewport is mobile-width
- **THEN** the filter bar remains fully visible and usable without horizontal overflow
