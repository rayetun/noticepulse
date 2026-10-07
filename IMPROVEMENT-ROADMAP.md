# NoticePulse — Improvement Roadmap

_Review date: 2026-10-04 · Reviewed version: 2.1.5 · 50+ active installs_

This roadmap is based on a full read of the codebase (data model, save/sanitization
flow, public renderer, admin, and the template system). It is ordered by **value vs.
risk**, and every item is checked against one rule:

> **Golden rule — never spoil existing users' data.**
> Existing bars live in the `wp_noticepulse_bars` table. Most improvements below touch
> only the *admin editor* or the *template picker*, which are apply-on-demand — they
> cannot alter saved bars. Where a change touches storage, it is explicitly called out
> and designed to be **additive only** (new JSON keys / new options, never renaming or
> dropping columns).

Decisions captured for this roadmap:
- **Monetization:** a paid Pro version is planned later → keep the free/premium seam,
  remove nothing. Pro-prep notes are in §5.
- **Template priorities:** (1) fix sync/accuracy bugs, (2) more & better templates,
  (3) user "Save as template".

---

## 0. What's already good (don't rewrite these)

A fair bit is solid for a free plugin — worth knowing so we don't "improve" working code:

- **`bar_meta` LONGTEXT JSON column** is the right design. New feature fields go into
  JSON, so new features need **no schema migration** and can't corrupt old rows. This is
  exactly what makes most of this roadmap data-safe.
- **Security hygiene is largely correct:** nonces on every admin-post/AJAX action,
  `current_user_can( 'manage_options' )` checks, `$wpdb->prepare()` with `%i` identifiers,
  `wp_kses()` on messages, `sanitize_hex_color()` on colors, allowlists for enum fields.
- **Conditional asset loading** — public CSS/JS only enqueue when a bar is actually
  eligible on the page.
- **Hook-based feature architecture** (`noticepulse_active_bars`,
  `noticepulse_bar_data_attributes`, `noticepulse_bar_inline_styles`,
  `noticepulse_save_bar_data`) is a clean seam — and the natural place to split Pro later.

---

## 1. Template system — fix sync & accuracy  ⭐ highest priority, zero data risk

### 1a. Kill the dual registry (root cause of "preview ≠ result")

Templates are currently defined **twice**:

| File | Holds | Used for |
|---|---|---|
| `includes/class-np-templates.php` | full field data (`fields` + `bar_meta`) | applying via AJAX |
| `admin/views/_templates-modal.php` | a second hand-copied `$np_template_registry` | drawing the preview cards |

The files even carry warning comments that they "MUST match exactly." They already
**don't** — e.g. the GDPR cards preview an Accept button color (`#7c5cfc`, `#22d3a5`) that
the real templates never set, so the picker shows something the applied bar won't produce.
Every new or edited template means editing two files in lockstep; that is the single
biggest source of template bugs.

**Fix:** make `NoticePulse_Templates::get_all_templates()` the only source of truth.
Rewrite `_templates-modal.php` to loop over that method and **derive** each preview card
from the template's own `fields` (message, `bg_color`, `text_color`, `btn_bg_color`,
`btn_txt_color`, `cta_label`). Delete the duplicate array.

- **Result:** preview always equals the applied bar. Adding a template = editing one file.
- **Data risk:** none — this is admin-screen rendering only.
- **Bonus:** derive the count instead of hardcoding "17 templates" in the modal header
  and readme (those drift every time you add one).

### 1b. Fix the carousel apply bug (confirmed)

In `admin/js/np-templates.js` (lines ~268–269), carousel speed and transition are applied
with `setSelectField()`:

```js
setSelectField( 'input[name="ticker_speed"]',      barMeta.ticker.speed );
setSelectField( 'input[name="ticker_transition"]', barMeta.ticker.transition );
```

But in `admin/views/edit-bar.php` those fields are **not** selects — `ticker_speed` is
`<input type="number">` and `ticker_transition` is a **radio group**. `setSelectField()`
bails unless it finds a matching `<option>`, so **carousel templates silently never apply
their speed or transition.** Replace with: set the number input directly, and
`.prop('checked', true)` + `.trigger('change')` on the correct transition radio.

### 1c. Make "preview color" fields honest for non-standard types

Standard/countdown/coupon templates map cleanly to the card. GDPR / email / call store
their button text and colors inside `bar_meta` (e.g. `gdpr.accept_label`), not in
`btn_bg_color`. Once 1a derives previews from real data, decide per type what the card's
"button" represents (e.g. GDPR → the Accept button label) so the preview is truthful
rather than a decorative guess.

### 1d. Minor apply-path polish (low priority, after 1a)

- `setColor()` guards on `$input.data('wpWpColorPicker')` — a brittle internal key; the
  `try/catch` hides failures so a template color may not visually refresh the picker
  widget even though the value is set. Verify against the current `wp-color-picker`.
- Design fields (`btn_radius`, `bar_padding`, `font_size`) are set on only *some*
  templates, so applying a template leaves stale values from the previous one. Give every
  template a complete design block (see 2b) so "apply" is a clean reset.

---

## 2. Templates — more & better  (data-safe; additive)

### 2a. Grow and rebalance the library
Current mix is announcement-heavy (5) and thin elsewhere (1 call template). Candidate
additions: Cyber Monday, Back to School, Low Stock / "Only N left", Shipping cutoff,
Webinar/Event countdown, Age-gate/consent, SMS opt-in, "We're hiring", Maintenance notice,
Review/rating request. Aim for 3–4 strong options per category.

### 2b. Standardize the template schema
Define one canonical shape every template fills completely — core fields **plus** a full
design block (`btn_radius`, `bar_padding`, `font_size`, `text_align`, `position`) **plus**
the type's `bar_meta`. Pairs with 1d so applying any template fully defines the bar.

### 2c. Light/dark and accessibility-aware pairs
Offer light + dark variants of popular templates, and sanity-check text/background
contrast so the defaults pass WCAG AA out of the box.

> All of §2 is new entries in the registry array — **no storage changes, no effect on
> existing bars.**

---

## 3. Templates — user "Save as template"  (additive storage, designed data-safe)

Let users save a configured bar as a reusable custom template.

- **Storage:** a single WordPress option, e.g. `noticepulse_user_templates` (array of
  template objects in the same canonical shape as §2b). **Do not** add columns to the bars
  table and **do not** touch existing rows.
- **Flow:** "Save as template" button on the editor → captures current form values →
  stores to the option. Custom templates appear in the picker in a "My Templates" category
  alongside built-ins, reusing the same apply path.
- **Lifecycle:** support rename/delete of custom templates; register the option in
  `uninstall.php` cleanup so a full uninstall removes it (respecting the existing
  uninstall behavior).
- **Guardrails:** cap count (e.g. 50) to keep the option small; sanitize on save with the
  same rules as `handle_save_bar()`; nonce + capability check on the save/delete actions.
- **Data risk:** none to existing bars — this only ever *reads* a bar's current form and
  *writes a separate option*.
- **Pro tie-in:** custom templates / a template cloud is a natural Pro upsell later (see §5).

---

## 4. Correctness & robustness (beyond templates)

Found during review — none are emergencies, ordered by impact:

1. **`$data_attrs` echoed unescaped** in `class-noticepulse-public.php` (`render_bar`,
   ~line 290, phpcs-ignored). It's assembled from escaped parts today, but any feature/Pro
   filter that appends an unescaped value becomes an output-escaping hole. Consider building
   attributes as an array and escaping centrally at output.
2. **Import does not validate `bar_meta` shape per bar type** — it accepts any JSON that
   decodes. Low severity (admin-only, capability-gated), but a malformed import could
   create a bar whose meta a feature class doesn't expect. Validate against known keys.
3. **`date_start` / `date_end` use `strtotime()` on raw input** then `gmdate()`. Timezone
   handling is loose (mixes site-local intent with GMT storage). Worth auditing so
   scheduled bars fire at the time the user expects.
4. **No automated tests.** Even a small PHPUnit suite around `NoticePulse_DB` (meta
   get/set round-trips, insert/update), save sanitization, and the template registry
   integrity (every template id resolvable, every type valid) would catch the §1 class of
   bugs permanently. `tests/` is already excluded from the build via `.distignore`.
5. **Run Plugin Check + PHPCS (WordPress-Extra)** before the next release and fix what they
   flag — this is the same bar WordPress.org reviews against.

---

## 5. Pro-version preparation (keep the seam; build later)

You've decided on a paid Pro later, so **remove nothing**. The current "Pro" naming is
already a usable seam:

- The filters in `class-noticepulse-public.php` and `noticepulse_save_bar_data` are the
  gate points — Pro features register on the same hooks the free bar types use.
- `class-noticepulse-features.php` already centralizes capability answers
  (`has_pro_analytics()` etc. currently return `true`/unlimited). This is the right place
  to later read real license state instead of hardcoded `true`.
- `limit-reached.php` and the "bar limit removed" vestiges can stay as scaffolding for a
  future Free-tier limit.

When you're ready, the **Freemius** path fits cleanly: free plugin on WordPress.org +
separate premium code gated behind `can_use_premium_code()`, with
`class-noticepulse-features.php` as the single licensing authority. (There's a dedicated
Freemius skill available for that work when the time comes.) **Do not** gate anything that
is currently free — existing users keeping today's features is itself part of the golden
rule.

---

## 6. Housekeeping & docs

- **Hardcoded template count** in the modal subtitle and readme ("17 templates") — derive
  it (ties to 1a).
- **Donate link** points to a personal Wise pay link — fine, just confirm it's current.
- **`README.md` vs `readme.txt`** — ensure the WordPress.org `readme.txt` stays the source
  of truth for users; keep dev notes in `README.md` (excluded from build).
- Keep the **"All existing bars preserved" / "Safe to update"** line in every Upgrade
  Notice — it's reassuring and, with this roadmap's constraints, stays true.

---

## Suggested sequence

| Phase | Work | Data risk | Why first |
|---|---|---|---|
| **1** | §1a single registry + §1b carousel bug + §1c truthful previews | none | Fixes the bug class you most feel; unblocks everything else |
| **2** | §1d apply polish + §2 more/better templates (canonical schema) | none | Cheap wins once the registry is single-source |
| **3** | §3 "Save as template" (new option, additive) | additive only | Headline new feature; self-contained |
| **4** | §4 correctness: escaping, import validation, dates | low | Hardening before Pro and before growth |
| **5** | §4 tests + Plugin Check/PHPCS pass | none | Locks in quality; required-quality bar for WP.org |
| **6** | §5 Pro seam prep (when you decide to monetize) | none to free users | Deliberate, separate project |

**Recommended next step:** Phase 1 — it's self-contained, carries no data risk, and
eliminates the template sync problem permanently. Say the word and I'll implement it.
