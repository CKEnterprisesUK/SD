# Implementation Plan: Portal UI Accessibility Improvements

## Overview

This plan implements presentation-layer accessibility, mobile-usability, and content-consistency fixes across the SiteDesk portal. Work follows the design's centralize-then-localize strategy: shared Blade component fixes land first (so they propagate to portal + admin), then new shared components are added, then per-view fixes are applied to navigation and the folder/library/projects views. Tests validate the code-level correctness properties; a final task runs the build/test suite and documents the manual WCAG steps that cannot be automated.

Stack: Laravel 13 / PHP 8.3, Blade, Tailwind, Alpine.js. No database or model changes. Style stays square (`rounded-none`), black/white/gray. Legitimate em-dash placeholders (`?: '—'`) are preserved throughout.

## Tasks

- [x] 1. Apply the focus-ring convention to shared components
  - [x] 1.1 Update shared buttons, links, and inputs to the focus-ring convention
    - Replace every unpaired `focus:outline-none` in `resources/views/components/primary-button.blade.php`, `secondary-button.blade.php`, `danger-button.blade.php`, `nav-link.blade.php`, `responsive-nav-link.blade.php`, `dropdown-link.blade.php`, and `text-input.blade.php` with the convention `focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-900 focus-visible:ring-offset-2`
    - Migrate the pre-existing indigo rings on the button components to `ring-gray-900` for palette consistency
    - Confirm changes propagate wherever these components are rendered (portal + admin) with no visual regression on mouse click (use `focus-visible`)
    - _Requirements: 1.1, 1.2, 1.3_

  - [ ]* 1.2 Write static assertion for focus-ring pairing across affected Blade files
    - **Property 1: Focus indicators are never removed without replacement**
    - **Validates: Requirements 1.1, 1.2, 1.3**
    - Scan the affected shared components (and later the affected views); for every `focus:outline-none` occurrence assert a `focus-visible:ring` utility is present in the same class string
    - Cover the `<a>` vs `<button>` rendering edge case

- [x] 2. Create the `x-icon-button` shared component
  - [x] 2.1 Implement `resources/views/components/icon-button.blade.php`
    - Accept `label` (required accessible name), `as` ('a' | 'button', default 'a'), and `href` props
    - Render `aria-label="{{ $label }}"`, apply the focus-ring convention and a touch box (`min-w-[44px] min-h-[44px]`, `inline-flex items-center justify-center`), keeping the square black/white/gray style
    - Merge caller `$attributes` (so callers can add `target`, `rel`, hover-reveal classes) and render the SVG via `$slot` (expected `aria-hidden="true"`)
    - _Requirements: 2.1, 2.2, 2.3, 4.1, 4.2_

  - [ ]* 2.2 Write render assertion for icon-button accessible name and touch sizing
    - **Property 2: Icon-only controls have a programmatic accessible name**
    - **Validates: Requirements 2.1, 2.2, 2.3**
    - Render as both `<a>` and `<button>`; assert `aria-label` is present and that the sizing classes (`min-w-[44px] min-h-[44px]`) are applied (proxy for Requirement 4.1/4.2)

- [x] 3. Create the `x-document-icon` shared component
  - [x] 3.1 Implement `resources/views/components/document-icon.blade.php`
    - Accept `name` and `mime` props; classify into pdf / image / doc / sheet / archive / generic using extension and mime (mirroring the existing `$fileKind` intent in `portal/folder.blade.php`)
    - Render exactly one `<svg aria-hidden="true">` per kind; `generic` is the fallback glyph for unknown/empty/missing type
    - _Requirements: 7.1, 7.2, 7.3_

  - [ ]* 3.2 Write property test for document icon mapping and fallback
    - **Property 5: Every document maps to a rendered file-type icon**
    - **Validates: Requirements 7.1, 7.2, 7.3**
    - Min. 100 iterations; generate random mime types and filename extensions including unknown/empty/missing; assert exactly one icon element renders and that the generic glyph is used when type is undetermined
    - Tag: **Feature: portal-ui-accessibility-improvements, Property 5: Every document maps to a rendered file-type icon**

- [x] 4. Create the `x-empty-state` shared component
  - [x] 4.1 Implement `resources/views/components/empty-state.blade.php`
    - Accept `heading` (required) and `message` (optional); render decorative icon slot (`aria-hidden="true"`, default glyph if omitted), heading, and optional supporting sentence in the shared centered structure
    - Match the existing spacing/typography of the current portal empty states
    - _Requirements: 8.1, 8.2, 8.3_

  - [ ]* 4.2 Write render assertion for the empty-state pattern
    - **Property 6: Empty states render the shared three-part pattern**
    - **Validates: Requirements 8.1, 8.2, 8.3**
    - Render with and without `message`; assert decorative icon + heading are present and the supporting sentence appears only when provided

- [x] 5. Checkpoint - shared components complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Fix the mobile navigation toggle and dropdown triggers in `layouts/navigation.blade.php`
  - [x] 6.1 Make the hamburger toggle accessible
    - Add `aria-label="Toggle navigation menu"`, bind `:aria-expanded="open.toString()"` to the existing Alpine `open` state, add `aria-controls="mobile-navigation"`, and apply the focus-ring convention + touch box
    - Mark the hamburger SVG `aria-hidden="true"`; add `id="mobile-navigation"` to the mobile panel container
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 6.2 Give the dropdown/user-menu chevron triggers accessible names and focus rings
    - Apply the focus-ring convention to the "Contractors" admin dropdown trigger and the user-menu chevron trigger; add `aria-label="Open user menu"` to the chevron-only trigger and mark its SVG `aria-hidden="true"`
    - _Requirements: 2.1, 2.2, 1.1_

  - [ ]* 6.3 Write example unit tests for hamburger ARIA
    - Assert `aria-label` present, `:aria-expanded` bound to `open`, and `aria-controls` references `mobile-navigation`
    - _Requirements: 3.1, 3.2, 3.3_

- [x] 7. Make document/folder rows responsive and touch-reachable in `portal/folder.blade.php`
  - [x] 7.1 Add file-type icons and phone-friendly rows
    - Render `x-document-icon` at the start of each document row; keep the desktop columns (`hidden sm:block`) unchanged
    - Add a phone-only stacked metadata line (`sm:hidden`) showing type · size · modified beneath the name; give subfolder rows the same phone-only "Folder" line
    - Preserve the `—` placeholders from `$formatBytes(null)` and the date fallbacks
    - _Requirements: 6.1, 6.2, 6.3, 7.1_

  - [x] 7.2 Convert row actions and the up-one-level control to `x-icon-button`
    - Replace row-action anchors and the toolbar up-one-level control with `x-icon-button` carrying descriptive labels (e.g. "View {name} in browser", "Download {name}", "Up one level" / "Back to library")
    - Make actions always visible on small screens; scope any hover reveal to `sm:` and pair it with `sm:group-focus-within:opacity-100` so keyboard focus reveals it
    - Keep the existing `Route::has(...)` guards so controls only render when their route exists
    - _Requirements: 4.2, 5.1, 5.2, 5.3, 2.1_

  - [ ]* 7.3 Write property test for row actions and small-screen metadata
    - **Property 3: Row actions are reachable without hovering on small screens**
    - **Property 4: Essential document metadata is present in the small-screen layout**
    - **Validates: Requirements 5.1, 5.2, 5.3, 6.1, 6.2, 6.3**
    - Min. 100 iterations; generate folders with random collections of documents/subfolders, render the view, and assert for every row that actions are present (not solely `opacity-0 group-hover:opacity-100`) and that type/size/modified text appears in the small-screen layout
    - Tag: **Feature: portal-ui-accessibility-improvements, Property 3: Row actions are reachable without hovering on small screens** and **Feature: portal-ui-accessibility-improvements, Property 4: Essential document metadata is present in the small-screen layout**

  - [ ]* 7.4 Write render assertion for em-dash placeholder preservation
    - **Property 7: Legitimate em-dash placeholders are preserved**
    - **Validates: Requirements 9.2**
    - Render rows with null size/date inputs and assert the `—` placeholders still render

- [x] 8. Apply empty-state and heading consistency to portal views
  - [x] 8.1 Route empty branches through `x-empty-state`
    - Update `portal/folder.blade.php` and `portal/library.blade.php` empty branches to render `x-empty-state`, keeping their existing tone ("No folders available" / "This folder is empty")
    - Update `customer/projects/index.blade.php` to adopt the shared three-part empty-state shape
    - _Requirements: 8.1, 8.2, 8.3_

  - [x] 8.2 Resolve the customer projects heading mismatch
    - Align the page header and in-page `h1` in `customer/projects/index.blade.php` to the nav wording ("My Projects"), keeping the supporting sentence
    - Preserve legitimate em-dashes; limit the pass to concrete inconsistencies (no voice/tone rewrite)
    - _Requirements: 9.1, 9.2, 9.3_

  - [ ]* 8.3 Write example unit test for heading consistency
    - Assert the customer projects page header and `h1` use the same agreed wording
    - _Requirements: 9.1_

- [x] 9. Checkpoint - per-view fixes complete
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Run the suite and document manual WCAG verification scope
  - Run the project's build/test suite (e.g. `npm run build` for assets and `php artisan test` / `phpunit` for the test suite) and fix any failures introduced by these changes
  - Add or update verification notes that: (a) state full WCAG 2.1 AA conformance cannot be auto-verified; (b) distinguish the automatable checks (accessible-name presence, `aria-expanded` binding, no unpaired `focus:outline-none`, icon fallback, metadata presence) from the manual checks; (c) list the outstanding manual steps (screen-reader passes, keyboard-only focus-order/contrast checks, real ~44px touch measurement, focus-ring contrast) and explicitly avoid any claim of guaranteed compliance
  - _Requirements: 10.1, 10.2, 10.3_

## Notes

- Tasks marked with `*` are optional test sub-tasks and can be skipped for a faster MVP.
- Each task references specific requirements/properties for traceability.
- Checkpoints ensure incremental validation after the shared-component and per-view stages.
- Property tests (Properties 3, 4, 5) run a minimum of 100 iterations and are tagged with the design feature/property. Properties 1, 2, 6, 7 are static/render assertions since they are single-render invariants over template source or output.
- Perceptual acceptance criteria (real focus-ring contrast, true 44px rendered targets, screen-reader announcement quality, reading/focus order) are not automatable and are covered by the manual verification in task 10.
- No database or model changes: this feature is presentation-layer only. Legitimate em-dash placeholders (`?: '—'`) are preserved.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "3.1", "4.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "3.2", "4.2", "6.1", "6.2"] },
    { "id": 2, "tasks": ["6.3", "7.1"] },
    { "id": 3, "tasks": ["7.2", "8.1", "8.2"] },
    { "id": 4, "tasks": ["7.3", "7.4", "8.3"] }
  ]
}
```
