## ADDED Requirements

### Requirement: New users are shown a tutorial checklist automatically
The system SHALL display a guided tutorial checklist to users whose tutorial progress has never been started, automatically on their first authenticated session load.

#### Scenario: First-time login triggers tutorial
- **WHEN** a user authenticates for the first time and their tutorial progress is null or empty
- **THEN** the system displays the tutorial checklist with all steps listed as incomplete

#### Scenario: Returning user does not see tutorial automatically
- **WHEN** an authenticated user who has previously interacted with the tutorial (completed at least one step or dismissed it) loads the app
- **THEN** the tutorial checklist is NOT shown automatically

### Requirement: Tutorial guides users through the four core flows
The system SHALL include exactly four ordered tutorial steps: (1) Create a category, (2) Record a transaction, (3) View the monthly summary, (4) Set a budget for a category.

#### Scenario: Steps are shown in order
- **WHEN** the tutorial checklist is displayed
- **THEN** the four steps are presented in sequence: Create Category → Record Transaction → View Summary → Set Budget

#### Scenario: Completing a step marks it done
- **WHEN** a user completes the action associated with a tutorial step (e.g., creates their first category)
- **THEN** that step is marked as completed in the checklist and the next step is highlighted

### Requirement: Tutorial progress is persisted per user on the backend
The system SHALL persist tutorial step completion state and dismissal state for each user, stored as a JSON structure in the users table, and expose it via the authenticated user profile endpoint.

#### Scenario: Completed steps survive page reload
- **WHEN** a user completes a tutorial step and then reloads the page
- **THEN** the previously completed step remains marked as done in the checklist

#### Scenario: Tutorial progress returned with user profile
- **WHEN** an authenticated request is made to `GET /api/user/me`
- **THEN** the response includes a `tutorial_progress` field with the current step completion state

#### Scenario: Updating tutorial progress
- **WHEN** a `PUT /api/user/tutorial` request is sent with a step ID and completion state
- **THEN** the system persists the update and returns the updated tutorial progress

### Requirement: Users can dismiss or restart the tutorial
The system SHALL allow users to dismiss the tutorial checklist at any time and to restart it from the Profile settings or via a persistent help button in the app layout.

#### Scenario: Dismissing the tutorial
- **WHEN** a user clicks the dismiss/close action on the tutorial checklist
- **THEN** the checklist is hidden and the dismissed state is persisted so it does not reappear automatically

#### Scenario: Restarting the tutorial from settings
- **WHEN** a user selects "Restart Tutorial" from the Profile page or the help button
- **THEN** all step completion states are cleared, dismissed is set to false, and the checklist reappears

#### Scenario: Persistent help button is always accessible
- **WHEN** the tutorial has been dismissed or completed
- **THEN** a persistent "?" help button remains visible in the app layout, and clicking it reopens the tutorial checklist

### Requirement: Tutorial provides contextual hints anchored to UI elements
The system SHALL render contextual tooltip-style hints anchored to relevant UI elements for each active tutorial step, pointing the user toward the correct action on the current page.

#### Scenario: Hint shown on the relevant page
- **WHEN** a tutorial step is active and the user is on the page where that action occurs
- **THEN** a tooltip hint is displayed anchored to the relevant UI element (e.g., the "Add Category" button)

#### Scenario: No hint shown when on an unrelated page
- **WHEN** a tutorial step is active but the user is NOT on the relevant page
- **THEN** no hint tooltip is shown, but the checklist indicates which page to navigate to
