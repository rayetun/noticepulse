<?php
/**
 * Admin-facing functionality for NoticePulse.
 *
 * v2.0.0: All Pro gates removed. Bar limit removed.
 * Uses NoticePulse_Features instead of NoticePulse_Pro.
 *
 * @package NoticePulse
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NoticePulse_Admin
 */
class NoticePulse_Admin {

	/**
	 * Single instance.
	 *
	 * @var NoticePulse_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return NoticePulse_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu',                                         array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts',                              array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_noticepulse_save_bar',                    array( $this, 'handle_save_bar' ) );
		add_action( 'wp_ajax_noticepulse_save_bar_ajax',                  array( $this, 'handle_save_bar_ajax' ) );
		add_action( 'admin_post_noticepulse_delete_bar',                  array( $this, 'handle_delete_bar' ) );
		add_action( 'admin_post_noticepulse_toggle_bar',                  array( $this, 'handle_toggle_bar' ) );
		add_action( 'wp_ajax_noticepulse_toggle_bar_ajax',                array( $this, 'handle_toggle_bar_ajax' ) );
		add_action( 'admin_post_noticepulse_reset_stats',                 array( $this, 'handle_reset_stats' ) );
		add_action( 'admin_post_noticepulse_export',                      array( $this, 'handle_export' ) );
		add_action( 'admin_post_noticepulse_import',                      array( $this, 'handle_import' ) );
		add_action( 'admin_post_noticepulse_reset_all',                   array( $this, 'handle_reset_all' ) );
		add_filter( 'plugin_action_links_' . NOTICEPULSE_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_init',                                         array( 'NoticePulse_DB', 'maybe_upgrade_db' ) );
		// Keep NoticePulse screens clean of other plugins' admin notices.
		add_action( 'in_admin_header',                                    array( $this, 'suppress_foreign_notices' ), 1000 );
	}

	/**
	 * Remove third-party admin notices on NoticePulse's own screens only.
	 *
	 * Other plugins crowd the top of our dashboard with banners. We strip the
	 * standard notice hooks here (NoticePulse renders its own notices directly
	 * in its views, not via these hooks, so ours are unaffected). Scoped to our
	 * screens only — never the rest of wp-admin.
	 */
	public function suppress_foreign_notices() {
		if ( ! $this->is_noticepulse_screen() ) {
			return;
		}
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}

	/**
	 * Whether the current admin screen belongs to NoticePulse.
	 *
	 * @return bool
	 */
	private function is_noticepulse_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		return $screen && false !== strpos( (string) $screen->id, 'noticepulse' );
	}

	/**
	 * Output a hardcoded inline SVG icon (replaces emoji UI chrome).
	 *
	 * Icons are trusted, static markup (no user data) rendered with currentColor
	 * so they inherit the surrounding text colour.
	 *
	 * @param string $name Icon name.
	 * @param int    $size Pixel size (square).
	 */
	public static function svg( $name, $size = 20 ) {
		$icons = array(
			// Bar types.
			'announcement' => '<path d="m3 11 15-4v10L3 13z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
			'cookie'       => '<path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/><path d="M8.5 8.5v.01"/><path d="M16 15.5v.01"/><path d="M12 12v.01"/><path d="M11 17v.01"/><path d="M7 14v.01"/>',
			'carousel'     => '<path d="M2 7v10"/><path d="M6 5v14"/><rect width="12" height="18" x="10" y="3" rx="2"/>',
			'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
			'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
			'mail'         => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
			'ticket'       => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 11v2"/><path d="M13 17v2"/>',
			// Tabs.
			'edit'         => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
			'palette'      => '<circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2Z"/>',
			'layout'       => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
			'zap'          => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
			'sliders'      => '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
		);
		if ( ! isset( $icons[ $name ] ) ) {
			return;
		}
		// Trusted static SVG markup (no user input) — safe to output verbatim.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo sprintf(
			'<svg class="np-ico" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
			(int) $size,
			$icons[ $name ]
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the "support this plugin" card.
	 *
	 * Shared across All Bars, Analytics, and Settings screens so the call to
	 * action stays consistent. Links: review + support on WordPress.org,
	 * donation via Wise.
	 */
	public static function support_section() {
		$review_url  = 'https://wordpress.org/support/plugin/noticepulse/reviews/#new-post';
		$donate_url  = 'https://wise.com/pay/me/mdrayhanu2';
		$support_url = 'https://wordpress.org/support/plugin/noticepulse/';

		// Trusted static SVG markup (no user input) — safe to output verbatim.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$heart   = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
		$star    = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2l2.9 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l7.1-1.01L12 2z"/></svg>';
		$coffee  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M10 2v2"/><path d="M14 2v2"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h12z"/><path d="M17 9h1a3 3 0 0 1 0 6h-1"/></svg>';
		$lifebuoy = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"/><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"/><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"/><line x1="4.93" y1="19.07" x2="9.17" y2="14.83"/></svg>';
		?>
		<div class="np-support">
			<div class="np-support__icon"><?php echo $heart; ?></div>
			<div class="np-support__text">
				<h3><?php esc_html_e( 'Enjoying NoticePulse?', 'noticepulse' ); ?></h3>
				<p><?php esc_html_e( 'NoticePulse is free and built by an independent developer. A quick review or a small donation helps keep it maintained and updated. Thank you for your support!', 'noticepulse' ); ?></p>
			</div>
			<div class="np-support__actions">
				<a class="np-support__btn np-support__btn--review" href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo $star; ?><?php esc_html_e( 'Leave a Review', 'noticepulse' ); ?>
				</a>
				<a class="np-support__btn np-support__btn--donate" href="<?php echo esc_url( $donate_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo $coffee; ?><?php esc_html_e( 'Donate', 'noticepulse' ); ?>
				</a>
				<a class="np-support__btn np-support__btn--support" href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo $lifebuoy; ?><?php esc_html_e( 'Get Support', 'noticepulse' ); ?>
				</a>
			</div>
		</div>
		<?php
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// ─────────────────────────────────────────────────────────────────────────
	// MENU
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Register admin menu.
	 */
	public function register_menu() {
		// Brand bell mark for the admin menu (white fill; WordPress dims it to
		// ~60% normally and full white when active, matching native menu icons).
		$menu_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><path fill="#fff" d="M128 44c-34 0-58 27-58 61 0 26-5 40-17 54-8 9-2 21 11 21h128c13 0 19-12 11-21-12-14-17-28-17-54 0-34-24-61-58-61z"/><path fill="#fff" d="M106 188h44c0 13-10 23-22 23s-22-10-22-23z"/></svg>';
		$menu_icon     = 'data:image/svg+xml;base64,' . base64_encode( $menu_icon_svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Inline SVG data URI, not obfuscation.

		add_menu_page(
			__( 'NoticePulse', 'noticepulse' ),
			__( 'NoticePulse', 'noticepulse' ),
			'manage_options',
			'noticepulse',
			array( $this, 'render_page' ),
			$menu_icon,
			81
		);

		add_submenu_page(
			'noticepulse',
			__( 'All Bars', 'noticepulse' ),
			__( 'All Bars', 'noticepulse' ),
			'manage_options',
			'noticepulse',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'noticepulse',
			__( 'Add New Bar', 'noticepulse' ),
			__( '+ Add New', 'noticepulse' ),
			'manage_options',
			'noticepulse&action=new',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			'noticepulse',
			__( 'Analytics', 'noticepulse' ),
			__( 'Analytics', 'noticepulse' ),
			'manage_options',
			'noticepulse-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'noticepulse',
			__( 'Settings & Tools', 'noticepulse' ),
			__( 'Settings', 'noticepulse' ),
			'manage_options',
			'noticepulse-settings',
			array( $this, 'render_settings_page' )
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// ASSETS
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enqueue admin CSS and JS.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'noticepulse' ) ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		// Core admin CSS.
		wp_enqueue_style(
			'noticepulse-admin',
			NOTICEPULSE_PLUGIN_URL . 'admin/css/noticepulse-admin.css',
			array( 'wp-color-picker' ),
			NOTICEPULSE_VERSION
		);
		// Core admin JS.
		wp_enqueue_script(
			'noticepulse-admin',
			NOTICEPULSE_PLUGIN_URL . 'admin/js/noticepulse-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			NOTICEPULSE_VERSION,
			true
		);
		wp_localize_script(
			'noticepulse-admin',
			'noticepulseAdmin',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'noticepulse_admin' ),
				'confirmDelete'     => __( 'Delete this bar? This cannot be undone.', 'noticepulse' ),
				'confirmResetStats' => __( 'Reset analytics for this bar?', 'noticepulse' ),
				'pluginUrl'         => NOTICEPULSE_PLUGIN_URL,
			)
		);
		// Edit-bar screen only: bar-type fields JS + template library.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			// Bar-type field switching JS.
			wp_enqueue_script(
				'noticepulse-edit-bar',
				NOTICEPULSE_PLUGIN_URL . 'admin/js/noticepulse-edit-bar.js',
				array( 'noticepulse-admin' ),
				NOTICEPULSE_VERSION,
				true
			);
			wp_localize_script(
				'noticepulse-edit-bar',
				'noticepulseMsgPlaceholders',
				array(
					'standard'      => __( '🎉 Free shipping on all orders today!', 'noticepulse' ),
					'gdpr'          => __( 'We use cookies to improve your experience.', 'noticepulse' ),
					'ticker'        => __( 'Shown in the preview — your carousel messages appear here.', 'noticepulse' ),
					'click_to_call' => __( 'Get a free quote today! Call us now.', 'noticepulse' ),
					'countdown'     => __( '⏳ Sale ends in:', 'noticepulse' ),
					'email_capture' => __( '📧 Get 10% off — join our newsletter!', 'noticepulse' ),
					'coupon_copy'   => __( '🎟 Use code below for 20% off your order!', 'noticepulse' ),
				)
			);
			// Template library CSS — modal, cards, confirm dialog, toasts.
			wp_enqueue_style(
				'np-templates',
				NOTICEPULSE_PLUGIN_URL . 'admin/css/np-templates.css',
				array( 'noticepulse-admin' ),
				NOTICEPULSE_VERSION
			);
			// Template library JS — depends on noticepulse-edit-bar so bar-type
			// switching is already wired before the template modal boots.
			wp_enqueue_script(
				'np-templates',
				NOTICEPULSE_PLUGIN_URL . 'admin/js/np-templates.js',
				array( 'jquery', 'noticepulse-edit-bar' ),
				NOTICEPULSE_VERSION,
				true
			);
			// AJAX save — no-reload Publish/Update. Depends on noticepulse-admin
			// so its validation submit handler binds first (we respect its cancel).
			wp_enqueue_script(
				'np-ajax-save',
				NOTICEPULSE_PLUGIN_URL . 'admin/js/np-ajax-save.js',
				array( 'jquery', 'noticepulse-admin' ),
				NOTICEPULSE_VERSION,
				true
			);
			wp_localize_script(
				'np-ajax-save',
				'noticepulseAjaxSave',
				array(
					'saving'    => __( 'Saving…', 'noticepulse' ),
					'publish'   => __( 'Publish Bar', 'noticepulse' ),
					'update'    => __( 'Update Bar', 'noticepulse' ),
					'editTitle' => __( 'Edit Bar', 'noticepulse' ),
					'network'   => __( 'Network error. Please try again.', 'noticepulse' ),
					'generic'   => __( 'Could not save. Please try again.', 'noticepulse' ),
				)
			);
			// WYSIWYG live preview — renders a real .np-bar inside an iframe that
			// loads the REAL public stylesheet, so the preview matches the front end.
			wp_enqueue_style(
				'np-live-preview',
				NOTICEPULSE_PLUGIN_URL . 'admin/css/np-live-preview.css',
				array( 'noticepulse-admin' ),
				NOTICEPULSE_VERSION
			);
			wp_enqueue_script(
				'np-live-preview',
				NOTICEPULSE_PLUGIN_URL . 'admin/js/np-live-preview.js',
				array( 'jquery', 'noticepulse-admin' ),
				NOTICEPULSE_VERSION,
				true
			);
			wp_localize_script(
				'np-live-preview',
				'noticepulsePreview',
				array(
					'publicCss' => NOTICEPULSE_PLUGIN_URL . 'public/css/noticepulse-public.css?ver=' . NOTICEPULSE_VERSION,
				)
			);
		}
		// Premium dashboard JS.
		wp_enqueue_script(
			'noticepulse-pro-admin',
			NOTICEPULSE_PLUGIN_URL . 'admin/js/noticepulse-pro-admin.js',
			array(),
			NOTICEPULSE_VERSION,
			true
		);
		/**
		 * Allow feature classes to enqueue their own admin assets.
		 *
		 * @since 2.0.0
		 * @param string $hook Current admin page hook.
		 */
		do_action( 'noticepulse_admin_enqueue_scripts', $hook );

		// Settings page JS — file drop zone for import.
		if ( false !== strpos( $hook, 'noticepulse-settings' ) ) {
			wp_enqueue_script(
				'np-settings-admin',
				NOTICEPULSE_PLUGIN_URL . 'admin/js/np-settings-admin.js',
				array(),
				NOTICEPULSE_VERSION,
				true
			);
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// PAGE ROUTER
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Main page router.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$bar_id = isset( $_GET['bar_id'] ) ? absint( $_GET['bar_id'] ) : 0;

		if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
			$this->render_edit_page( $bar_id );
		} else {
			$this->render_list_page();
		}
	}

	/**
	 * List page.
	 */
	private function render_list_page() {
		$bars  = NoticePulse_DB::get_all_bars();
		$stats = NoticePulse_Analytics::get_all_stats();
		$count = NoticePulse_DB::count_bars();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['np_notice'] ) ? sanitize_key( $_GET['np_notice'] ) : '';
		include NOTICEPULSE_PLUGIN_DIR . 'admin/views/list-bars.php';
	}

	/**
	 * Edit / Add new bar page.
	 *
	 * @param int $bar_id Bar ID (0 for new).
	 */
	private function render_edit_page( $bar_id = 0 ) {
		$bar    = null;
		$is_new = ! $bar_id;

		if ( $bar_id ) {
			$bar = NoticePulse_DB::get_bar( $bar_id );
			if ( ! $bar ) {
				wp_die( esc_html__( 'Bar not found.', 'noticepulse' ) );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['np_notice'] ) ? sanitize_key( $_GET['np_notice'] ) : '';
		include NOTICEPULSE_PLUGIN_DIR . 'admin/views/edit-bar.php';
	}

	/**
	 * Analytics page.
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) );
		}
		include NOTICEPULSE_PLUGIN_DIR . 'admin/views/analytics.php';
	}

	/**
	 * Settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) );
		}
		include NOTICEPULSE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SAVE BAR
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Build a sanitized bar-data array from the current request ($_POST).
	 *
	 * Shared by the classic admin-post handler ({@see handle_save_bar()}) and
	 * the AJAX handler ({@see handle_save_bar_ajax()}) so both paths sanitize
	 * and validate identically. The nonce and capability are verified by the
	 * caller *before* this method runs — hence the NonceVerification disable.
	 *
	 * @return array {
	 *     @type int    $bar_id Target bar ID (0 for a new bar).
	 *     @type bool   $is_new Whether this request creates a new bar.
	 *     @type array  $data   Sanitized column data (incl. bar_meta via filter).
	 *     @type string $error  Notice key when invalid, '' when valid.
	 * }
	 */
	private function build_bar_data_from_request() {
		// Nonce + capability are checked by the calling handler before this runs.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
		$bar_id = isset( $_POST['bar_id'] ) ? absint( $_POST['bar_id'] ) : 0;
		$is_new = ! $bar_id;

		// All bar types accepted — feature classes add their own via filter.
		$valid_types = array(
			'standard', 'gdpr', 'ticker', 'click_to_call',
			'countdown', 'email_capture', 'coupon_copy',
		);

		$raw_type = sanitize_key( wp_unslash( $_POST['bar_type'] ?? 'standard' ) );
		$bar_type = in_array( $raw_type, $valid_types, true ) ? $raw_type : 'standard';

		// Core fields.
		$data = array(
			'name'          => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'bar_type'      => $bar_type,
			'message'       => wp_kses( wp_unslash( $_POST['message'] ?? '' ), array(
				'strong' => array(),
				'em'     => array(),
				'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
				'span'   => array( 'class' => array(), 'style' => array() ),
				'br'     => array(),
			) ),
			'cta_label'     => sanitize_text_field( wp_unslash(
								// Countdown type uses separate field names to avoid conflict with
								// the hidden standard CTA fields that always submit in the form.
								'countdown' === $bar_type
									? ( $_POST['countdown_cta_label'] ?? '' )
									: ( $_POST['cta_label'] ?? '' )
							) ),
			'cta_url'       => esc_url_raw( wp_unslash(
								'countdown' === $bar_type
									? ( $_POST['countdown_cta_url'] ?? '' )
									: ( $_POST['cta_url'] ?? '' )
							) ),
			'cta_target'    => in_array( wp_unslash( $_POST['cta_target'] ?? '' ), array( '_self', '_blank' ), true )
							   ? sanitize_key( wp_unslash( $_POST['cta_target'] ) ) : '_self',
			'position'      => in_array( wp_unslash( $_POST['position'] ?? '' ), array( 'top', 'bottom' ), true )
							   ? sanitize_key( wp_unslash( $_POST['position'] ) ) : 'top',
			'is_sticky'     => isset( $_POST['is_sticky'] )    ? 1 : 0,
			'show_desktop'  => isset( $_POST['show_desktop'] ) ? 1 : 0,
			'show_tablet'   => isset( $_POST['show_tablet'] )  ? 1 : 0,
			'show_mobile'   => isset( $_POST['show_mobile'] )  ? 1 : 0,
			'show_close'    => isset( $_POST['show_close'] )   ? 1 : 0,
			'cookie_days'   => absint( $_POST['cookie_days'] ?? 7 ),
			'visibility'    => in_array( wp_unslash( $_POST['visibility'] ?? '' ), array( 'all', 'specific' ), true )
							   ? sanitize_key( wp_unslash( $_POST['visibility'] ) ) : 'all',
			'page_ids'      => preg_replace( '/[^0-9,]/', '', sanitize_text_field( wp_unslash( $_POST['page_ids'] ?? '' ) ) ),
			'user_status'   => in_array( wp_unslash( $_POST['user_status'] ?? '' ), array( 'all', 'logged_in', 'logged_out' ), true )
							   ? sanitize_key( wp_unslash( $_POST['user_status'] ) ) : 'all',
			'font_size'     => in_array( wp_unslash( $_POST['font_size'] ?? '' ), array( 'small', 'medium', 'large' ), true )
							   ? sanitize_key( wp_unslash( $_POST['font_size'] ) ) : 'medium',
			'bar_padding'   => in_array( wp_unslash( $_POST['bar_padding'] ?? '' ), array( 'compact', 'normal', 'tall' ), true )
							   ? sanitize_key( wp_unslash( $_POST['bar_padding'] ) ) : 'normal',
			'btn_radius'    => in_array( wp_unslash( $_POST['btn_radius'] ?? '' ), array( 'sharp', 'rounded', 'pill' ), true )
							   ? sanitize_key( wp_unslash( $_POST['btn_radius'] ) ) : 'rounded',
			'text_align'    => in_array( wp_unslash( $_POST['text_align'] ?? '' ), array( 'left', 'center', 'right' ), true )
							   ? sanitize_key( wp_unslash( $_POST['text_align'] ) ) : 'center',
			'bg_color'      => sanitize_hex_color( wp_unslash( $_POST['bg_color']      ?? '#1a73e8' ) ),
			'text_color'    => sanitize_hex_color( wp_unslash( $_POST['text_color']    ?? '#ffffff' ) ),
			'btn_bg_color'  => sanitize_hex_color( wp_unslash( $_POST['btn_bg_color']  ?? '#ffffff' ) ),
			'btn_txt_color' => sanitize_hex_color( wp_unslash( $_POST['btn_txt_color'] ?? '#1a73e8' ) ),
			'close_color'   => sanitize_hex_color( wp_unslash( $_POST['close_color']   ?? '#ffffff' ) ),
			'is_active'     => isset( $_POST['is_active'] ) ? 1 : 0,
			'bar_meta'      => '',
		);

		// Dates.
		$ds             = sanitize_text_field( wp_unslash( $_POST['date_start'] ?? '' ) );
		$de             = sanitize_text_field( wp_unslash( $_POST['date_end']   ?? '' ) );
		$data['date_start'] = ! empty( $ds ) ? gmdate( 'Y-m-d H:i:s', strtotime( $ds ) ) : null;
		$data['date_end']   = ! empty( $de ) ? gmdate( 'Y-m-d H:i:s', strtotime( $de ) ) : null;

		// Validate name — callers decide how to surface the error.
		$error = empty( $data['name'] ) ? 'missing_name' : '';

		// Per-element visibility (core elements: icon / message / CTA). Stored in
		// bar_meta.elements BEFORE the feature filters so their set_meta() merges
		// preserve it. Backward-compatible: bars saved before 2.2.0 have no
		// 'elements' key and the renderer/preview fall back to today's behaviour.
		$elements = array(
			'icon'    => array(
				'enabled' => isset( $_POST['element_icon_enabled'] ) ? 1 : 0,
				'value'   => sanitize_text_field( wp_unslash( $_POST['element_icon_value'] ?? '' ) ),
			),
			'message' => array( 'enabled' => isset( $_POST['element_message_enabled'] ) ? 1 : 0 ),
			'cta'     => array( 'enabled' => isset( $_POST['element_cta_enabled'] ) ? 1 : 0 ),
			'decline' => array( 'enabled' => isset( $_POST['element_decline_enabled'] ) ? 1 : 0 ),
		);
		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'], 'elements', $elements );

		/**
		 * Allow feature classes to save their extra fields into bar_meta.
		 *
		 * Each feature class hooks here, reads its own POST fields, sanitizes
		 * them, and stores them in $data['bar_meta'] using NoticePulse_DB::set_meta().
		 *
		 * @since 2.0.0
		 * @param array $data Full bar data array including 'bar_meta'.
		 */
		$data = apply_filters( 'noticepulse_save_bar_data', $data );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

		return array(
			'bar_id' => $bar_id,
			'is_new' => $is_new,
			'data'   => $data,
			'error'  => $error,
		);
	}

	/**
	 * Handle save bar via classic admin-post (full-page redirect; no-JS fallback).
	 */
	public function handle_save_bar() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) );
		}
		check_admin_referer( 'noticepulse_save_bar', 'noticepulse_nonce' );

		$built  = $this->build_bar_data_from_request();
		$bar_id = $built['bar_id'];
		$is_new = $built['is_new'];

		if ( '' !== $built['error'] ) {
			$redir = $is_new
				? admin_url( 'admin.php?page=noticepulse&action=new&np_notice=' . $built['error'] )
				: admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . $bar_id . '&np_notice=' . $built['error'] );
			wp_safe_redirect( $redir );
			exit;
		}

		if ( $is_new ) {
			$result = NoticePulse_DB::insert_bar( $built['data'] );
			$notice = $result ? 'saved' : 'error';
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&np_notice=' . $notice ) );
		} else {
			$result = NoticePulse_DB::update_bar( $bar_id, $built['data'] );
			$notice = $result ? 'updated' : 'error';
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . $bar_id . '&np_notice=' . $notice ) );
		}
		exit;
	}

	/**
	 * Handle save bar via AJAX (no page reload).
	 *
	 * Reuses the SAME form nonce the edit-bar form already prints
	 * (field `noticepulse_nonce`, action `noticepulse_save_bar`), so the
	 * serialized form validates without a second nonce. When JS is
	 * unavailable the form posts to {@see handle_save_bar()} instead.
	 */
	public function handle_save_bar_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'notice'  => 'error',
				'message' => __( 'Permission denied.', 'noticepulse' ),
			), 403 );
		}
		check_ajax_referer( 'noticepulse_save_bar', 'noticepulse_nonce' );

		$built  = $this->build_bar_data_from_request();
		$bar_id = $built['bar_id'];
		$is_new = $built['is_new'];

		if ( '' !== $built['error'] ) {
			wp_send_json_error( array(
				'notice'  => $built['error'],
				'message' => __( 'Bar name is required.', 'noticepulse' ),
			) );
		}

		if ( $is_new ) {
			$new_id = NoticePulse_DB::insert_bar( $built['data'] );
			if ( ! $new_id ) {
				wp_send_json_error( array(
					'notice'  => 'error',
					'message' => __( 'An error occurred. Please try again.', 'noticepulse' ),
				) );
			}
			wp_send_json_success( array(
				'bar_id'   => (int) $new_id,
				'is_new'   => true,
				'notice'   => 'saved',
				'message'  => __( 'Bar created successfully.', 'noticepulse' ),
				'edit_url' => admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . (int) $new_id ),
			) );
		}

		$result = NoticePulse_DB::update_bar( $bar_id, $built['data'] );
		if ( ! $result ) {
			wp_send_json_error( array(
				'notice'  => 'error',
				'message' => __( 'An error occurred. Please try again.', 'noticepulse' ),
			) );
		}
		wp_send_json_success( array(
			'bar_id'  => (int) $bar_id,
			'is_new'  => false,
			'notice'  => 'updated',
			'message' => __( 'Bar updated successfully.', 'noticepulse' ),
		) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// OTHER ACTIONS
	// ─────────────────────────────────────────────────────────────────────────

	/** Handle bar deletion. */
	public function handle_delete_bar() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		$bar_id = isset( $_GET['bar_id'] ) ? absint( $_GET['bar_id'] ) : 0;
		check_admin_referer( 'noticepulse_delete_bar_' . $bar_id );
		if ( $bar_id ) { NoticePulse_DB::delete_bar( $bar_id ); }
		wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&np_notice=deleted' ) );
		exit;
	}

	/** Handle bar toggle. */
	public function handle_toggle_bar() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		$bar_id = isset( $_GET['bar_id'] ) ? absint( $_GET['bar_id'] ) : 0;
		check_admin_referer( 'noticepulse_toggle_bar_' . $bar_id );
		if ( $bar_id ) {
			$bar = NoticePulse_DB::get_bar( $bar_id );
			if ( $bar ) { NoticePulse_DB::update_bar( $bar_id, array( 'is_active' => $bar->is_active ? 0 : 1 ) ); }
		}
		wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&np_notice=toggled' ) );
		exit;
	}

	/** Handle bar toggle via AJAX (no page reload — the list uses a toast). */
	public function handle_toggle_bar_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'noticepulse' ) ), 403 );
		}
		check_ajax_referer( 'noticepulse_admin', 'nonce' );
		$bar_id = isset( $_POST['bar_id'] ) ? absint( $_POST['bar_id'] ) : 0;
		$bar    = $bar_id ? NoticePulse_DB::get_bar( $bar_id ) : null;
		if ( ! $bar ) {
			wp_send_json_error( array( 'message' => __( 'Bar not found.', 'noticepulse' ) ) );
		}
		$new = $bar->is_active ? 0 : 1;
		NoticePulse_DB::update_bar( $bar_id, array( 'is_active' => $new ) );
		wp_send_json_success( array(
			'bar_id'  => $bar_id,
			'active'  => $new,
			'message' => $new
				? __( 'Bar activated.', 'noticepulse' )
				: __( 'Bar deactivated.', 'noticepulse' ),
		) );
	}

	/** Handle stats reset. */
	public function handle_reset_stats() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		$bar_id = isset( $_GET['bar_id'] ) ? absint( $_GET['bar_id'] ) : 0;
		check_admin_referer( 'noticepulse_reset_stats_' . $bar_id );
		if ( $bar_id ) { NoticePulse_Analytics::reset_bar_stats( $bar_id ); }
		wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&np_notice=stats_reset' ) );
		exit;
	}

	/** Plugin action links. */
	public function plugin_action_links( $links ) {
		return array_merge(
			array( '<a href="' . esc_url( admin_url( 'admin.php?page=noticepulse' ) ) . '">' . esc_html__( 'Manage Bars', 'noticepulse' ) . '</a>' ),
			$links
		);
	}

	/**
	 * Render an admin notice from a notice key.
	 *
	 * @param string $notice Notice key.
	 */
	public function render_notice( $notice ) {
		$messages = array(
			'saved'        => array( 'success', __( 'Bar created successfully.', 'noticepulse' ) ),
			'updated'      => array( 'success', __( 'Bar updated successfully.', 'noticepulse' ) ),
			'deleted'      => array( 'success', __( 'Bar deleted.', 'noticepulse' ) ),
			'toggled'      => array( 'success', __( 'Bar status updated.', 'noticepulse' ) ),
			'stats_reset'  => array( 'success', __( 'Analytics reset.', 'noticepulse' ) ),
			'all_reset'    => array( 'success', __( 'All data deleted.', 'noticepulse' ) ),
			'imported'     => array( 'success', __( 'Bars imported successfully.', 'noticepulse' ) ),
			'import_error' => array( 'error',   __( 'Import failed — check the file.', 'noticepulse' ) ),
			'error'        => array( 'error',   __( 'An error occurred. Please try again.', 'noticepulse' ) ),
			'missing_name' => array( 'error',   __( 'Bar name is required.', 'noticepulse' ) ),
		);
		if ( ! isset( $messages[ $notice ] ) ) { return; }
		list( $type, $message ) = $messages[ $notice ];
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/** Handle JSON export. */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		check_admin_referer( 'noticepulse_export', 'noticepulse_export_nonce' );
		$bars     = NoticePulse_DB::get_all_bars();
		$filename = 'noticepulse-export-' . gmdate( 'Y-m-d' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Pragma: no-cache' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_json_encode( array( 'version' => NOTICEPULSE_VERSION, 'exported' => gmdate( 'Y-m-d H:i:s' ), 'bars' => $bars ), JSON_PRETTY_PRINT );
		exit;
	}

	/** Handle JSON import. */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		check_admin_referer( 'noticepulse_import', 'noticepulse_import_nonce' );

		if ( empty( $_FILES['np_import_file']['tmp_name'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse-settings&np_notice=import_error' ) ); exit;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tmp = $_FILES['np_import_file']['tmp_name'];
		if ( ! is_uploaded_file( $tmp ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse-settings&np_notice=import_error' ) ); exit;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw  = file_get_contents( $tmp );
		$data = $raw ? json_decode( $raw, true ) : null;
		if ( JSON_ERROR_NONE !== json_last_error() || empty( $data['bars'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse-settings&np_notice=import_error' ) ); exit;
		}

		$imported = 0;
		foreach ( (array) $data['bars'] as $bar ) {
			if ( ! is_array( $bar ) ) { continue; }
			$insert = array();

			// Plain text fields.
			foreach ( array( 'name', 'cta_label', 'cta_target', 'position', 'visibility',
				'page_ids', 'user_status', 'font_size', 'bar_padding', 'btn_radius',
				'text_align', 'date_start', 'date_end' ) as $col ) {
				if ( isset( $bar[ $col ] ) ) {
					$insert[ $col ] = sanitize_text_field( (string) $bar[ $col ] );
				}
			}

			// Integer fields.
			foreach ( array( 'is_sticky', 'show_desktop', 'show_tablet', 'show_mobile',
				'show_close', 'cookie_days', 'is_active', 'sort_order' ) as $col ) {
				if ( isset( $bar[ $col ] ) ) {
					$insert[ $col ] = absint( $bar[ $col ] );
				}
			}

			// Colour fields.
			foreach ( array( 'bg_color', 'text_color', 'btn_bg_color', 'btn_txt_color', 'close_color' ) as $col ) {
				if ( isset( $bar[ $col ] ) ) {
					$sanitized      = sanitize_hex_color( (string) $bar[ $col ] );
					$insert[ $col ] = $sanitized ? $sanitized : '#000000';
				}
			}

			// Message — preserve safe HTML.
			if ( isset( $bar['message'] ) ) {
				$insert['message'] = wp_kses( (string) $bar['message'], array(
					'strong' => array(), 'em' => array(), 'br' => array(),
					'a'      => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
					'span'   => array( 'class' => array(), 'style' => array() ),
				) );
			}

			// URL field.
			if ( isset( $bar['cta_url'] ) ) {
				$insert['cta_url'] = esc_url_raw( (string) $bar['cta_url'] );
			}

			// bar_type — allowlisted values only.
			$valid_bar_types = array( 'standard', 'gdpr', 'ticker', 'click_to_call',
				'countdown', 'email_capture', 'coupon_copy' );
			if ( isset( $bar['bar_type'] ) && in_array( $bar['bar_type'], $valid_bar_types, true ) ) {
				$insert['bar_type'] = $bar['bar_type'];
			}

			// bar_meta — stored as JSON string.
			// IMPORTANT: Do NOT use sanitize_text_field() on this field.
			// It strips characters like { } : " which are required by JSON.
			if ( ! empty( $bar['bar_meta'] ) ) {
				$meta_str = is_array( $bar['bar_meta'] )
					? wp_json_encode( $bar['bar_meta'] )
					: (string) $bar['bar_meta'];
				$decoded = json_decode( $meta_str, true );
				if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
					$insert['bar_meta'] = wp_json_encode( $decoded );
				}
			}

			if ( ! empty( $insert ) ) {
				NoticePulse_DB::insert_bar( $insert );
				++$imported;
			}
		}
		
		wp_safe_redirect( admin_url( 'admin.php?page=noticepulse-settings&np_notice=' . ( $imported > 0 ? 'imported' : 'import_error' ) ) );
		exit;
	}

	/** Handle reset all. */
	public function handle_reset_all() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) ); }
		check_admin_referer( 'noticepulse_reset_all' );
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $wpdb->prefix . NoticePulse_DB::LEADS_TABLE ) );
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $wpdb->prefix . NoticePulse_DB::BARS_TABLE ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable
		wp_safe_redirect( admin_url( 'admin.php?page=noticepulse-settings&np_notice=all_reset' ) );
		exit;
	}
}
