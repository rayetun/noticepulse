# NoticePulse 2.3.0 — Build Plan

> Status: **spec / not started.** Planning doc (excluded from the shipped ZIP via `.distignore`).
> Golden rule (unchanged): **additive only.** Existing bars must render byte-identical. A new flag absent
> = prior behavior. No schema migration — everything new lives in the `bar_meta` JSON column.

## Scope (all free)
1. **Marquee / News Ticker** bar — continuous scroll (horizontal) + optional vertical ticker.
2. **Extension hook surface** — §4 of `WOOCOMMERCE-PLAN.md` (#1–#6; #7 dormant stub).
3. **Basic WooCommerce free-shipping bar** — `{free_shipping_left}` token, live cart.
4. **Header-based country targeting** — replace the ipapi.co external call in free.
5. **Compliance-wording bundle** — fold in branch `fix/compliance-language` (readme-only).

Ship order below is also the recommended PR order. 1→2 are prerequisites for 3.

---

## How the plugin renders today (grounding — verified in code)

- `class-noticepulse-public.php::render_bar()` emits a **generic** node: icon + `.np-bar__message`
  (`$bar->message` through `wp_kses`, line ~313) + CTA. There is **no server-side `switch($type)`.**
- Behavior/animation per type is layered by **feature classes** (`includes/class-np-*.php`, loaded via
  `file_exists()` in `noticepulse.php`) that hook three extension points:
  `noticepulse_bar_data_attributes` (emit `data-*`), `noticepulse_bar_inline_styles`, and
  `wp_enqueue_scripts` (ship the type's JS/CSS). `class-np-ticker.php` is the reference implementation.
- Bar types are validated against **two hardcoded allow-lists** in `class-noticepulse-admin.php`:
  the save path (`$valid_types`, ~line 489) and the import path (`$valid_bar_types`, ~line 858).
- `type_class = 'np-bar--type-' . bar_type` is already on the wrapper (line ~279) — free CSS/JS hook.

**Implication:** a new first-party type = new feature class + CSS/JS + registering the type string.
No core-render surgery. This is why 2.3.0 is low-risk.

---

## 1. Marquee / News Ticker bar

New feature file `includes/class-np-marquee.php`, modeled on `class-np-ticker.php`. Add
`includes/class-np-marquee.php` to the `$feature_files` array + a `class_exists('NoticePulse_Marquee')`
boot line in `noticepulse.php`.

- **Type string:** `marquee`. Register via the new filter (§2) **and** add to both hardcoded arrays
  (belt-and-braces; first-party types shouldn't depend solely on the filter).
- **Distinct from `ticker`** (which is the fade/slide **carousel**). Marquee = continuous CSS transform
  scroll; no "slides".
- **Meta** (`bar_meta.marquee`): `direction` (left|right|up), `speed` (px/s, default ~60), `gap` (px),
  `pause_hover` (bool), `repeat` (int, auto-duplicate content to fill width), `separator` (string, e.g. `•`).
- **Data attrs:** hook `noticepulse_bar_data_attributes` → `data-marquee="1" data-marquee-dir
  data-marquee-speed data-marquee-pause ...`.
- **JS** `assets/js/np-marquee.js`: clone the message node N times into a track, animate with
  `requestAnimationFrame` transform (or CSS `@keyframes` with a computed duration); pause on hover;
  honor `prefers-reduced-motion` (fall back to static). Enqueue only when a marquee bar is active
  (`maybe_enqueue`, same guard pattern as ticker).
- **CSS** `assets/css/np-marquee.css`: `.np-bar--type-marquee .np-bar__content { overflow:hidden }`,
  track flex, `white-space:nowrap`. Vertical variant for `direction: up`.
- **Editor:** add a Marquee option to the type picker + a small settings group (direction/speed/gap/
  pause/separator) via the editor extension slot (§2 #5) — same way ticker injects its fields.
- **Accessibility:** wrap scrolled text with `aria-live="off"`; provide a static, readable DOM copy.

**Additive check:** existing bars have no `marquee` meta → feature class returns early → unchanged.

---

## 2. Extension hook surface

Ship these as stable, documented hooks (they let Marquee + the Woo bar be clean modules and keep a
future Pro add-on cheap). Map to `WOOCOMMERCE-PLAN.md` §4.

1. **`noticepulse_valid_bar_types` filter.** Replace both hardcoded arrays
   (`class-noticepulse-admin.php` ~489 and ~858) with:
   ```php
   $valid_types = apply_filters( 'noticepulse_valid_bar_types', array(
       'standard','gdpr','ticker','click_to_call','countdown','email_capture','coupon_copy',
   ) );
   ```
   Keep the `in_array( …, true )` validation. First-party `marquee` and `woo_free_shipping` are added
   via this filter from their own feature classes.
2. **Custom-type render hook.** In `render_bar()`, before the default `.np-bar__message` output, add:
   ```php
   $np_handled = apply_filters( 'noticepulse_render_bar_type', false, $np_type, $bar );
   if ( false === $np_handled ) { /* existing default message/CTA output */ }
   ```
   Lets a module fully own its markup. Marquee does **not** need this (it reuses the message node);
   the Woo bars may. Default `false` = today's behavior exactly.
3. **Merge-tag / token API.** Wrap the message output (line ~313) with:
   ```php
   $np_message = apply_filters( 'noticepulse_bar_message', $bar->message, $bar );
   ```
   A `noticepulse_message_tokens` registry helper resolves `{token}` → value. Core ships **zero**
   tokens (so existing `{...}` literals are untouched unless a module registers that exact token).
   Escaping stays: still run through `wp_kses( …, allowed_message_tags() )` after resolution.
4. **Client-side JS event bus.** Tiny `window.NoticePulse` in the existing public JS:
   `NoticePulse.on(event, cb)`, `NoticePulse.emit(event, detail)`, and a `refreshBar(id, html)` helper.
   Fires `np:bars-rendered`. Modules (free-shipping bar) use `np:cart-updated` to re-render text.
   Namespaced; no global leakage beyond `window.NoticePulse`.
5. **Editor extension slots.** `do_action( 'noticepulse_editor_fields_{tab}', $bar )` inside the editor's
   design/content/triggers tabs so modules inject fields. Marquee's own settings use this (dogfood it).
6. **Analytics event hook.** `apply_filters` on the analytics write path + a documented
   `NoticePulse_Analytics::record_event( $bar_id, $type, $meta )` seam so a module can log
   revenue/order events later (foundation only; no Woo code in core).
7. **Upsell anchors — dormant stub.** Reserve a settings-tab slot + one `plugin_action_links` slot,
   rendered empty while no Pro exists. No nags. (Costs ~nothing; ready if Pro is ever built.)

**Docs:** add a short `HOOKS.md` (dev-only, `.distignore`'d) documenting #1–#6 signatures.

---

## 3. Basic WooCommerce free-shipping bar (free)

New module `includes/class-np-woo-shipping.php`, loaded **only if** `class_exists('WooCommerce')`
(guard at top; the `file_exists` loader already tolerates absence). Uses the §2 hooks — **no core edits
beyond what §2 adds.**

- **Type:** `woo_free_shipping` (registered via `noticepulse_valid_bar_types`).
- **Token:** registers `{free_shipping_left}` (and `{free_shipping_goal}`, `{cart_total}`) via the §2 #3
  token API. v1 reads the **free-shipping method threshold** from the current cart's packages; smart
  shipping-zone logic is deferred (that's the §3-E/A depth in `WOOCOMMERCE-PLAN.md`, later).
- **Live cart:** small `assets/js/np-woo-shipping.js` listens for Woo's `updated_cart_totals` /
  `added_to_cart` fragments events, calls an AJAX endpoint (`wp_ajax[_nopriv]_np_free_shipping`) that
  returns the resolved message, and uses `window.NoticePulse.refreshBar()` (§2 #4) to swap text.
- **When goal met:** show the configured "You've unlocked free shipping 🎉" message (meta field).
- **Additive:** absent WooCommerce → module never loads → zero effect on non-Woo sites.

---

## 4. Header-based country targeting (replace ipapi.co)

In `includes/class-np-geo-target.php` (existing). **Additive, no behavior change for saved bars.**

- Add a header resolver that reads, in order: `CF-IPCountry` (Cloudflare), `CloudFront-Viewer-Country`,
  `X-Country-Code` / common host headers, then WP's locale as last resort. Returns an ISO-3166 alpha-2
  or empty.
- **Keep ipapi.co as an opt-in fallback** behind a setting, default **off**, so the privacy default is
  "no third-party call." Existing users who relied on ipapi.co: detect prior geo usage and keep their
  behavior (fallback **on**) via a one-time migration flag in options — so nobody's targeting silently
  breaks. New installs default to headers-only.
- Document in readme: country targeting now works header-first (Cloudflare/host), no third-party IP
  lookup by default — this is also a **compliance win** (no data sent to ipapi.co).

**Decision to confirm at build time:** the exact "keep existing users on fallback" migration trigger
(e.g. any saved bar with a country rule → set fallback on). Flag it in the PR.

---

## 5. Compliance-wording bundle

- Merge branch `fix/compliance-language` (commit `970d2e3`, readme-only) into the 2.3.0 branch.
- Add its wording changes to the 2.3.0 **changelog** + **upgrade notice** (they were reverted from the
  abandoned 2.2.1).
- Re-run the `wp-plugin-directory-guidelines` skill before release (new Woo code + upsell stub touch
  Guideline 5/11 territory).

---

## Release checklist (2.3.0)

- [ ] Version bump: `noticepulse.php` header + `NOTICEPULSE_VERSION` → `2.3.0`; readme `Stable tag`.
- [ ] Changelog + upgrade notice (Marquee, country-targeting privacy change, free-shipping bar,
      compliance wording).
- [ ] Plugin Check: 0 errors (warnings = dev-only files, expected).
- [ ] Manual test on `pinspot-dev.local`: existing bars render identically; new Marquee; Woo bar on a
      WooCommerce test store; country targeting with a spoofed `CF-IPCountry` header.
- [ ] `prefers-reduced-motion` honored by Marquee.
- [ ] Deploy via GitHub Release → `deploy.yml` (same flow as 2.2.0).

## Risk notes
- **Lowest-risk:** Marquee + hooks (#1–#5) — pure additions, feature-class pattern already proven.
- **Medium:** country-targeting migration — must not break existing geo users; gate carefully.
- **Needs a Woo test store:** the free-shipping bar — can't fully verify on `pinspot-dev.local`
  without WooCommerce + a shipping zone configured.
