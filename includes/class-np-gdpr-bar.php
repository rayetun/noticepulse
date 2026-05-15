<?php
/**
 * NoticePulse — GDPR / Cookie Consent Bar.
 *
 * Registers into the free plugin hook API automatically.
 * Adds Accept/Decline buttons, stores consent cookie.
 *
 * @package NoticePulse
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_GDPR_Bar {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded',                  array( $this, 'maybe_add_meta_column' ) );
		add_filter( 'noticepulse_bar_data_attributes', array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',       array( $this, 'save_fields' ) );
		add_action( 'wp_enqueue_scripts',              array( $this, 'maybe_enqueue' ), 20 );
	}

	/**
	 * Add bar_meta column if it doesn't exist yet.
	 *
	 * This is a one-time schema migration that runs on plugins_loaded and
	 * is guarded by an option flag so it only ever executes once per site.
	 * The SHOW COLUMNS and ALTER TABLE calls are unavoidable for a migration
	 * that must be compatible with existing installations; they are suppressed
	 * with inline phpcs:ignore rather than globally disabling the sniff.
	 *
	 * FIX: Table name passed via %i (identifier placeholder, WP 6.2+) inside
	 * prepare() instead of raw string interpolation.
	 */
	public function maybe_add_meta_column() {
		if ( get_option( 'noticepulse_bar_meta_column', false ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . NoticePulse_DB::BARS_TABLE;

		// Check if column already exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cols = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SHOW COLUMNS FROM %i',
				$table
			),
			0
		);

		if ( ! in_array( 'bar_meta', (array) $cols, true ) ) {
			// One-time schema migration — ALTER TABLE is intentional here.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$wpdb->query(
				$wpdb->prepare(
				    // @codingStandardsIgnoreStart
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'ALTER TABLE %i ADD COLUMN bar_meta LONGTEXT NOT NULL DEFAULT \'\'',
					$table
					// @codingStandardsIgnoreEnd
				)
			);
		}

		update_option( 'noticepulse_bar_meta_column', true );
	}

	public function add_data_attributes( $attrs, $bar ) {
		if ( ! isset( $bar->bar_type ) || 'gdpr' !== $bar->bar_type ) { return $attrs; }
		$meta = NoticePulse_DB::get_meta( $bar, 'gdpr' );

		$attrs .= sprintf(
			' data-gdpr="1" data-gdpr-accept="%s" data-gdpr-decline="%s" data-gdpr-policy-url="%s" data-gdpr-policy-label="%s" data-gdpr-cookie="%s" data-gdpr-days="%d"',
			esc_attr( $meta['accept_label']  ?? __( 'Accept All', 'noticepulse' ) ),
			esc_attr( $meta['decline_label'] ?? __( 'Decline', 'noticepulse' ) ),
			esc_url(  $meta['policy_url']    ?? get_privacy_policy_url() ),
			esc_attr( $meta['policy_label']  ?? __( 'Privacy Policy', 'noticepulse' ) ),
			esc_attr( $meta['cookie_name']   ?? 'np_gdpr_consent' ),
			absint(   $meta['cookie_days']   ?? 365 )
		);
		return $attrs;
	}

	public function save_fields( $data ) {
		// Verify nonce — the same nonce submitted with the bar save form.
		// check_admin_referer() is also called upstream in handle_save_bar()
		// before this filter fires, so this is a belt-and-braces check.
		if (
			! isset( $_POST['noticepulse_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_key( $_POST['noticepulse_nonce'] ),
				'noticepulse_save_bar'
			)
		) {
			return $data;
		}

		// FIX: wp_unslash() + sanitize_key() on bar_type before comparison.
		$np_bar_type = sanitize_key( wp_unslash( $_POST['bar_type'] ?? '' ) );
		if ( 'gdpr' !== $np_bar_type ) { return $data; }

		$values = array(
			'accept_label'  => sanitize_text_field( wp_unslash( $_POST['gdpr_accept_label']  ?? __( 'Accept All', 'noticepulse' ) ) ),
			'decline_label' => sanitize_text_field( wp_unslash( $_POST['gdpr_decline_label'] ?? __( 'Decline', 'noticepulse' ) ) ),
			'policy_url'    => esc_url_raw( wp_unslash( $_POST['gdpr_policy_url']            ?? get_privacy_policy_url() ) ),
			'policy_label'  => sanitize_text_field( wp_unslash( $_POST['gdpr_policy_label']  ?? __( 'Privacy Policy', 'noticepulse' ) ) ),
			'cookie_name'   => sanitize_key( wp_unslash( $_POST['gdpr_cookie_name']          ?? 'np_gdpr_consent' ) ),
			'cookie_days'   => min( 3650, max( 1, absint( $_POST['gdpr_cookie_days']         ?? 365 ) ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'gdpr', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'gdpr' === $bar->bar_type ) {
				wp_enqueue_script(
					'noticepulse-gdpr',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-gdpr.js',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION,
					true
				);
				// FIX: noticepulse-pro-public merged into noticepulse-public — reference updated.
				wp_enqueue_style(
					'noticepulse-public',
					NOTICEPULSE_PLUGIN_URL . 'public/css/noticepulse-public.css',
					array(),
					NOTICEPULSE_VERSION
				);
				return;
			}
		}
	}
}
