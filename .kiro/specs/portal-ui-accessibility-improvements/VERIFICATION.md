# Verification: Portal UI Accessibility Improvements

## Scope and honest disclaimer

This feature targets **WCAG 2.1 Level AA**. **Full WCAG 2.1 AA conformance cannot be
automatically verified.** Automated tooling, static scans, and Blade render assertions
catch only a *subset* of accessibility issues. Genuine conformance still requires manual
testing with assistive technologies and expert accessibility review.

**No claim of guaranteed WCAG 2.1 AA compliance is made here.** The changes remove several
well-known code-level anti-patterns and add accessible-name/state affordances, which is a
meaningful improvement — but the perceptual and behavioral qualities that determine real
conformance are covered only by the outstanding manual steps listed below.

_Validates: Requirements 10.1, 10.2, 10.3_

## Automated / build & test results

Run on the current branch with all accessibility changes applied:

- **Asset build** — `npm run build` (Vite): **PASS** (built in ~0.6s; `manifest.json`,
  `app-*.css`, `app-*.js` emitted, exit code 0).
- **Test suite** — `php artisan test`: **75 passed, 3 failed** (21,429 assertions).

### The 3 failures are pre-existing and unrelated to this feature

The failing tests are default Laravel scaffolding tests that do not match this
application's routing, and they fail identically on the pre-change baseline (verified by
stashing the view changes and re-running):

| Test | Reason | Related to a11y changes? |
| --- | --- | --- |
| `Auth\RegistrationTest::registration screen can be rendered` | `/register` returns 404 — no `register` route is defined in this app | No |
| `Auth\RegistrationTest::new users can register` | Same — registration is not enabled | No |
| `Feature\ExampleTest::the application returns a successful response` | `/` returns 302 (redirects to auth) instead of the scaffolding-expected 200 | No |

These pre-date and are independent of the presentation-layer accessibility work (which
touches only Blade views and shared components). They were not introduced by this feature
and are left as-is; enabling/removing the scaffolding tests is out of scope for this spec.

## What the automated checks *can* cover (subset)

These are the code-level invariants that static scans and Blade render/property tests can
assert (see the optional test sub-tasks in `tasks.md`, Properties 1–7 in `design.md`):

- **Accessible-name presence** on icon-only controls (`aria-label` / visually hidden text),
  with the decorative SVG marked `aria-hidden="true"` (Property 2).
- **`aria-expanded` binding** on the mobile navigation hamburger, bound to the Alpine
  `open` state, plus `aria-controls="mobile-navigation"` (Property tests for task 6).
- **No unpaired `focus:outline-none`** — every occurrence in the affected shared components
  and views is paired with a `focus-visible:ring-*` utility on the same class string
  (Property 1).
- **File-type icon fallback** — every document maps to exactly one rendered icon, with the
  generic glyph used for unknown/empty/missing types (Property 5).
- **Small-screen metadata presence** — type / size / modified appear in the phone layout
  (not solely inside `hidden sm:block`), and row actions are not gated solely by
  `opacity-0 group-hover:opacity-100` (Properties 3 and 4).
- **Empty-state structure** — decorative icon + heading + optional supporting sentence
  render through `x-empty-state` (Property 6).
- **Em-dash placeholder preservation** — `—` placeholders for null size/date still render
  (Property 7).
- **Touch-sizing classes present** — controls carry `min-w-[44px] min-h-[44px]` as a
  *proxy* for the touch-target requirement (the true rendered pixel size is manual).

## Outstanding manual checks (cannot be automated)

The following MUST be performed by a human with assistive technologies / measurement tools
before any conformance claim is made. Automated results above do **not** substitute for
these:

1. **Screen-reader passes** (e.g. VoiceOver, NVDA) confirming names, roles, and state
   announcements read sensibly — including the hamburger's expanded/collapsed state and the
   document/folder row actions.
2. **Keyboard-only navigation** confirming a logical focus order across the portal and that
   every focus indicator is *actually visible* in practice.
3. **Real touch-target measurement** (~44×44 CSS px) on representative devices — the class
   utilities are only a proxy; rendered size depends on surrounding layout.
4. **Focus-ring and text color-contrast verification** against the actual backgrounds the
   controls render on (the `ring-gray-900` ring vs. real page backgrounds).

These items, plus overall reading/focus order across assistive technologies, are the
remaining work required for a defensible WCAG 2.1 AA conformance statement.
