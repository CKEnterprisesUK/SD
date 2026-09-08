# Requirements Document

## Introduction

This feature improves the accessibility, mobile usability, and content consistency of the SiteDesk portal. It covers portal-facing customer views, admin templates, and the shared Blade components those views render. Because many issues are recurring anti-patterns (missing focus indicators, icon-only controls without accessible names, hover-only touch targets, inconsistent copy), the strategy centralizes fixes in shared components where possible so a single fix propagates to both portal and admin, and applies targeted per-view fixes where markup is local (notably the document/folder tables).

The accessibility bar is WCAG 2.1 Level AA. It is understood and stated explicitly throughout this spec that **full WCAG conformance cannot be automatically verified**. Automated tooling and code review catch a subset of issues; genuine conformance still requires manual testing with assistive technologies and expert accessibility review.

Stack: Laravel 13 / PHP 8.3, Blade templates, Tailwind CSS, Alpine.js. Single CSS entry at `resources/css/app.css`. Existing visual language uses square styling (`rounded-none`), a black/white/gray palette, and Tailwind utility classes.

## Glossary

- **WCAG 2.1 AA**: The Web Content Accessibility Guidelines version 2.1, conformance Level AA, used as the accessibility bar for this feature.
- **Accessible Name**: The programmatically determined name a control exposes to assistive technologies, provided via `aria-label`, visually hidden text, or associated markup rather than a `title` attribute alone.
- **Icon-Only Control**: An interactive control (such as view-in-browser, download, up-one-level, or a dropdown chevron trigger) that communicates its purpose only through an icon and has no visible text label.
- **Focus-Ring Convention**: The shared styling approach for keyboard focus that replaces `focus:outline-none` with a visible `focus-visible:ring` indicator across shared components.
- **Touch Target**: The effective tappable area of an interactive control, targeted at approximately 44x44 CSS pixels or larger on touch viewports.
- **Shared Component**: A reusable Blade component (buttons, nav links, dropdown links, text inputs, empty states) rendered by both portal and admin views, where a single change propagates everywhere it is used.
- **Portal View**: A customer-facing Blade template in the portal area of the application.
- **Admin View**: An admin-facing Blade template that renders shared components alongside portal views.
- **Empty State**: The consistent pattern (icon, heading, supporting sentence) shown when a list or collection has no items.
- **Em-Dash Placeholder**: A legitimate em-dash used as an empty-value placeholder (such as `?: '—'`) or heading separator that must be preserved during the copy-consistency pass.

## Requirements

### Requirement 1: Visible Focus Indicators

**User Story:** As a keyboard user, I want a clearly visible focus indicator on every interactive control, so that I always know where I am on the page.

#### Acceptance Criteria

1. WHEN an interactive control (link, button, input, dropdown trigger) receives keyboard focus THEN the system SHALL render a visible focus indicator with sufficient contrast against its background.
2. WHERE a control currently uses `focus:outline-none` THEN the system SHALL pair it with a replacement visible focus ring (e.g. `focus-visible:ring`) rather than removing the indicator outright.
3. WHEN focus styling is applied to a shared component (buttons, nav links, dropdown links, text inputs) THEN the change SHALL propagate consistently to every portal and admin view that renders that component.

### Requirement 2: Accessible Names on Icon-Only Controls

**User Story:** As a screen reader user, I want every icon-only control to announce its purpose, so that I can operate the interface without seeing the icons.

#### Acceptance Criteria

1. WHEN an icon-only control (view-in-browser, download, up-one-level, dropdown chevron trigger) is rendered THEN the system SHALL provide an accessible name via `aria-label` or visually hidden text.
2. WHEN a control communicates meaning only through an icon THEN the decorative SVG SHALL be marked `aria-hidden="true"` and the accessible name SHALL come from the control, not the SVG.
3. WHEN an icon-only control also carries a `title` attribute THEN the system SHALL additionally provide a programmatic accessible name (title alone is not a reliable accessible name).

### Requirement 3: Accessible Mobile Navigation Toggle

**User Story:** As a mobile screen reader user, I want the hamburger menu button to announce its purpose and expanded/collapsed state, so that I understand the navigation control.

#### Acceptance Criteria

1. WHEN the mobile navigation toggle is rendered THEN the system SHALL give it an `aria-label` describing its purpose.
2. WHEN the mobile navigation panel is open or closed THEN the toggle SHALL reflect the state via `aria-expanded` bound to the Alpine `open` state.
3. WHERE the toggle controls a specific panel THEN the toggle SHOULD reference that panel via `aria-controls`.
4. WHEN the toggle receives keyboard focus THEN it SHALL show a visible focus indicator (per Requirement 1).

### Requirement 4: Adequate Touch Target Size

**User Story:** As a touch device user, I want tap targets to be large enough to hit reliably, so that I can operate controls without mis-taps.

#### Acceptance Criteria

1. WHEN an interactive control is rendered on a touch viewport THEN its effective touch target SHALL be approximately 44x44 CSS pixels or larger.
2. WHERE row action controls exist in the document/folder tables THEN they SHALL meet the touch target size on small screens.

### Requirement 5: Touch-Reachable Row Actions

**User Story:** As a touch device user, I want document and folder row actions to be reachable without hovering, so that I can view and download files on a phone.

#### Acceptance Criteria

1. WHEN a document row is rendered on a touch device THEN its actions (view, download) SHALL be reachable without a hover interaction.
2. WHERE actions currently rely on `opacity-0 group-hover:opacity-100` THEN the system SHALL make them always visible on small screens (or expose them via an explicit menu), while an optional hover reveal MAY remain on pointer devices.
3. WHEN row actions are focused via keyboard THEN they SHALL be visible regardless of hover state.

### Requirement 6: Phone-Friendly Document/Folder Rows

**User Story:** As a mobile user, I want document metadata to be visible on small screens, so that I can identify files without a desktop layout.

#### Acceptance Criteria

1. WHEN the folder view is rendered on a small screen THEN essential metadata (type, size, modified date) SHALL be presented in a phone-friendly layout rather than hidden via `hidden sm:block`.
2. WHEN metadata is presented on small screens THEN it SHALL be laid out so that name and key details remain legible (e.g. stacked secondary metadata line).
3. WHERE the desktop column-header row uses `hidden sm:flex` THEN the small-screen layout SHALL remain understandable without that header.

### Requirement 7: File-Type Icons for Documents

**User Story:** As a portal user, I want documents to show a file-type icon, so that I can scan a folder and recognize file kinds quickly.

#### Acceptance Criteria

1. WHEN a document row is rendered THEN the system SHALL display an icon derived from the document's file type or extension.
2. WHEN the file type cannot be determined THEN the system SHALL fall back to a generic document icon.
3. WHEN a file-type icon is rendered THEN it SHALL be decorative (`aria-hidden="true"`) and SHALL NOT be the sole conveyor of the file type (the type is also available as text).

### Requirement 8: Consistent Empty States

**User Story:** As a portal user, I want empty states to look and read consistently, so that the interface feels coherent.

#### Acceptance Criteria

1. WHEN a list or collection is empty THEN the system SHALL render an empty state following one consistent pattern (icon, heading, supporting sentence).
2. WHEN empty-state copy is written THEN it SHALL use consistent phrasing across portal views.
3. WHERE an empty-state pattern is centralized in a shared component THEN portal views SHALL use it so the pattern stays consistent.

### Requirement 9: Consistent Headings and Copy

**User Story:** As a portal user, I want headings and labels to be consistent, so that the same concept is named the same way throughout.

#### Acceptance Criteria

1. WHEN a page names the customer projects area THEN the page header and the in-page `h1` SHALL use consistent wording (resolve the "My Projects" header vs "Your projects" h1 mismatch).
2. WHEN headings and empty-state copy are standardized THEN legitimate em-dash usage SHALL be preserved (empty-value placeholders such as `?: '—'` and heading separators SHALL NOT be altered).
3. WHEN the consistency pass is applied THEN it SHALL be limited to concrete inconsistencies and standard headings/empty-state copy, and SHALL NOT perform a full voice/tone rewrite.

### Requirement 10: Honest Verification Scope

**User Story:** As a maintainer, I want the verification approach to be honest about what can and cannot be automatically checked, so that we do not claim guaranteed WCAG compliance.

#### Acceptance Criteria

1. WHEN the verification approach is documented THEN it SHALL state explicitly that full WCAG 2.1 AA conformance cannot be auto-verified.
2. WHEN verification is described THEN it SHALL distinguish automatable checks (presence of accessible names, `aria-expanded` binding, absence of unpaired `focus:outline-none`) from checks requiring manual assistive-technology testing and expert review.
3. WHEN the work is reported THEN it SHALL avoid claiming guaranteed compliance and SHALL list the manual steps still required.
