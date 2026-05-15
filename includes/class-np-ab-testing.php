<?php
/**
 * NoticePulse — A/B Testing.
 *
 * 50/50 split test between Variant A (original bar message) and
 * Variant B (alternative message + optional CTA).
 *
 * How it works:
 *  - On first page load, np-ab-testing.js assigns the visitor to
 *    variant A or B using a session cookie (np_ab_{bar_id}).
 *  - The variant assignment is stored client-side; PHP outputs
 *    both variants as data attributes.
 *  - Analytics are tracked per variant using the existing
 *    noticepulse_track AJAX action with a variant parameter.
 *
 * Hook API:
 *  - noticepulse_bar_data_attributes → adds data-ab-* attrs
 *  - noticepulse_save_bar_data       → saves ab fields to bar_meta
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_AB_Testing {

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
	 * Add A/B test data attributes to the bar element.
	 * JS reads these and swaps message/CTA for variant B visitors.
	 *
	 * @param string $attrs Existing data attributes string.
	 * @param object $bar   Bar object.
	 * @return string
	 */
	public function add_data_attributes( $attrs, $bar ) {
		$meta = NoticePulse_DB::get_meta( $bar, 'ab' );

		if ( empty( $meta['enabled'] ) ) {
			return $attrs;
		}

		$message_b   = isset( $meta['message_b'] )   ? sanitize_text_field( $meta['message_b'] )   : '';
		$cta_label_b = isset( $meta['cta_label_b'] ) ? sanitize_text_field( $meta['cta_label_b'] ) : '';
		$cta_url_b   = isset( $meta['cta_url_b'] )   ? esc_url( $meta['cta_url_b'] )               : '';

		if ( empty( $message_b ) ) {
			return $attrs;
		}

		$attrs .= sprintf(
			' data-ab="1" data-ab-message="%s" data-ab-cta-label="%s" data-ab-cta-url="%s"',
			esc_attr( $message_b ),
			esc_attr( $cta_label_b ),
			esc_attr( $cta_url_b )
		);

		return $attrs;
	}

	/**
	 * Enqueue A/B testing JS when a bar with A/B enabled is active.
	 */
	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'ab' );
			if ( ! empty( $meta['enabled'] ) && ! empty( $meta['message_b'] ) ) {
				wp_enqueue_script(
					'np-ab-testing',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-ab-testing.js',
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
	 * Save A/B fields from POST into bar_meta['ab'].
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

		$enabled     = isset( $_POST['ab_enabled'] )     ? 1 : 0;
		$message_b   = sanitize_textarea_field( wp_unslash( $_POST['ab_message_b']   ?? '' ) );
		$cta_label_b = sanitize_text_field( wp_unslash( $_POST['ab_cta_label_b']     ?? '' ) );
		$cta_url_b   = esc_url_raw( wp_unslash( $_POST['ab_cta_url_b']               ?? '' ) );

		$values = array(
			'enabled'     => $enabled,
			'message_b'   => $message_b,
			'cta_label_b' => $cta_label_b,
			'cta_url_b'   => $cta_url_b,
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'ab', $values );
		return $data;
	}
}