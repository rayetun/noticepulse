<?php
/**
 * NoticePulse — Google Analytics 4 Integration.
 *
 * Pushes bar impression and CTA click events to GA4 using
 * gtag() if it is already loaded on the page, or via the
 * Measurement Protocol as a fallback.
 *
 * What it does:
 *  - Adds data-ga4-id and data-ga4-event attributes to bars.
 *  - np-ga4.js reads these and fires gtag() events on:
 *      · bar impression (when bar appears)
 *      · CTA click (when visitor clicks the button)
 *
 * Hook API:
 *  - noticepulse_bar_data_attributes → adds GA4 data attrs
 *  - noticepulse_save_bar_data       → saves ga4 fields
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_GA4 {

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

	// ─────────────────────────────────────────────────────────────────────────
	// PUBLIC OUTPUT
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Add GA4 data attributes to the bar element.
	 *
	 * @param string $attrs Existing data attributes string.
	 * @param object $bar   Bar object.
	 * @return string
	 */
	public function add_data_attributes( $attrs, $bar ) {
		$meta = NoticePulse_DB::get_meta( $bar, 'ga4' );

		$measurement_id = isset( $meta['measurement_id'] ) ? sanitize_text_field( $meta['measurement_id'] ) : '';
		$event_name     = isset( $meta['event_name'] )     ? sanitize_key( $meta['event_name'] )            : '';

		if ( empty( $measurement_id ) || empty( $event_name ) ) {
			return $attrs;
		}

		$attrs .= sprintf(
			' data-ga4-id="%s" data-ga4-event="%s" data-ga4-bar-name="%s"',
			esc_attr( $measurement_id ),
			esc_attr( $event_name ),
			esc_attr( $bar->name ?? '' )
		);

		return $attrs;
	}

	/**
	 * Enqueue GA4 JS when any bar has a GA4 measurement ID configured.
	 */
	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'ga4' );
			if ( ! empty( $meta['measurement_id'] ) ) {
				wp_enqueue_script(
					'np-ga4',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-ga4.js',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION,
					true
				);
				return;
			}
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SAVE
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Save GA4 fields from POST into bar_meta['ga4'].
	 *
	 * @param array $data Bar data array being saved.
	 * @return array
	 */
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

		$measurement_id = sanitize_text_field( wp_unslash( $_POST['ga4_measurement_id'] ?? '' ) );
		$event_name     = sanitize_key( wp_unslash( $_POST['ga4_event_name']             ?? 'noticepulse_cta_click' ) );

		// Validate GA4 measurement ID format: G-XXXXXXXXXX
		if ( ! empty( $measurement_id ) && ! preg_match( '/^G-[A-Z0-9]+$/i', $measurement_id ) ) {
			$measurement_id = '';
		}

		$values = array(
			'measurement_id' => $measurement_id,
			'event_name'     => $event_name ?: 'noticepulse_cta_click',
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'ga4', $values );
		return $data;
	}
}