<?php
/**
 * NoticePulse — Click-to-Call Bar.
 *
 * Adds data-phone-* attributes to the bar element.
 * np-click-to-call.js reads them and builds the phone button.
 *
 * @package NoticePulse
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Click_To_Call {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_data_attributes', array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',       array( $this, 'save_fields' ) );
		add_action( 'wp_enqueue_scripts',              array( $this, 'maybe_enqueue' ), 20 );
	}

	public function add_data_attributes( $attrs, $bar ) {
		if ( ! isset( $bar->bar_type ) || 'click_to_call' !== $bar->bar_type ) { return $attrs; }
		$meta  = NoticePulse_DB::get_meta( $bar, 'call' );
		$phone = isset( $meta['phone'] ) ? trim( $meta['phone'] ) : '';
		if ( empty( $phone ) ) { return $attrs; }

		$tel = preg_replace( '/[^0-9+]/', '', $phone );
		$attrs .= sprintf(
			' data-phone="%s" data-phone-tel="%s" data-phone-btn="%s" data-phone-mobile-only="%d" data-phone-hours="%s"',
			esc_attr( $phone ),
			esc_attr( $tel ),
			esc_attr( $meta['btn_label']   ?? '📞 Call Us' ),
			(int) ( $meta['mobile_only']   ?? 0 ),
			esc_attr( $meta['hours']       ?? '' )
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
		if ( 'click_to_call' !== $np_bar_type ) { return $data; }

		$values = array(
			'phone'       => sanitize_text_field( wp_unslash( $_POST['call_phone']       ?? '' ) ),
			'btn_label'   => sanitize_text_field( wp_unslash( $_POST['call_btn_label']   ?? '📞 Call Us' ) ),
			'mobile_only' => isset( $_POST['call_mobile_only'] ) ? 1 : 0,
			'hours'       => sanitize_text_field( wp_unslash( $_POST['call_hours']       ?? '' ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'call', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'click_to_call' === $bar->bar_type ) {
				wp_enqueue_script(
					'np-click-to-call',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-click-to-call.js',
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
