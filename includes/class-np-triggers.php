<?php
/**
 * NoticePulse — Display Triggers.
 *
 * Supports: immediate, time-delay, scroll-depth, exit-intent.
 * Adds data-trigger-* attributes. Frontend JS reads them.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Triggers {

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
		$meta = NoticePulse_DB::get_meta( $bar, 'triggers' );
		$type = $meta['type'] ?? 'immediate';

		if ( 'immediate' === $type ) { return $attrs; }

		$attrs .= ' data-trigger="' . esc_attr( $type ) . '"';

		if ( 'delay' === $type ) {
			$attrs .= ' data-trigger-delay="' . absint( $meta['delay_seconds'] ?? 3 ) . '"';
		}
		if ( 'scroll' === $type ) {
			$attrs .= ' data-trigger-scroll="' . absint( $meta['scroll_pct'] ?? 30 ) . '"';
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

		$valid_types = array( 'immediate', 'delay', 'scroll', 'exit_intent' );
		$type        = sanitize_key( wp_unslash( $_POST['trigger_type'] ?? 'immediate' ) );

		$values = array(
			'type'          => in_array( $type, $valid_types, true ) ? $type : 'immediate',
			'delay_seconds' => min( 120, max( 1, absint( $_POST['trigger_delay_seconds'] ?? 3 ) ) ),
			'scroll_pct'    => min( 95,  max( 5, absint( $_POST['trigger_scroll_pct']    ?? 30 ) ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'triggers', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'triggers' );
			$type = $meta['type'] ?? 'immediate';
			if ( 'immediate' !== $type ) {
				wp_enqueue_script( 'np-triggers', NOTICEPULSE_PLUGIN_URL . 'public/js/np-triggers.js', array( 'noticepulse-public' ), NOTICEPULSE_VERSION, true );
				return;
			}
		}
	}
}
