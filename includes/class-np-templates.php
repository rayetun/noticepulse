<?php
/**
 * NoticePulse — Template Library.
 *
 * Registry of pre-designed bar templates.
 * Templates are applied via an AJAX call that pre-fills
 * the edit-bar form fields with the template data.
 *
 * Note: The full template UI (admin view with preview cards)
 * will be built separately. This class provides the data
 * layer and AJAX handler so templates are available
 * as soon as the UI is ready.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Templates {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_noticepulse_get_template', array( $this, 'ajax_get_template' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// TEMPLATE REGISTRY
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Return all available templates.
	 * Each template maps to a full set of bar fields that will
	 * be injected into the edit-bar form via JS.
	 *
	 * @return array
	 */
	public static function get_all_templates() {
		return array(

			// ── Announcement ────────────────────────────────────────────────

			array(
				'id'       => 'free-shipping',
				'name'     => 'Free Shipping',
				'category' => 'announcement',
				'emoji'    => '🚚',
				'preview'  => '🚚 Free shipping on all orders today!',
				'fields'   => array(
					'bar_type'      => 'standard',
					'message'       => '🚚 Free shipping on all orders today! No minimum purchase.',
					'cta_label'     => 'Shop Now',
					'cta_url'       => '',
					'bg_color'      => '#16a34a',
					'text_color'    => '#ffffff',
					'btn_bg_color'  => '#ffffff',
					'btn_txt_color' => '#16a34a',
					'btn_radius'    => 'pill',
					'bar_padding'   => 'normal',
					'font_size'     => 'medium',
				),
			),

			array(
				'id'       => 'black-friday',
				'name'     => 'Black Friday',
				'category' => 'announcement',
				'emoji'    => '🖤',
				'preview'  => '🖤 BLACK FRIDAY — 40% off everything!',
				'fields'   => array(
					'bar_type'      => 'standard',
					'message'       => '🖤 BLACK FRIDAY — 40% off everything. Use code <strong>BF40</strong>',
					'cta_label'     => 'Grab the Deal',
					'bg_color'      => '#0a0a0a',
					'text_color'    => '#f5f0e8',
					'btn_bg_color'  => '#f59e0b',
					'btn_txt_color' => '#0a0a0a',
					'btn_radius'    => 'rounded',
					'bar_padding'   => 'normal',
				),
			),

			array(
				'id'       => 'summer-sale',
				'name'     => 'Summer Sale',
				'category' => 'announcement',
				'emoji'    => '☀️',
				'preview'  => '☀️ Summer Sale — up to 50% off',
				'fields'   => array(
					'bar_type'    => 'standard',
					'message'     => '☀️ Summer Sale is LIVE — up to 50% off selected items!',
					'cta_label'   => 'Shop the Sale',
					'bg_color'    => '#f97316',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#ffffff',
					'btn_txt_color'=> '#f97316',
					'btn_radius'  => 'pill',
				),
			),

			array(
				'id'       => 'new-arrival',
				'name'     => 'New Arrival',
				'category' => 'announcement',
				'emoji'    => '✨',
				'preview'  => '✨ New collection just dropped!',
				'fields'   => array(
					'bar_type'    => 'standard',
					'message'     => '✨ New collection just dropped — be the first to explore it.',
					'cta_label'   => 'Explore Now',
					'bg_color'    => '#7c3aed',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#ffffff',
					'btn_txt_color'=> '#7c3aed',
					'btn_radius'  => 'pill',
				),
			),

			array(
				'id'       => 'flash-sale',
				'name'     => 'Flash Sale',
				'category' => 'announcement',
				'emoji'    => '⚡',
				'preview'  => '⚡ 4-hour flash sale — 30% off!',
				'fields'   => array(
					'bar_type'    => 'standard',
					'message'     => '⚡ 4-hour flash sale — 30% off sitewide. Hurry!',
					'cta_label'   => 'Shop Now',
					'bg_color'    => '#dc2626',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#fef2f2',
					'btn_txt_color'=> '#dc2626',
					'btn_radius'  => 'rounded',
				),
			),

			// ── GDPR ────────────────────────────────────────────────────────

			array(
				'id'       => 'gdpr-minimal',
				'name'     => 'GDPR Minimal',
				'category' => 'gdpr',
				'emoji'    => '🍪',
				'preview'  => 'We use cookies to improve your experience.',
				'fields'   => array(
					'bar_type'    => 'gdpr',
					'message'     => 'We use cookies to improve your browsing experience and analyse our traffic.',
					'bg_color'    => '#1e293b',
					'text_color'  => '#e2e8f0',
					'bar_meta'    => array( 'gdpr' => array(
						'accept_label'  => 'Accept All',
						'decline_label' => 'Decline',
						'policy_label'  => 'Privacy Policy',
						'cookie_days'   => 365,
					) ),
				),
			),

			array(
				'id'       => 'gdpr-friendly',
				'name'     => 'GDPR Friendly',
				'category' => 'gdpr',
				'emoji'    => '🍪',
				'preview'  => 'We value your privacy.',
				'fields'   => array(
					'bar_type'    => 'gdpr',
					'message'     => '🍪 We value your privacy. We use cookies to personalise content and to analyse our traffic.',
					'bg_color'    => '#0f172a',
					'text_color'  => '#f1f5f9',
					'bar_meta'    => array( 'gdpr' => array(
						'accept_label'  => '✓ Accept All Cookies',
						'decline_label' => 'Decline',
						'policy_label'  => 'Cookie Policy',
						'cookie_days'   => 365,
					) ),
				),
			),

			// ── Countdown ───────────────────────────────────────────────────

			array(
				'id'       => 'sale-ends',
				'name'     => 'Sale Ends Soon',
				'category' => 'countdown',
				'emoji'    => '⏱',
				'preview'  => '⏳ Sale ends in: [timer]',
				'fields'   => array(
					'bar_type'    => 'countdown',
					'message'     => '⏳ Sale ends in:',
					'cta_label'   => 'Shop Now',
					'bg_color'    => '#dc2626',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#ffffff',
					'btn_txt_color'=> '#dc2626',
					'btn_radius'  => 'rounded',
					'bar_meta'    => array( 'countdown' => array(
						'label_days'     => 'Days',
						'label_hours'    => 'Hrs',
						'label_mins'     => 'Min',
						'label_secs'     => 'Sec',
						'hide_on_expire' => 1,
					) ),
				),
			),

			array(
				'id'       => 'limited-offer',
				'name'     => 'Limited Offer',
				'category' => 'countdown',
				'emoji'    => '⏱',
				'preview'  => '🔥 Limited offer ends in: [timer]',
				'fields'   => array(
					'bar_type'    => 'countdown',
					'message'     => '🔥 Limited offer ends in:',
					'cta_label'   => 'Claim Offer',
					'bg_color'    => '#7c3aed',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#fbbf24',
					'btn_txt_color'=> '#1d2327',
					'btn_radius'  => 'pill',
				),
			),

			// ── Email Capture ────────────────────────────────────────────────

			array(
				'id'       => 'newsletter',
				'name'     => 'Newsletter Signup',
				'category' => 'email_capture',
				'emoji'    => '📧',
				'preview'  => '📧 Get 10% off your first order',
				'fields'   => array(
					'bar_type'    => 'email_capture',
					'message'     => '📧 Get 10% off your first order — join our newsletter!',
					'bg_color'    => '#1a73e8',
					'text_color'  => '#ffffff',
					'bar_meta'    => array( 'email' => array(
						'placeholder' => 'Your email address…',
						'btn_label'   => 'Get 10% Off',
						'success_msg' => '🎉 Check your inbox for your discount code!',
						'provider'    => 'none',
					) ),
				),
			),

			array(
				'id'       => 'lead-magnet',
				'name'     => 'Lead Magnet',
				'category' => 'email_capture',
				'emoji'    => '🎁',
				'preview'  => '🎁 Free guide — enter your email',
				'fields'   => array(
					'bar_type'    => 'email_capture',
					'message'     => '🎁 Get our free beginner\'s guide — no spam, ever.',
					'bg_color'    => '#0f172a',
					'text_color'  => '#e2e8f0',
					'bar_meta'    => array( 'email' => array(
						'placeholder' => 'Enter your email…',
						'btn_label'   => 'Send Me the Guide',
						'success_msg' => '✅ Check your inbox!',
						'provider'    => 'none',
					) ),
				),
			),

			// ── Coupon ──────────────────────────────────────────────────────

			array(
				'id'       => 'coupon-20',
				'name'     => '20% Off Coupon',
				'category' => 'coupon_copy',
				'emoji'    => '🎟',
				'preview'  => '🎟 Use code SAVE20 for 20% off',
				'fields'   => array(
					'bar_type'    => 'coupon_copy',
					'message'     => '🎟 Use code below for 20% off your order!',
					'bg_color'    => '#16a34a',
					'text_color'  => '#ffffff',
					'bar_meta'    => array( 'coupon' => array(
						'code'          => 'SAVE20',
						'btn_label'     => 'Copy Code',
						'success_label' => '✓ Copied!',
					) ),
				),
			),

			array(
				'id'       => 'welcome10',
				'name'     => 'Welcome 10%',
				'category' => 'coupon_copy',
				'emoji'    => '👋',
				'preview'  => '👋 Welcome! Use WELCOME10 for 10% off',
				'fields'   => array(
					'bar_type'    => 'coupon_copy',
					'message'     => '👋 Welcome! Get 10% off your first order.',
					'bg_color'    => '#7c3aed',
					'text_color'  => '#ffffff',
					'bar_meta'    => array( 'coupon' => array(
						'code'          => 'WELCOME10',
						'btn_label'     => '📋 Copy Code',
						'success_label' => '✓ Copied!',
					) ),
				),
			),

			// ── Click-to-Call ────────────────────────────────────────────────

			array(
				'id'       => 'call-us',
				'name'     => 'Call Us Now',
				'category' => 'click_to_call',
				'emoji'    => '📞',
				'preview'  => '📞 Questions? Call us!',
				'fields'   => array(
					'bar_type'    => 'click_to_call',
					'message'     => 'Questions? Our team is ready to help.',
					'bg_color'    => '#0ea5e9',
					'text_color'  => '#ffffff',
					'bar_meta'    => array( 'call' => array(
						'btn_label'   => '📞 Call Us Now',
						'mobile_only' => 0,
					) ),
				),
			),

			// ── Text Carousel ─────────────────────────────────────────────────

			array(
				'id'       => 'carousel-promo',
				'name'     => 'Promo Carousel',
				'category' => 'ticker',
				'emoji'    => '🎠',
				'preview'  => '🎠 Rotating promotional messages',
				'fields'   => array(
					'bar_type'    => 'ticker',
					'message'     => 'Free shipping on orders over $50',
					'bg_color'    => '#7c3aed',
					'text_color'  => '#ffffff',
					'btn_bg_color'=> '#ffffff',
					'btn_txt_color'=> '#7c3aed',
					'bar_meta'    => array( 'ticker' => array(
						'messages'    => array(
							array( 'text' => '🚚 Free shipping on all orders over $50', 'cta_label' => 'Shop Now', 'cta_url' => '' ),
							array( 'text' => '⚡ Flash Sale — 30% off sitewide today only!', 'cta_label' => 'See Deals', 'cta_url' => '' ),
							array( 'text' => '🎁 Free gift with every order over $100', 'cta_label' => 'Learn More', 'cta_url' => '' ),
						),
						'speed'       => 4,
						'transition'  => 'fade',
						'pause_hover' => 1,
						'show_arrows' => 0,
						'show_dots'   => 1,
					) ),
				),
			),

			array(
				'id'       => 'carousel-features',
				'name'     => 'Feature Highlights',
				'category' => 'ticker',
				'emoji'    => '✨',
				'preview'  => '✨ Highlight your key features',
				'fields'   => array(
					'bar_type'    => 'ticker',
					'message'     => '✅ Trusted by 10,000+ customers',
					'bg_color'    => '#0f172a',
					'text_color'  => '#e2e8f0',
					'btn_bg_color'=> '#7c3aed',
					'btn_txt_color'=> '#ffffff',
					'bar_meta'    => array( 'ticker' => array(
						'messages'    => array(
							array( 'text' => '✅ Trusted by 10,000+ customers worldwide', 'cta_label' => '', 'cta_url' => '' ),
							array( 'text' => '🔒 Secure checkout — 256-bit SSL encryption', 'cta_label' => '', 'cta_url' => '' ),
							array( 'text' => '↩ 30-day hassle-free returns', 'cta_label' => 'Our Policy', 'cta_url' => '' ),
							array( 'text' => '💬 24/7 live support ready to help', 'cta_label' => 'Chat Now', 'cta_url' => '' ),
						),
						'speed'       => 5,
						'transition'  => 'slide',
						'pause_hover' => 1,
						'show_arrows' => 1,
						'show_dots'   => 1,
					) ),
				),
			),

			array(
				'id'       => 'carousel-announcements',
				'name'     => 'Announcements',
				'category' => 'ticker',
				'emoji'    => '📢',
				'preview'  => '📢 Multiple announcements rotating',
				'fields'   => array(
					'bar_type'    => 'ticker',
					'message'     => '📢 Important announcement',
					'bg_color'    => '#f59e0b',
					'text_color'  => '#1a1a1a',
					'btn_bg_color'=> '#1a1a1a',
					'btn_txt_color'=> '#f59e0b',
					'bar_meta'    => array( 'ticker' => array(
						'messages'    => array(
							array( 'text' => '🎉 Grand opening sale — this weekend only!', 'cta_label' => 'Get Directions', 'cta_url' => '' ),
							array( 'text' => '📅 New arrivals every Monday — stay tuned', 'cta_label' => '', 'cta_url' => '' ),
							array( 'text' => '🏆 Voted #1 in customer satisfaction 2025', 'cta_label' => 'Read More', 'cta_url' => '' ),
						),
						'speed'       => 4,
						'transition'  => 'fade',
						'pause_hover' => 1,
						'show_arrows' => 1,
						'show_dots'   => 1,
					) ),
				),
			),

			// ── Premium set (2.2.0): gradients, leading icons, light/dark ──────

			array(
				'id'       => 'gradient-mega-sale',
				'name'     => 'Mega Sale (Gradient)',
				'category' => 'announcement',
				'emoji'    => '🔥',
				'preview'  => '🔥 Mega Sale — up to 60% off everything!',
				'fields'   => array(
					'bar_type'      => 'standard',
					'message'       => 'Mega Sale is here — up to <strong>60% off</strong> everything!',
					'cta_label'     => 'Shop the Sale',
					'cta_url'       => '',
					'bg_color'      => '#7c3aed',
					'text_color'    => '#ffffff',
					'btn_bg_color'  => '#ffffff',
					'btn_txt_color' => '#7c3aed',
					'btn_radius'    => 'pill',
					'bar_padding'   => 'normal',
					'font_size'     => 'medium',
					'text_align'    => 'center',
					'elements'      => array( 'icon' => array( 'enabled' => 1, 'value' => '🔥' ) ),
					'bar_meta'      => array( 'gradient' => array(
						'enabled' => 1, 'color1' => '#ec4899', 'color2' => '#8b5cf6', 'type' => 'linear', 'angle' => 135,
					) ),
				),
			),

			array(
				'id'       => 'minimal-light',
				'name'     => 'Minimal (Light)',
				'category' => 'announcement',
				'emoji'    => '◻️',
				'preview'  => 'New collection now available.',
				'fields'   => array(
					'bar_type'      => 'standard',
					'message'       => 'New collection now available.',
					'cta_label'     => 'Browse',
					'cta_url'       => '',
					'bg_color'      => '#ffffff',
					'text_color'    => '#111111',
					'btn_bg_color'  => '#111111',
					'btn_txt_color' => '#ffffff',
					'close_color'   => '#111111',
					'btn_radius'    => 'sharp',
					'bar_padding'   => 'normal',
					'font_size'     => 'medium',
					'text_align'    => 'center',
				),
			),

			array(
				'id'       => 'minimal-dark',
				'name'     => 'Minimal (Dark)',
				'category' => 'announcement',
				'emoji'    => '◼️',
				'preview'  => 'New collection now available.',
				'fields'   => array(
					'bar_type'      => 'standard',
					'message'       => 'New collection now available.',
					'cta_label'     => 'Browse',
					'cta_url'       => '',
					'bg_color'      => '#0a0a0a',
					'text_color'    => '#f5f5f5',
					'btn_bg_color'  => '#f5f5f5',
					'btn_txt_color' => '#0a0a0a',
					'close_color'   => '#f5f5f5',
					'btn_radius'    => 'sharp',
					'bar_padding'   => 'normal',
					'font_size'     => 'medium',
					'text_align'    => 'center',
				),
			),

			array(
				'id'       => 'launch-countdown',
				'name'     => 'Launch Countdown',
				'category' => 'countdown',
				'emoji'    => '🚀',
				'preview'  => '🚀 Launching in: [timer]',
				'fields'   => array(
					'bar_type'      => 'countdown',
					'message'       => 'Launching in:',
					'bg_color'      => '#0ea5e9',
					'text_color'    => '#ffffff',
					'btn_bg_color'  => '#ffffff',
					'btn_txt_color' => '#0369a1',
					'btn_radius'    => 'pill',
					'elements'      => array( 'icon' => array( 'enabled' => 1, 'value' => '🚀' ) ),
					'bar_meta'      => array(
						'gradient'  => array( 'enabled' => 1, 'color1' => '#06b6d4', 'color2' => '#3b82f6', 'type' => 'linear', 'angle' => 120 ),
						'countdown' => array(
							'label_days' => 'Days', 'label_hours' => 'Hrs', 'label_mins' => 'Min', 'label_secs' => 'Sec',
							'show_hours' => 1, 'show_mins' => 1, 'show_seconds' => 1, 'hide_on_expire' => 1,
						),
					),
				),
			),

			array(
				'id'       => 'vip-email',
				'name'     => 'VIP List',
				'category' => 'email_capture',
				'emoji'    => '⭐',
				'preview'  => '⭐ Join the VIP list for early access',
				'fields'   => array(
					'bar_type'      => 'email_capture',
					'message'       => 'Join the VIP list for early access & exclusive drops.',
					'bg_color'      => '#0f172a',
					'text_color'    => '#e2e8f0',
					'elements'      => array( 'icon' => array( 'enabled' => 1, 'value' => '⭐' ) ),
					'bar_meta'      => array( 'email' => array(
						'placeholder' => 'you@example.com', 'btn_label' => 'Join VIP',
						'success_msg' => '🎉 Welcome to the VIP list!', 'provider' => 'none',
					) ),
				),
			),

			array(
				'id'       => 'holiday-coupon',
				'name'     => 'Holiday Gift',
				'category' => 'coupon_copy',
				'emoji'    => '🎁',
				'preview'  => '🎁 Holiday gift: 25% off with HOLIDAY25',
				'fields'   => array(
					'bar_type'      => 'coupon_copy',
					'message'       => 'Our holiday gift to you — <strong>25% off</strong> any order!',
					'bg_color'      => '#e11d48',
					'text_color'    => '#ffffff',
					'elements'      => array( 'icon' => array( 'enabled' => 1, 'value' => '🎁' ) ),
					'bar_meta'      => array(
						'gradient' => array( 'enabled' => 1, 'color1' => '#f43f5e', 'color2' => '#f59e0b', 'type' => 'linear', 'angle' => 95 ),
						'coupon'   => array( 'code' => 'HOLIDAY25', 'btn_label' => 'Copy Code', 'success_label' => '✓ Copied!' ),
					),
				),
			),

			array(
				'id'       => 'gdpr-compact',
				'name'     => 'GDPR Compact',
				'category' => 'gdpr',
				'emoji'    => '🍪',
				'preview'  => 'We use cookies to enhance your experience.',
				'fields'   => array(
					'bar_type'      => 'gdpr',
					'message'       => 'We use cookies to enhance your experience.',
					'bg_color'      => '#1e293b',
					'text_color'    => '#e2e8f0',
					// One-button (Accept only) consent — the Decline button is hidden
					// via the per-element toggle added in 2.2.0.
					'elements'      => array( 'decline' => array( 'enabled' => 0 ) ),
					'bar_meta'      => array( 'gdpr' => array(
						'accept_label' => 'Got it', 'decline_label' => 'Decline',
						'policy_label' => 'Learn more', 'cookie_days' => 365,
					) ),
				),
			),

			array(
				'id'       => 'support-line',
				'name'     => 'Support Line',
				'category' => 'click_to_call',
				'emoji'    => '☎️',
				'preview'  => '☎️ Need help? Call our team',
				'fields'   => array(
					'bar_type'      => 'click_to_call',
					'message'       => 'Need help? Our support team is one tap away.',
					'bg_color'      => '#2563eb',
					'text_color'    => '#ffffff',
					'elements'      => array( 'icon' => array( 'enabled' => 1, 'value' => '☎️' ) ),
					'bar_meta'      => array(
						'gradient' => array( 'enabled' => 1, 'color1' => '#4f46e5', 'color2' => '#2563eb', 'type' => 'linear', 'angle' => 135 ),
						'call'     => array( 'btn_label' => '☎️ Call Support', 'mobile_only' => 0 ),
					),
				),
			),

		);
	}

	/**
	 * Get a single template by ID.
	 *
	 * @param string $template_id Template ID.
	 * @return array|null
	 */
	public static function get_template( $template_id ) {
		foreach ( self::get_all_templates() as $tpl ) {
			if ( $tpl['id'] === $template_id ) {
				return $tpl;
			}
		}
		return null;
	}

	/**
	 * Derive the modal card's preview display from a template's REAL fields.
	 *
	 * This is the single source of truth: the card a user sees is computed from
	 * the same data that applying the template injects, so preview === result.
	 *
	 * @param array $tpl A template from get_all_templates().
	 * @return array { bg, text, btn, btn_text, message, cta, type }
	 */
	public static function get_card_preview( $tpl ) {
		$f    = isset( $tpl['fields'] ) && is_array( $tpl['fields'] ) ? $tpl['fields'] : array();
		$meta = isset( $f['bar_meta'] ) && is_array( $f['bar_meta'] ) ? $f['bar_meta'] : array();
		$type = isset( $f['bar_type'] ) ? $f['bar_type'] : 'standard';

		// Background — a gradient wins when enabled, else the solid colour.
		$bg = isset( $f['bg_color'] ) ? $f['bg_color'] : '#1a73e8';
		if ( ! empty( $meta['gradient']['enabled'] ) ) {
			$g   = $meta['gradient'];
			$c1  = isset( $g['color1'] ) ? $g['color1'] : '#7c3aed';
			$c2  = isset( $g['color2'] ) ? $g['color2'] : '#4f46e5';
			$bg  = ( isset( $g['type'] ) && 'radial' === $g['type'] )
				? 'radial-gradient(circle, ' . $c1 . ', ' . $c2 . ')'
				: 'linear-gradient(' . ( isset( $g['angle'] ) ? (int) $g['angle'] : 135 ) . 'deg, ' . $c1 . ', ' . $c2 . ')';
		}

		// CTA label shown on the card differs per bar type.
		$cta = isset( $f['cta_label'] ) ? $f['cta_label'] : '';
		switch ( $type ) {
			case 'gdpr':
				$cta = isset( $meta['gdpr']['accept_label'] ) ? $meta['gdpr']['accept_label'] : 'Accept All';
				break;
			case 'email_capture':
				$cta = isset( $meta['email']['btn_label'] ) ? $meta['email']['btn_label'] : 'Subscribe';
				break;
			case 'coupon_copy':
				$cta = isset( $meta['coupon']['code'] ) ? $meta['coupon']['code'] : 'SAVE20';
				break;
			case 'click_to_call':
				$cta = isset( $meta['call']['btn_label'] ) ? $meta['call']['btn_label'] : '📞 Call Us';
				break;
			case 'ticker':
				if ( ! empty( $meta['ticker']['messages'][0]['cta_label'] ) ) {
					$cta = $meta['ticker']['messages'][0]['cta_label'];
				}
				break;
		}

		$message = ( isset( $tpl['preview'] ) && is_string( $tpl['preview'] ) )
			? $tpl['preview']
			: ( isset( $f['message'] ) ? $f['message'] : '' );

		return array(
			'bg'       => $bg,
			'text'     => isset( $f['text_color'] ) ? $f['text_color'] : '#ffffff',
			'btn'      => isset( $f['btn_bg_color'] ) ? $f['btn_bg_color'] : '#ffffff',
			'btn_text' => isset( $f['btn_txt_color'] ) ? $f['btn_txt_color'] : '#1a73e8',
			'message'  => $message,
			'cta'      => $cta,
			'type'     => $type,
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// AJAX
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Return a template's fields as JSON so the edit-bar JS
	 * can pre-fill the form.
	 */
	public function ajax_get_template() {
		check_ajax_referer( 'noticepulse_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_die(); }

		$id  = isset( $_POST['template_id'] ) ? sanitize_key( $_POST['template_id'] ) : '';
		$tpl = self::get_template( $id );

		if ( ! $tpl ) {
			wp_send_json_error( array( 'message' => 'Template not found.' ) );
		}

		wp_send_json_success( $tpl );
	}
}