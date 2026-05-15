<?php
/**
 * NoticePulse — Click-to-Copy Coupon Bar.
 *
 * Adds data-coupon attribute. np-coupon.js handles the copy UX.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Coupon_Copy {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_data_attributes', array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',        array( $this, 'save_fields' ) );
		add_action( 'wp_enqueue_scripts',               array( $this, 'maybe_enqueue' ), 20 );
	}

	public function add_data_attributes( $attrs, $bar ) {
		if ( ! isset( $bar->bar_type ) || 'coupon_copy' !== $bar->bar_type ) { return $attrs; }
		$meta = NoticePulse_DB::get_meta( $bar, 'coupon' );
		if ( empty( $meta['code'] ) ) { return $attrs; }

		$attrs .= sprintf(
			' data-coupon="%s" data-coupon-btn="%s" data-coupon-success="%s"',
			esc_attr( $meta['code'] ),
			esc_attr( $meta['btn_label']     ?? __( 'Copy Code', 'noticepulse' ) ),
			esc_attr( $meta['success_label'] ?? '✓ Copied!' )
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
		if ( 'coupon_copy' !== $np_bar_type ) { return $data; }

		$values = array(
			'code'          => strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon_code']          ?? '' ) ) ),
			'btn_label'     => sanitize_text_field( wp_unslash( $_POST['coupon_btn_label']                 ?? __( 'Copy Code', 'noticepulse' ) ) ),
			'success_label' => sanitize_text_field( wp_unslash( $_POST['coupon_success_label']             ?? '✓ Copied!' ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'coupon', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'coupon_copy' === $bar->bar_type ) {
				wp_enqueue_script(
					'np-coupon',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-coupon.js',
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
