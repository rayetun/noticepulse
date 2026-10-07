# NoticePulse — Premium Editor & Templates Build Plan

_Planned: 2026-10-05 · Base version: 2.1.5 · Target release: 2.2.0 (non-breaking, feature)_
_Verified live in WP Local (`pinspot-dev.local`, WP 7.1.2, PHP 8.2) by walkthrough of all tabs._

## Objective
Make the bar editor and template library feel like a paid product: no-reload saving,
a true WYSIWYG preview, show/hide + styling for every element, modern templates, SVG
icons, and a clean dashboard free of other plugins' notices — **without any existing user
losing data or having a bar change how it renders.**

Decisions confirmed with product owner:
- **Live preview:** true WYSIWYG (renders each bar type for real, + device toggle).
- **Save:** AJAX, no reload, inline toast; keep POST handler as no-JS fallback.
- **Per-element control:** full (every visible element: show/hide + styling where it fits).
- **Templates:** a mix of bold/graphical and clean/minimal; light + dark pairs.

---

## The golden constraint (unchanged)
Existing bars live in `wp_noticepulse_bars` + `bar_meta` JSON. Every change below is
**additive**: new JSON keys and new options only — no renamed/dropped columns, no
destructive migration. **Absence of a new flag = today's exact behavior.** An existing bar
opened in the new editor shows every element enabled (matching how it renders now); the
user can then toggle. Nothing regresses for the 50+ installs.

---

## Live findings that drive the plan
| # | Finding | Severity |
|---|---|---|
| 1 | Live preview ignores bar type — Countdown showed a plain message+CTA, no timer. Same for email/coupon/carousel/GDPR. | High (perceived quality) |
| 2 | Save = full-page POST + redirect (no AJAX). | High (feels slow) |
| 3 | Bar-type & tab icons are OS emoji — inconsistent, clip-art look. | Medium |
| 4 | Color pickers are stock WP iris — "free plugin" tell. | Medium |
| 5 | 17 templates all render as the same flat solid-bar shape; no variety. | Medium |
| 6 | No per-element enable/disable (only countdown units). | Feature gap |
| 7 | Third-party `admin_notices` crowd our screens (site runs ~10 plugins). | Medium |
| ✓ | Tab switching is instant JS, no reload — keep. Bar-type section swap works — keep. Colors update preview live — keep. | (good) |

---

## Architecture decisions

### A. Per-element model (`bar_meta.elements`)
New canonical sub-key. Each element: `{ enabled, ...style overrides }`.

```jsonc
"elements": {
  "icon":    { "enabled": true,  "value": "🚚" },
  "message": { "enabled": true },
  "cta":     { "enabled": true },
  "close":   { "enabled": true },
  // per-type parts: countdown units, carousel arrows/dots, gdpr decline, etc.
}
```

- **Backward compatibility:** readers use `isset($el['close']['enabled']) ? (bool)… :
  $bar->show_close` — i.e. fall back to the legacy column/meta when the new flag is absent.
  Old bars therefore render byte-for-byte as before.
- **Dual-write during transition:** the save handler keeps legacy columns
  (`show_close`, `cta_label`, countdown unit meta) in sync with `elements` so the public
  renderer keeps working while we migrate it. No big-bang renderer rewrite.

### B. WYSIWYG preview (single styling source, no server round-trip)
- Enqueue the **real** `public/css/noticepulse-public.css` into the editor, scoped to the
  preview shell, so the preview inherits production styles (WYSIWYG by construction).
- A new `admin/js/np-live-preview.js` builds the actual `.np-bar` DOM from live form
  values, per bar type, reusing real class names.
- Dynamic behaviors (countdown ticking, carousel rotating, coupon copy, email field)
  run by giving each public feature script a small "init against this node" entry point,
  reused by the preview. One behavior source, two mount points (front-end + preview).
- Add a **desktop/mobile** preview toggle in the sidebar.

### C. AJAX save (no reload)
- Refactor the sanitize/build logic out of `handle_save_bar()` into a shared
  `build_bar_data_from_post()` so the POST handler **and** a new
  `wp_ajax_noticepulse_save_bar` use identical validation (DRY, no divergence).
- JS intercepts submit → posts → shows toast → on new-bar success, sets `bar_id` and
  `history.replaceState` to the edit URL (no navigation). Nonce + capability unchanged.
- The existing `admin-post` POST handler stays as the no-JS fallback.

### D. Icons
Replace emoji with an inline SVG set (bar types + tabs + element toggles) via a small PHP
helper / sprite. Accessible labels preserved.

### E. Templates (ties to earlier roadmap §1)
- **Single source of truth:** generate modal cards from
  `NoticePulse_Templates::get_all_templates()`; delete the duplicate registry in
  `_templates-modal.php`. Preview always equals result.
- Extend the template schema to include the `elements` block + full design block, so
  applying a template yields a fully-formed, per-element-toggleable bar.
- **Fix the confirmed carousel bug:** speed/transition never apply (JS uses a `<select>`
  helper on a number input + radio group).
- New template set: **mix** of graphical (gradients, icons) and minimal, with light/dark
  pairs; contrast-checked to pass WCAG AA by default.
- Template cards re-rendered through the real-bar preview (true mini-previews).

### F. Notice suppression
On NoticePulse screens only, strip third-party `admin_notices` / `all_admin_notices`
(late priority) while **preserving our own** notices (rendered through our own path).
Scoped strictly to our pages — we never touch other plugins' screens.

---

## Phased delivery (each phase independently shippable & data-safe)

| Phase | Scope | Risk |
|---|---|---|
| **1 — Save & data foundation** | Shared sanitizer, AJAX no-reload save + toast, introduce `elements` meta with backward-compatible reads. | Low |
| **2 — WYSIWYG preview** | Scoped public CSS in admin, real-bar preview builder, per-type behaviors, device toggle. | Low–med |
| **3 — Per-element controls** | Show/hide + styling UI for every element across all 7 types; renderer honors flags with legacy fallback. | Med |
| **4 — Templates overhaul** | Single source of truth, new premium set (mix + light/dark), schema with `elements`, carousel-bug fix, real-render cards. | Low |
| **5 — Icons, polish, notices** | SVG icon set, restyled pickers/preset swatches, spacing/typography, sticky save bar, admin-responsive, notice suppression. | Low |
| **6 — QA & release** | Full pass on all 7 types in WP Local, Plugin Check + PHPCS, upgrade-path test from a 2.1.5 bar, bump to 2.2.0, readme + changelog ("All existing bars preserved"). | — |

**Pro seam (build later, not now):** per-element styling, premium template packs, and
WYSIWYG device modes are natural Pro upgrade lines. I'll keep the free/premium boundary
clean (via `class-noticepulse-features.php`) as I go — Freemius is already present in this
environment — but gate nothing that is free today.

---

## What I need to verify as I build (in WP Local)
- Open a **pre-existing 2.1.5 bar** in the new editor → confirm identical render before any
  change (the data-safety proof).
- Each bar type's preview matches the real front-end output.
- AJAX save round-trips and the no-JS fallback still works.
- Plugin Check + PHPCS clean before release.

---

## Recommended start
**Phase 1** — it's the backbone (shared sanitizer + AJAX save + `elements` scaffold),
low-risk, and unblocks 2–5. I'll build it on a feature branch, test against a real
existing bar in `pinspot-dev`, and show you before moving on.
