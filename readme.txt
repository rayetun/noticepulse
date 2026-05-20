=== NoticePulse — Notification Bar, Announcement Bar & Cookie Notice ===
Contributors:      rayetun
Donate link:       https://wise.com/pay/me/mdrayhanu2
Tags:              notification bar, announcement bar, cookie notice, countdown timer, header bar
Requires at least: 6.2
Tested up to:      7.0
Requires PHP:      7.4
Stable tag:        2.1.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Notification bar plugin. CTA Bar, Header bar, footer bar, cookie notice, countdown timer, text carousel, email capture, coupon copy & more - all free.

== Description ==

**NoticePulse** is the most complete free notification bar plugin for WordPress. Display stunning announcement bars, GDPR cookie notices, countdown timers, rotating text carousels, email capture bars, click-to-copy coupon bars, and click-to-call bars — all from one clean, professional dashboard.

Whether you need a sticky top header bar for a flash sale, a GDPR cookie consent notice at the bottom of your site, a live countdown for a limited-time offer, or an inline email capture bar to grow your list — NoticePulse delivers everything out of the box.

> **NoticePulse is 100% free.** Unlimited bars, all bar types, full analytics, A/B testing, geo-targeting, email integrations — no premium tier, no feature gates, no upgrade prompts. Ever.

---

= 🔔 7 Bar Types — One Plugin =

**1. Announcement Bar**
The essential notification bar. Display promotions, shipping offers, product launches, or any message at the top or bottom of your site. Full color control, optional CTA button, and a real-time live preview that updates as you type.

**2. Cookie / GDPR Consent Bar**
A fully compliant cookie consent bar with Accept, Decline, and Privacy Policy link buttons. Choice stored in visitor's browser — configurable expiry from 1 to 3,650 days. Ready for GDPR, CCPA, and ePrivacy compliance.

**3. Countdown Timer Bar**
Build urgency with a live ticking countdown. Set an end date and time — the bar auto-hides when the timer hits zero. Fully customizable day, hour, minute, and second labels. Perfect for flash sales, limited offers, and product launches.

**4. Text Carousel Bar**
Rotate multiple messages in a single bar with smooth fade or slide transitions. Each slide can have its own CTA button and URL. Navigation arrows, dot indicators, and pause-on-hover included. Announce multiple promotions without stacking bars.

**5. Email Capture Bar**
Grow your mailing list directly from your notification bar. Inline email input, customizable submit button, and a success message when visitors subscribe. Leads saved to your WordPress database. Optional one-click sync to Mailchimp, Klaviyo, Kit, MailerLite, or Brevo.

**6. Click-to-Copy Coupon Bar**
Display a promo code with a copy-to-clipboard button. One click copies the code instantly — a "✓ Copied!" confirmation appears. Uses the Clipboard API with a graceful fallback for older browsers.

**7. Click-to-Call Bar**
Show a phone number with a tap-to-call button for mobile visitors. Works with tel: links for instant dialing. Can be set to appear on mobile only so desktop visitors see a different bar.

---

= 🎨 17 Pre-Built Templates — Launch in Seconds =

NoticePulse includes **17 professionally designed templates** organized by bar type. One click applies the full design — colors, message, CTA, and settings pre-filled. Customize to your brand and publish.

**Announcement:** Free Shipping, Black Friday, Summer Sale, New Arrival, Flash Sale
**GDPR:** GDPR Minimal, GDPR Friendly
**Countdown:** Sale Ends Soon, Limited Offer
**Email Capture:** Newsletter Signup, Lead Magnet
**Coupon:** 20% Off, Welcome 10%
**Click-to-Call:** Call Us Now
**Text Carousel:** Promo Carousel, Feature Highlights, Announcements

---

= ⚙️ Complete Feature List =

**Display & Position**
* Sticky top header bar — stays fixed as visitors scroll
* Sticky bottom footer bar — ideal for cookie notices and CTAs
* Static mode — bar scrolls with the page content
* Slide-in, fade, bounce, and pulse entrance animations
* Show or hide independently on desktop, tablet, and mobile

**Design & Customization**
* Full hex color picker for background, message text, button background, button text, and close button
* Gradient backgrounds — linear and radial, with angle control and live preview
* Google Fonts integration — choose any font from the Google Fonts library
* Button shape: Sharp, Rounded, or Pill
* Font size: Small, Medium, or Large
* Bar height: Compact, Normal, or Tall
* Text alignment: Left, Center, or Right

**Content**
* Message supports `<strong>`, `<em>`, `<a>`, `<br>`, `<span>` and emoji
* Optional CTA button with label, URL, and new-tab toggle
* Close/dismiss button with configurable cookie duration (0–3,650 days)

**Targeting & Scheduling**
* Show on all pages or restrict to specific page/post IDs
* Target all visitors, logged-in users only, or logged-out visitors only
* Start and end date/time scheduling — bars appear and disappear automatically

**Trigger Options**
* On page load (default)
* After visitor scrolls a configurable percentage
* After a time delay in seconds
* Exit intent — fires when the cursor moves toward the browser chrome

**Analytics**
* Impression counter per bar
* CTA click counter per bar
* Click-through rate (CTR) calculated automatically
* Full analytics dashboard with Chart.js line graph
* Filter by date range: 7, 30, 90 days, or all time
* Filter by individual bar
* Per-bar performance table with colour-coded CTR badges
* Export analytics as CSV
* Export captured email leads as CSV
* Reset analytics per bar individually

**A/B Testing**
* Split traffic between two bar variants
* Track impressions and clicks per variant independently
* Identify the winning variant from the analytics dashboard

**Geo-Targeting**
* Restrict bars to visitors from specific countries
* Country detected from visitor IP address via ipapi.co (see External Services below)
* Results cached server-side — no repeat API calls for the same IP within one hour

**Admin & Tools**
* Dark-themed, distraction-free admin interface
* Live preview sidebar — updates in real time as you design
* Template library with 14 pre-built designs and instant one-click apply
* Export all bars as a JSON backup
* Import bars from a JSON file — existing bars are always preserved
* Danger Zone — delete all data with a single confirmed click
* Plugin info card — version, WordPress version, PHP version, total bars, database status

**Performance**
* Vanilla JavaScript on the frontend — no jQuery dependency
* Assets enqueued only on pages where active bars exist
* No meta boxes, no custom post types, no admin bloat

**Developer Hooks**
* `noticepulse_active_bars` — filter which bars are eligible per page and visitor
* `noticepulse_bar_data_attributes` — add custom HTML data attributes to any bar
* `noticepulse_bar_inline_styles` — extend or override bar inline CSS properties
* `noticepulse_save_bar_data` — hook into the bar save process for custom fields

---

= 🛡️ Privacy & Security =

* Visitor dismissal state stored in a browser cookie on the visitor's own device only
* Email leads stored in your own WordPress database — never transmitted without your configuration
* All database queries use `$wpdb->prepare()` with correct placeholders
* All inputs sanitized on save and escaped on output per WordPress coding standards
* Nonce verification on all form submissions and AJAX requests
* Capability checks (`manage_options`) on all admin actions

---

= 🌐 Who Uses NoticePulse? =

* **eCommerce stores** — free shipping announcements, flash sale countdowns, coupon codes
* **Agencies** — GDPR cookie consent bars for client sites
* **SaaS companies** — product launch announcements, feature update banners
* **Bloggers & media** — newsletter signups, breaking news carousels
* **Restaurants & local businesses** — click-to-call bars for mobile visitors
* **Event organizers** — ticket sale countdowns, registration deadline timers
* **Email marketers** — inline capture bars synced to Mailchimp, Klaviyo, and more

---

== External Services ==

NoticePulse optionally connects to external services for specific features. Each connection is documented below. No data is transmitted until you explicitly configure and activate the relevant feature.

---

**ipapi.co — Geo-Targeting Country Detection**

Used when a bar has Geo-Targeting enabled. The visitor's IP address is sent to ipapi.co to determine their two-letter country code (e.g. "US", "DE"). The result is cached server-side for one hour — no repeat API calls are made for the same IP within that window. If no active bar has geo-targeting enabled, no request is ever made.

Data sent: visitor IP address.
Sent when: a page loads containing an active bar with geo-targeting configured.
Service URL: https://ipapi.co
Terms of Service: https://ipapi.co/terms/
Privacy Policy: https://ipapi.co/privacy/

---

**Google Fonts — Custom Typography**

Used when a bar is configured with a Google Font. The visitor's browser requests the font stylesheet directly from Google's servers. No request is made if no active bar uses a custom font.

Data sent: the font family name as a URL parameter in the request to fonts.googleapis.com. No personal data is transmitted — this is a standard browser font request.
Sent when: a page loads containing an active bar with a Google Font selected.
Service URL: https://fonts.google.com
Terms of Service: https://developers.google.com/terms
Privacy Policy: https://policies.google.com/privacy

---

**Email Integrations (Email Capture Bar)**

The following services are used only when you have configured an email integration in the Email Capture bar settings, and a visitor submits the email capture form. If you choose "Store locally only", no external request is ever made.

**Mailchimp** — adds subscriber to your Mailchimp audience.
Data sent: email address.
Terms of Service: https://mailchimp.com/legal/terms/
Privacy Policy: https://www.intuit.com/privacy/statement/

**Klaviyo** — adds subscriber to your Klaviyo list.
Data sent: email address.
Terms of Service: https://www.klaviyo.com/legal/terms-of-service
Privacy Policy: https://www.klaviyo.com/legal/privacy

**Kit (formerly ConvertKit)** — adds subscriber to your Kit form.
Data sent: email address, first name (if provided).
Terms of Service: https://kit.com/terms
Privacy Policy: https://kit.com/privacy

**MailerLite** — adds subscriber to your MailerLite group.
Data sent: email address.
Terms of Service: https://www.mailerlite.com/legal/terms-of-service
Privacy Policy: https://www.mailerlite.com/legal/privacy-policy

**Brevo (formerly Sendinblue)** — adds contact to your Brevo list.
Data sent: email address.
Terms of Service: https://www.brevo.com/legal/termsofuse/
Privacy Policy: https://www.brevo.com/legal/privacypolicy/

---

== Installation ==

**From the WordPress Plugin Directory (recommended)**

1. Go to **Plugins → Add New** in your WordPress admin.
2. Search for **NoticePulse**.
3. Click **Install Now** then **Activate**.
4. Navigate to **NoticePulse** in the left admin menu.

**Manual Upload**

1. Download the plugin ZIP file.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Select the ZIP file and click **Install Now**, then **Activate Plugin**.

**After Activation**

1. Go to **NoticePulse → Add New Bar**.
2. Select a bar type or click **Browse Templates** for a pre-built design.
3. Customize colors, message, CTA, and settings.
4. Click **Publish Bar**.
5. Visit your site — the bar appears immediately.

---

== Frequently Asked Questions ==

= Is NoticePulse completely free? =

Yes. Every feature — all 7 bar types, unlimited bars, analytics, 14 templates, email integrations, A/B testing, geo-targeting, and exit intent — is completely free. There is no paid version.

= How many notification bars can I have? =

Unlimited. There is no cap on the number of bars you can create or activate simultaneously.

= What bar types does NoticePulse include? =

7 types: Announcement, Cookie/GDPR Consent, Countdown Timer, Text Carousel, Email Capture, Click-to-Copy Coupon, and Click-to-Call.

= Will it slow down my site? =

No. NoticePulse uses vanilla JavaScript (no jQuery), loads assets only on pages with active bars, and outputs clean minimal HTML. It has no measurable impact on Core Web Vitals.

= Can I show different bars on different pages? =

Yes. Each bar has Page Visibility settings — show on all pages or restrict to specific page and post IDs.

= How does the GDPR cookie bar work? =

The GDPR bar shows Accept, Decline, and an optional Privacy Policy link. The visitor's choice is stored in a browser cookie. You control how many days the bar stays hidden — from 1 day to 3,650 days.

= How does email capture work? =

An inline email input and submit button appear inside your notification bar. Submitted emails are saved to your WordPress database. Optionally connect to Mailchimp, Klaviyo, Kit, MailerLite, or Brevo to sync subscribers — or keep everything local with no external connection.

= Does the countdown timer auto-hide when it reaches zero? =

Yes. The bar automatically hides when the countdown expires.

= How does geo-targeting work? =

When geo-targeting is enabled on a bar, the visitor's IP address is sent to ipapi.co to detect their country code. Results are cached server-side for one hour. Full details are in the External Services section.

= Does NoticePulse work with Elementor, Divi, and Beaver Builder? =

Yes. Bars are rendered via WordPress's `wp_footer` hook, completely independently of any page builder or theme.

= Is it compatible with WP Rocket, LiteSpeed Cache, and other caching plugins? =

Yes. Analytics tracking uses AJAX so it works correctly with full-page caching. Cookie-based dismiss is handled client-side only.

= Can I back up and restore my bars? =

Yes. Go to **NoticePulse → Settings** to export all bars as a JSON file. Import on any other site running NoticePulse — existing bars are never overwritten.


---

== 🤝 Support ==
Post in the WordPress.org support forum. We aim to respond within 3 business days.

If Urgent You can contact me here:
Email: rayetun2.0@gmail.com

---

== Author ==
Md Rayhan Uddin
https://rayetun.com

---

== Screenshots ==

1. Bar list dashboard — manage all notification bars from one clean dark-themed interface.
2. Add New Bar — choose your bar type with descriptions for each option.
3. Template library — 17 pre-built designs. One click applies the full design.
4. Edit bar — Content tab with live preview updating in real time.
5. Edit bar — Design tab with color pickers, Google Fonts, and gradient options.
6. Edit bar — Triggers tab with exit intent, scroll depth, and time delay.
7. GDPR cookie consent bar on the frontend.
8. Countdown timer bar on the frontend — live ticking counter.
9. Email capture bar with inline subscribe form.
10. Click-to-copy coupon bar — code copied to clipboard in one click.
11. Analytics dashboard — Chart.js chart, per-bar CTR table, CSV export.
12. Settings & Tools — JSON export/import, plugin info, danger zone.

---

== Changelog ==
= 2.1.4 =
* Confirmed compatibility with WordPress 7.0.

= 2.1.3 =
* Security: All admin view output now wrapped with esc_html() or esc_attr().
* Security: TRUNCATE TABLE queries now use $wpdb->prepare() with the %i identifier placeholder.
* Security: Nonce explanation comments added to all save_fields() filter callbacks.
* Fix: All AJAX hook names renamed from np_ to noticepulse_ prefix (4-character minimum).
* Fix: JS object names renamed from npAnalytics/npEmailCapture to noticepulseAnalytics/noticepulseEmailCapture.
* Fix: ipapi.co and Google Fonts fully documented in External Services section.
* Fix: Dead custom CSS class reference removed from main plugin file.
* Fix: Import handler uses per-field sanitization — bar_meta JSON no longer corrupted on import.
* Fix: Import button now enables correctly after drag-and-drop file selection.
* Fix: Settings page JS correctly enqueued.

= 2.1.2 =
* Removed: Custom CSS textarea per WordPress.org policy. Users directed to Appearance → Customize → Additional CSS.
* Updated: Chart.js from v4.4.1 to v4.5.1.
* Added: External Services section in readme covering all email integrations.

= 2.1.1 =
* New: Text Carousel bar type with fade/slide transitions, arrows, and dot indicators.
* New: Analytics dashboard with Chart.js chart, date range filter, CTR table, and CSV export.
* New: Template library with 14 pre-built designs.
* New: Google Fonts, gradient backgrounds, exit intent, scroll depth, and time delay triggers.
* New: A/B testing, geo-targeting, and email leads CSV export.
* Fix: Button colors, color picker swatches, and database schema migrations.

= 2.0.0 =
* New: Cookie/GDPR, Countdown Timer, Email Capture, Coupon, Click-to-Call, and Text Carousel bar types.
* New: Developer filter API, live preview sidebar, Google Fonts, gradients, JSON export/import.

= 1.0.0 =
* Initial release — Announcement bar, full color control, sticky positions, scheduling, analytics, live preview.

---

== Upgrade Notice ==

= 2.1.3 =
Security and compliance update. Fully aligned with WordPress.org coding standards. Safe to update — no breaking changes, no database modifications.

= 2.1.2 =
Removes Custom CSS textarea. Updates Chart.js to v4.5.1. Safe to update.

= 2.0.0 =
Major release — 6 new bar types. All existing bars preserved.
