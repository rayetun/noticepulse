<?php
/**
 * NoticePulse — Display Frequency Control.
 *
 * Controls how often a bar re-appears: once per session,
 * always, once ever, or every N page views.
 *
 * The cookie/session logic is handled by np-triggers.js.
 * This class adds data-frequency-* attributes.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Frequency {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_data_attributes', array( $this, 'add_data_attributes' ), 15, 2 );
		add_filter( 'noticepulse_save_bar_data',        array( $this, 'save_fields' ) );
	}

	public function add_data_attributes( $attrs, $bar ) {
		$meta  = NoticePulse_DB::get_meta( $bar, 'frequency' );
		$type  = $meta['type']  ?? 'session';
		$value = $meta['value'] ?? 5;

		$valid = array( 'session', 'always', 'once', 'pageviews' );
		if ( ! in_array( $type, $valid, true ) ) { $type = 'session'; }

		$attrs .= ' data-frequency="' . esc_attr( $type ) . '"';
		if ( 'pageviews' === $type ) {
			$attrs .= ' data-frequency-n="' . absint( $value ) . '"';
		}

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

		$valid = array( 'session', 'always', 'once', 'pageviews' );
		$type  = sanitize_key( wp_unslash( $_POST['frequency_type'] ?? 'session' ) );

		$values = array(
			'type'  => in_array( $type, $valid, true ) ? $type : 'session',
			'value' => min( 999, max( 1, absint( $_POST['frequency_value'] ?? 5 ) ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'frequency', $values );
		return $data;
	}
}
