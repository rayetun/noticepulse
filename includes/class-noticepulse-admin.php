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
		add_action( 'admin_post_noticepulse_delete_bar',                  array( $this, 'handle_delete_bar' ) );
		add_action( 'admin_post_noticepulse_toggle_bar',                  array( $this, 'handle_toggle_bar' ) );
		add_action( 'admin_post_noticepulse_reset_stats',                 array( $this, 'handle_reset_stats' ) );
		add_action( 'admin_post_noticepulse_export',                      array( $this, 'handle_export' ) );
		add_action( 'admin_post_noticepulse_import',                      array( $this, 'handle_import' ) );
		add_action( 'admin_post_noticepulse_reset_all',                   array( $this, 'handle_reset_all' ) );
		add_filter( 'plugin_action_links_' . NOTICEPULSE_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_init',                                         array( 'NoticePulse_DB', 'maybe_upgrade_db' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// MENU
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Register admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'NoticePulse', 'noticepulse' ),
			__( 'NoticePulse', 'noticepulse' ),
			'manage_options',
			'noticepulse',
			array( $this, 'render_page' ),
			'dashicons-megaphone',
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
	 * Handle save bar (insert or update).
	 */
	public function handle_save_bar() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'noticepulse' ) );
		}

		check_admin_referer( 'noticepulse_save_bar', 'noticepulse_nonce' );

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

		// Validate name.
		if ( empty( $data['name'] ) ) {
			$redir = $is_new
				? admin_url( 'admin.php?page=noticepulse&action=new&np_notice=missing_name' )
				: admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . $bar_id . '&np_notice=missing_name' );
			wp_safe_redirect( $redir );
			exit;
		}

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

		if ( $is_new ) {
			$result = NoticePulse_DB::insert_bar( $data );
			$notice = $result ? 'saved' : 'error';
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&np_notice=' . $notice ) );
		} else {
			$result = NoticePulse_DB::update_bar( $bar_id, $data );
			$notice = $result ? 'updated' : 'error';
			wp_safe_redirect( admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . $bar_id . '&np_notice=' . $notice ) );
		}
		exit;
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
