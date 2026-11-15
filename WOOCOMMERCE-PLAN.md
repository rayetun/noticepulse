# NoticePulse × WooCommerce — Strategy (free-now, Pro-ready)

> Status: **direction locked, build not started.** Planning doc only (excluded from the shipped ZIP via `.distignore`).
> Decision (2026-11-15): **ship the WooCommerce features FREE.** Do **not** build a paid tier yet — but
> architect everything on a clean hook seam so a Freemius-based `noticepulse-pro` stays possible with
> near-zero rework if/when scale justifies it.
> Depends on: hook surface shipped in free **2.3.0**.

---

## 1. The decision and why (CTO call, 2026-11-15)

At **~60 installs**, a paid tier earns rounding-error revenue while adding permanent fixed overhead
(a second plugin to maintain, the Freemius SDK + its gotchas, billing, refunds, license edge cases,
Freemius's cut). That trade is bad at this scale.

Meanwhile the competitive scan (below) shows the opposite play is working: **Runline — a free,
privacy-first, feature-rich bar plugin — just got featured for November 2026.** Featuring is the
single biggest install-growth lever on WordPress.org, and the directory structurally favours
genuinely-free plugins. Paywalling now would risk the exact lever that grows the audience a paid
tier would eventually need.

**So:**
- Ship **all** the WooCommerce features **free** — as a growth engine.
- Build them on the **extension hook seam** (§4) so a future `noticepulse-pro` can attach via hooks
  only, with no rework of free code.
- **Revisit Pro at ~2–3k installs**, led by **revenue analytics** (attributed revenue / AOV — the one
  thing with real willingness-to-pay that Runline does *not* give away).

Keeping the Pro door open is free; walking through it now is expensive.

### What changed vs the earlier draft
This file previously scoped a paid Freemius add-on. The architecture research there still stands and is
preserved in **§6 (Pro-ready architecture — deferred)** so nothing is lost. Only the *price tag* comes
off the WooCommerce features; the feature plan itself is unchanged.

---

## 2. Competitive gap analysis

| Capability | NoticePulse (today, 2.2.x) | Runline (free, featured) | MyStickyMenu (100K) |
|---|---|---|---|
| Marquee / news ticker (continuous scroll) | ❌ (has fade/slide carousel only) | ✅ free (fade/slide/typewriter + **vertical** ticker) | 🔒 Pro ("sliding text") |
| WooCommerce free-shipping progress bar | ❌ | ✅ free (`{free_shipping_left}`, live cart) | ❌ none |
| Dynamic product messages (on-sale/featured/best-selling) | ❌ | ✅ free | ❌ |
| Recent-sales social proof | ❌ | ✅ free | ❌ |
| Countdown from product sale end date | ❌ | ✅ free | 🔒 Pro (generic) |
| Country targeting | ✅ (via ipapi.co, external) | ✅ (Cloudflare/host headers, **no 3rd party**) | 🔒 Pro |
| Design/template count | 25 | 60+ | — |
| Email providers | **5** (Mailchimp, Klaviyo, Kit, MailerLite, Brevo) | 3 (Mailchimp, MailerLite, Brevo) | Poptin only |
| Cookie / notice bar | ✅ | ➖ not emphasized | ❌ |
| A/B testing | ✅ free | ✅ free | 🔒 Pro |
| Per-element controls + WYSIWYG preview | ✅ (2.2.0) | ➖ | ➖ |

**Reading:**
- NoticePulse's **free tier already beats MyStickyMenu's free tier** (we give away targeting, triggers,
  A/B, geo, 5 email providers that they gate).
- **Runline is the benchmark** — free, privacy-first, feature-rich. Match its table stakes in *our* free
  tier: (a) a **marquee / news ticker** mode, (b) **country targeting without a third-party IP call**
  (privacy win; removes our one ipapi.co dependency).
- Because Runline gives basic Woo bars away free, there is **no commodity Woo feature we could charge
  for** today. The only paid-worthy cluster is **real shipping-zone logic + auto-coupons + revenue
  analytics** — and that's the deferred-Pro candidate, not a launch product.

---

## 3. What ships, and when (all FREE)

### Ships FREE in 2.3.0 (see `NOTICEPULSE-2.3.0-BUILD-PLAN.md` for the spec)
- **Marquee / News Ticker** bar — continuous horizontal scroll + optional vertical ticker.
  *(Distinct from the existing fade/slide `ticker`/carousel type.)*
- **The extension hook surface** (§4) — shipped even though no add-on consumes it yet; it's what keeps
  Pro cheap later and lets the Woo bars be built as cleanly-separated modules.
- **Basic WooCommerce free-shipping bar** — `{free_shipping_left}` style, live cart, to match Runline.
- **Country targeting via Cloudflare/host headers** — replaces the ipapi.co external call in free.

### Ships FREE later (was the "paid" list; now just sequenced free work)
Grouped by value prop — build order roughly A→G as demand shows:

**A. Smart free-shipping bar** — reads **real WooCommerce shipping zones & methods / thresholds**
(not a hardcoded number); **tiered goals** ("$50 → free shipping, $100 → free gift"); per-category /
per-product exclusions; multi-currency aware.

**B. Cart-aware conversion messaging** — "Add $X more for free shipping / a gift", updates as the cart
changes; conditional bars by **cart contents** and **cart value**.

**C. Auto-applying coupon bars** — one click **applies the coupon to the cart** (vs the existing free
copy-to-clipboard coupon bar).

**D. Sale-synced countdowns** — pull end date/time automatically from **scheduled WooCommerce product
sales**; multi-product.

**E. Revenue analytics** — **attributed revenue & AOV lift per bar**; A/B testing scored on **revenue**,
not just clicks. *(If Pro is ever built, this is the lead paid feature — see §6.)*

**F. Customer-segment targeting** — first-time vs returning, order history / lifetime value, logged-in
customer segments.

**G. Dynamic product bars** — on-sale / featured / best-selling products with price & stock, tracked for
conversion.

> Because it's all free, the §3 guardrail from the old plan ("keep the free Woo footprint small") is
> **dropped**. Give it all away; compete on breadth + privacy + polish.

---

## 4. Hook surface to add to the plugin in 2.3.0

Even though we're not building Pro now, these hooks are worth shipping in 2.3.0 because they let the Woo
features live as **self-contained modules** (same `file_exists()` feature-file pattern as today) rather
than being wired into core — and they're exactly what a future add-on would attach to.

The plugin **already exposes** (verified in code):
`noticepulse_active_bars`, `noticepulse_save_bar_data`, `noticepulse_bar_data_attributes`,
`noticepulse_bar_inline_styles`, `noticepulse_admin_enqueue_scripts`.

**To add in 2.3.0** — bar types are currently validated against a **hardcoded allow-list**
(`class-noticepulse-admin.php`, the `$valid_types` / `$valid_bar_types` arrays), so new types can't be
registered by a module today:

1. **`noticepulse_valid_bar_types` filter** — make the allow-list filterable so modules can register
   `marquee`, `woo_free_shipping`, `woo_cart`, `woo_coupon`, etc.
2. **Custom bar-type render hook** — `do_action( 'noticepulse_render_bar_type_{type}', $bar )` (or a
   `noticepulse_render_custom_bar` filter) so a module can output markup for its own types.
3. **Merge-tag / message-token API** — `noticepulse_message_tokens` filter so a module can register
   `{free_shipping_left}`, `{cart_total}`, `{coupon}` and resolve them at render time.
4. **Client-side JS event bus** — a small documented `window.NoticePulse` API + a `np:cart-updated`
   event so cart JS can refresh bar text without core knowing anything about Woo.
5. **Editor extension slots** — `do_action` inside the bar editor (design/content/triggers tabs) so a
   module can inject its own fields; plus the admin-enqueue hook (already present).
6. **Analytics event hook** — a filter/action on the analytics write path so a module can log
   revenue/order events against a bar (foundation for §3-E).
7. **Upsell anchors (link-only), dormant** — a settings-tab slot + a single `plugin_action_links` slot,
   wired but **empty/hidden** while there's no Pro. Costs nothing now; ready if Pro is ever built. WP.org
   Guideline 11 compliant (no dashboard-wide nags).

Deliverable for 2.3.0: ship #1–#6 as stable, documented hooks alongside the Marquee bar. #7 is a dormant
stub.

---

## 5. Roadmap placement

| Version | Tier | Ships |
|---|---|---|
| **2.3.0** | free | **Marquee / News Ticker** + §4 hook surface + **basic free-shipping bar** + **header-based country targeting** + compliance-wording bundle + Marquee/Woo templates. **Shipped to branch.** |
| **2.4.0** | free | **Gutenberg blocks** — 2–3 server-rendered dynamic blocks (Countdown, Marquee, WooCommerce Free-Shipping) reusing the render + feature classes and the §4 hook seam. Inline variant of the global bars; new block-directory discovery channel. |
| **2.5.0** | free | "Save as template" (bumped from 2.4.0 for the Gutenberg blocks). |
| **2.6.0+** | free | WooCommerce depth: §3 A–G as demand shows, each as a hook-attached module. |
| **Pro v1.0** | paid | **Deferred.** Revisit at ~2–3k installs; revenue analytics (§3-E) as the lead feature. See §6. |

---

## 6. Pro-ready architecture (deferred — reference only)

Preserved from the earlier add-on scoping so the path is documented if/when we build it. **Do not act on
this now.**

### Trigger to revisit
~2–3k installs **and** evidence of willingness-to-pay for revenue attribution. Lead feature = §3-E
(attributed revenue / AOV per bar), the one thing not commoditised by Runline's free tier.

### Freemius two-plugin model (when built)
1. **Two plugins.** Free `noticepulse` stays on WP.org, **Freemius-free**, never crippled. Paid tier is a
   **separate plugin** (`noticepulse-pro`) via Freemius, `Requires Plugins: noticepulse`.
2. **Attach via hooks only** (the §4 surface) — the add-on never forks or edits free code.
3. **Premium-only product:** `is_premium_only = true`, `is_org_compliant = false`.
4. **Net-new features only.** Never move a shipped free feature behind the paywall. *(This is why Pro's
   lead feature must be revenue analytics — it's new depth on top of free analytics, not a takeaway.)*
5. **Free-side detection** via a marker constant the add-on defines *only after* `can_use_premium_code()`
   passes, read at **render time**.

### Freemius setup gotchas (from the playbook, for later)
- Deploy keys come from **My Profile → Keys** (developer scope), *not* the product's Settings → Keys (avoids 401).
- No bundled `uninstall.php` — register cleanup via `after_uninstall` (avoids 400).
- **Bump version every deploy** (Freemius rejects duplicates).
- Premium-only support-forum link 404s → filter `support_forum_url` to the free plugin's WP.org forum.

### Pricing (for if/when — not active)
Annual-only (monthly churn isn't worth the billing overhead at a small team). WooCommerce-store buyers;
benchmarked against conversion-bar plugins ($49–99/yr norm):

| Plan | Annual | Lifetime (launch-only, capped/sunset) |
|---|---|---|
| 1 site | **$49/yr** ($39 intro) | $129 |
| 5 sites | **$99/yr** | $249 |
| 25 sites | **$199/yr** | $499 |

Plus a **7-day no-card trial** (biggest Freemius conversion lever) and **40%-off first-100-customers** to
seed reviews. Lifetime must be capped or sunset — upfront cash for long-tail support liability; fine for
launch, dangerous as a permanent SKU.

---

## 7. Open decisions

All six from the last round are now **closed**:
1. Add-on name (if ever built) = `noticepulse-pro`. ✅
2. Free Woo footprint = **ship it all free** (not just a basic bar). ✅ *(supersedes the old "keep it small")*
3. Swap ipapi.co → header-based country targeting = **yes**, in 2.3.0. ✅
4. Pricing = recorded in §6 for if/when; **not active**. ✅
5. Build Pro now vs keep free = **keep free, architect for optionality, revisit at scale**. ✅
6. Compliance patch = **bundled into 2.3.0**, no standalone 2.2.1. ✅

Nothing open. Next concrete work = the 2.3.0 build (`NOTICEPULSE-2.3.0-BUILD-PLAN.md`).
