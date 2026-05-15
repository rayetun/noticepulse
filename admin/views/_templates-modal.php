<?php
/**
 * NoticePulse — Template Library Modal.
 *
 * Template IDs and preview data here MUST match class-np-templates.php exactly.
 * The AJAX handler (np_get_template) looks up templates by their 'id' field,
 * so any mismatch returns "Template not found" → error toast.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Display-only registry — mirrors class-np-templates.php IDs exactly.
 * Only preview colours/text are stored here; full field values come from AJAX.
 *
 * @var array $np_template_registry
 */
$np_template_registry = array(

	// ── Announcement ─────────────────────────────────────────────────────────
	array(
		'id'       => 'free-shipping',        // ← matches class-np-templates.php
		'label'    => 'Free Shipping',
		'category' => 'announcement',
		'bar_type' => 'standard',
		'emoji'    => '🚚',
		'preview'  => array(
			'message'  => '🚚 Free shipping on all orders today!',
			'bg'       => '#16a34a',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#16a34a',
			'cta'      => 'Shop Now',
		),
	),
	array(
		'id'       => 'black-friday',
		'label'    => 'Black Friday',
		'category' => 'announcement',
		'bar_type' => 'standard',
		'emoji'    => '🖤',
		'preview'  => array(
			'message'  => '🖤 BLACK FRIDAY — 40% off everything!',
			'bg'       => '#0a0a0a',
			'text'     => '#f5f0e8',
			'btn'      => '#f59e0b',
			'btn_text' => '#0a0a0a',
			'cta'      => 'Grab the Deal',
		),
	),
	array(
		'id'       => 'summer-sale',
		'label'    => 'Summer Sale',
		'category' => 'announcement',
		'bar_type' => 'standard',
		'emoji'    => '☀️',
		'preview'  => array(
			'message'  => '☀️ Summer Sale — up to 50% off selected items!',
			'bg'       => '#f97316',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#f97316',
			'cta'      => 'Shop the Sale',
		),
	),
	array(
		'id'       => 'new-arrival',
		'label'    => 'New Arrival',
		'category' => 'announcement',
		'bar_type' => 'standard',
		'emoji'    => '✨',
		'preview'  => array(
			'message'  => '✨ New collection just dropped — be the first to explore.',
			'bg'       => '#7c3aed',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#7c3aed',
			'cta'      => 'Explore Now',
		),
	),
	array(
		'id'       => 'flash-sale',
		'label'    => 'Flash Sale',
		'category' => 'announcement',
		'bar_type' => 'standard',
		'emoji'    => '⚡',
		'preview'  => array(
			'message'  => '⚡ 4-hour flash sale — 30% off sitewide. Hurry!',
			'bg'       => '#dc2626',
			'text'     => '#ffffff',
			'btn'      => '#fef2f2',
			'btn_text' => '#dc2626',
			'cta'      => 'Shop Now',
		),
	),

	// ── GDPR ─────────────────────────────────────────────────────────────────
	array(
		'id'       => 'gdpr-minimal',
		'label'    => 'GDPR Minimal',
		'category' => 'gdpr',
		'bar_type' => 'gdpr',
		'emoji'    => '🍪',
		'preview'  => array(
			'message'  => 'We use cookies to improve your browsing experience.',
			'bg'       => '#1e293b',
			'text'     => '#e2e8f0',
			'btn'      => '#7c5cfc',
			'btn_text' => '#ffffff',
			'cta'      => 'Accept All',
		),
	),
	array(
		'id'       => 'gdpr-friendly',
		'label'    => 'GDPR Friendly',
		'category' => 'gdpr',
		'bar_type' => 'gdpr',
		'emoji'    => '🔒',
		'preview'  => array(
			'message'  => '🍪 We value your privacy. We use cookies to personalise content.',
			'bg'       => '#0f172a',
			'text'     => '#f1f5f9',
			'btn'      => '#22d3a5',
			'btn_text' => '#0f172a',
			'cta'      => '✓ Accept All Cookies',
		),
	),

	// ── Countdown ────────────────────────────────────────────────────────────
	array(
		'id'       => 'sale-ends',
		'label'    => 'Sale Ends Soon',
		'category' => 'countdown',
		'bar_type' => 'countdown',
		'emoji'    => '⏱',
		'preview'  => array(
			'message'  => '⏳ Sale ends in: 02:14:37',
			'bg'       => '#dc2626',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#dc2626',
			'cta'      => 'Shop Now',
		),
	),
	array(
		'id'       => 'limited-offer',
		'label'    => 'Limited Offer',
		'category' => 'countdown',
		'bar_type' => 'countdown',
		'emoji'    => '🔥',
		'preview'  => array(
			'message'  => '🔥 Limited offer ends in: 00:59:12',
			'bg'       => '#7c3aed',
			'text'     => '#ffffff',
			'btn'      => '#fbbf24',
			'btn_text' => '#1d2327',
			'cta'      => 'Claim Offer',
		),
	),

	// ── Email Capture ────────────────────────────────────────────────────────
	array(
		'id'       => 'newsletter',
		'label'    => 'Newsletter Signup',
		'category' => 'email_capture',
		'bar_type' => 'email_capture',
		'emoji'    => '📧',
		'preview'  => array(
			'message'  => '📧 Get 10% off your first order — join our newsletter!',
			'bg'       => '#1a73e8',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#1a73e8',
			'cta'      => 'Get 10% Off',
		),
	),
	array(
		'id'       => 'lead-magnet',
		'label'    => 'Lead Magnet',
		'category' => 'email_capture',
		'bar_type' => 'email_capture',
		'emoji'    => '🎁',
		'preview'  => array(
			'message'  => '🎁 Get our free beginner\'s guide — no spam, ever.',
			'bg'       => '#0f172a',
			'text'     => '#e2e8f0',
			'btn'      => '#22d3a5',
			'btn_text' => '#0f172a',
			'cta'      => 'Send Me the Guide',
		),
	),

	// ── Coupon ───────────────────────────────────────────────────────────────
	array(
		'id'       => 'coupon-20',
		'label'    => '20% Off Coupon',
		'category' => 'coupon_copy',
		'bar_type' => 'coupon_copy',
		'emoji'    => '🎟',
		'preview'  => array(
			'message'  => '🎟 Use code below for 20% off your order!',
			'bg'       => '#16a34a',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#16a34a',
			'cta'      => 'SAVE20',
		),
	),
	array(
		'id'       => 'welcome10',
		'label'    => 'Welcome 10%',
		'category' => 'coupon_copy',
		'bar_type' => 'coupon_copy',
		'emoji'    => '👋',
		'preview'  => array(
			'message'  => '👋 Welcome! Get 10% off your first order.',
			'bg'       => '#7c3aed',
			'text'     => '#ffffff',
			'btn'      => '#ede9fe',
			'btn_text' => '#7c3aed',
			'cta'      => 'WELCOME10',
		),
	),

	// ── Click-to-Call ────────────────────────────────────────────────────────
	array(
		'id'       => 'call-us',
		'label'    => 'Call Us Now',
		'category' => 'click_to_call',
		'bar_type' => 'click_to_call',
		'emoji'    => '📞',
		'preview'  => array(
			'message'  => 'Questions? Our team is ready to help.',
			'bg'       => '#0ea5e9',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#0ea5e9',
			'cta'      => '📞 Call Us Now',
		),
	),

	// ── Text Carousel ─────────────────────────────────────────────────────────
	array(
		'id'       => 'carousel-promo',
		'label'    => 'Promo Carousel',
		'category' => 'ticker',
		'bar_type' => 'ticker',
		'emoji'    => '🎠',
		'preview'  => array(
			'message'  => '🚚 Free shipping  •  ⚡ Flash Sale  •  🎁 Free gift',
			'bg'       => '#7c3aed',
			'text'     => '#ffffff',
			'btn'      => '#ffffff',
			'btn_text' => '#7c3aed',
			'cta'      => 'Shop Now',
		),
	),
	array(
		'id'       => 'carousel-features',
		'label'    => 'Feature Highlights',
		'category' => 'ticker',
		'bar_type' => 'ticker',
		'emoji'    => '✨',
		'preview'  => array(
			'message'  => '✅ 10,000+ customers  •  🔒 SSL secure  •  ↩ 30-day returns',
			'bg'       => '#0f172a',
			'text'     => '#e2e8f0',
			'btn'      => '#7c3aed',
			'btn_text' => '#ffffff',
			'cta'      => 'Our Policy',
		),
	),
	array(
		'id'       => 'carousel-announcements',
		'label'    => 'Announcements',
		'category' => 'ticker',
		'bar_type' => 'ticker',
		'emoji'    => '📢',
		'preview'  => array(
			'message'  => '🎉 Grand opening sale  •  📅 New arrivals Monday  •  🏆 #1 rated',
			'bg'       => '#f59e0b',
			'text'     => '#1a1a1a',
			'btn'      => '#1a1a1a',
			'btn_text' => '#f59e0b',
			'cta'      => 'Get Directions',
		),
	),

);

$np_categories = array(
	'all'           => 'All',
	'announcement'  => '📢 Announcement',
	'gdpr'          => '🍪 GDPR',
	'countdown'     => '⏳ Countdown',
	'email_capture' => '📧 Email',
	'coupon_copy'   => '🎟 Coupon',
	'click_to_call' => '📞 Call',
	'ticker'        => '🎠 Carousel',
);
?>

<!-- ── TEMPLATE LIBRARY MODAL ──────────────────────────────────────────── -->
<div id="np-template-overlay" class="np-tmpl-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Template Library', 'noticepulse' ); ?>" style="display:none;">
	<div class="np-tmpl-modal">

		<div class="np-tmpl-header">
			<div class="np-tmpl-header__left">
				<span class="np-tmpl-header__icon">🎨</span>
				<div>
					<h2 class="np-tmpl-header__title"><?php esc_html_e( 'Template Library', 'noticepulse' ); ?></h2>
					<p class="np-tmpl-header__sub"><?php esc_html_e( '17 ready-made templates — pick one to pre-fill your bar instantly.', 'noticepulse' ); ?></p>
				</div>
			</div>
			<button type="button" class="np-tmpl-close" id="np-tmpl-close" aria-label="<?php esc_attr_e( 'Close', 'noticepulse' ); ?>">✕</button>
		</div>

		<!-- Category tabs -->
		<div class="np-tmpl-cats" id="np-tmpl-cats">
			<?php foreach ( $np_categories as $np_cat_key => $np_cat_label ) : ?>
			<button type="button"
			        class="np-tmpl-cat<?php echo 'all' === $np_cat_key ? ' np-tmpl-cat--active' : ''; ?>"
			        data-cat="<?php echo esc_attr( $np_cat_key ); ?>">
				<?php echo esc_html( $np_cat_label ); ?>
			</button>
			<?php endforeach; ?>
		</div>

		<!-- Template grid -->
		<div class="np-tmpl-grid" id="np-tmpl-grid">
			<?php foreach ( $np_template_registry as $np_tpl ) :
				$np_p = $np_tpl['preview'];
			?>
			<div class="np-tmpl-card"
			     data-id="<?php echo esc_attr( $np_tpl['id'] ); ?>"
			     data-cat="<?php echo esc_attr( $np_tpl['category'] ); ?>"
			     data-type="<?php echo esc_attr( $np_tpl['bar_type'] ); ?>"
			     data-label="<?php echo esc_attr( $np_tpl['label'] ); ?>">

				<!-- Mini bar preview -->
				<div class="np-tmpl-preview" style="background:<?php echo esc_attr( $np_p['bg'] ); ?>;">
					<span class="np-tmpl-preview__msg" style="color:<?php echo esc_attr( $np_p['text'] ); ?>;">
						<?php echo esc_html( $np_p['message'] ); ?>
					</span>
					<span class="np-tmpl-preview__btn" style="background:<?php echo esc_attr( $np_p['btn'] ); ?>; color:<?php echo esc_attr( $np_p['btn_text'] ); ?>;">
						<?php echo esc_html( $np_p['cta'] ); ?>
					</span>
				</div>

				<!-- Footer -->
				<div class="np-tmpl-card__footer">
					<div class="np-tmpl-card__meta">
						<span class="np-tmpl-card__emoji"><?php echo esc_html( $np_tpl['emoji'] ); ?></span>
						<div>
							<div class="np-tmpl-card__name"><?php echo esc_html( $np_tpl['label'] ); ?></div>
							<div class="np-tmpl-card__type"><?php echo esc_html( ucwords( str_replace( '_', ' ', $np_tpl['bar_type'] ) ) ); ?></div>
						</div>
					</div>
					<button type="button"
					        class="np-tmpl-apply-btn"
					        data-id="<?php echo esc_attr( $np_tpl['id'] ); ?>"
					        data-label="<?php echo esc_attr( $np_tpl['label'] ); ?>">
						<?php esc_html_e( 'Use Template', 'noticepulse' ); ?>
					</button>
				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<div class="np-tmpl-empty" id="np-tmpl-empty" style="display:none;">
			<span>🔍</span>
			<p><?php esc_html_e( 'No templates in this category.', 'noticepulse' ); ?></p>
		</div>

	</div>
</div>

<!-- ── CONFIRM DIALOG ────────────────────────────────────────────────────── -->
<div id="np-tmpl-confirm" class="np-tmpl-confirm-wrap" style="display:none;" role="alertdialog" aria-modal="true">
	<div class="np-tmpl-confirm-box">
		<div class="np-tmpl-confirm-icon">⚠️</div>
		<h3 class="np-tmpl-confirm-title"><?php esc_html_e( 'Replace current settings?', 'noticepulse' ); ?></h3>
		<p class="np-tmpl-confirm-msg">
			<?php esc_html_e( 'Applying', 'noticepulse' ); ?>
			<strong id="np-tmpl-confirm-name"></strong>
			<?php esc_html_e( 'will overwrite your current bar settings. This cannot be undone until you save.', 'noticepulse' ); ?>
		</p>
		<div class="np-tmpl-confirm-actions">
			<button type="button" class="np-btn-confirm-cancel" id="np-tmpl-confirm-cancel">
				<?php esc_html_e( 'Keep Current', 'noticepulse' ); ?>
			</button>
			<button type="button" class="np-btn-confirm-ok" id="np-tmpl-confirm-ok">
				<?php esc_html_e( 'Yes, Apply Template', 'noticepulse' ); ?>
			</button>
		</div>
	</div>
</div>
