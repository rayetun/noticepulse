<?php
/**
 * NoticePulse — Google Fonts.
 *
 * Loads a Google Font via wp_enqueue_style() when a bar uses one,
 * then injects font-family via noticepulse_bar_inline_styles.
 *
 * @package NoticePulse
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Google_Fonts {

	private static $instance = null;

	/** @var array Tracks which font families have already been enqueued. */
	private $loaded_fonts = array();

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// FIX: use wp_enqueue_scripts (frontend) instead of wp_head so
		// WordPress manages the <link> output through its enqueue system.
		add_action( 'wp_enqueue_scripts',            array( $this, 'enqueue_fonts' ), 5 );

		// FIX: register preconnect hints through WP's resource hint filter
		// instead of raw echo in wp_head.
		add_filter( 'wp_resource_hints',             array( $this, 'add_preconnect_hints' ), 10, 2 );

		add_filter( 'noticepulse_bar_inline_styles', array( $this, 'apply_font' ), 20, 2 );
		add_filter( 'noticepulse_save_bar_data',     array( $this, 'save_fields' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// ENQUEUE
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Enqueue Google Fonts for all active bars that use one.
	 */
	public function enqueue_fonts() {
		$bars = NoticePulse_DB::get_active_bars();
		if ( empty( $bars ) ) {
			return;
		}

		foreach ( $bars as $bar ) {
			$meta   = NoticePulse_DB::get_meta( $bar, 'font' );
			$family = sanitize_text_field( $meta['family'] ?? '' );
			$weight = sanitize_text_field( $meta['weight'] ?? '500' );

			if ( empty( $family ) || isset( $this->loaded_fonts[ $family ] ) ) {
				continue;
			}

			$this->loaded_fonts[ $family ] = true;

			// Build a safe handle: np-font-open-sans, np-font-roboto, etc.
			$handle = 'np-font-' . sanitize_title( $family );

			// Build the Google Fonts URL using add_query_arg() — no string concat.
			// Format: family=Open+Sans:wght@300;400;500;600;700
			$font_query = rawurlencode( $family ) . ':wght@300;400;500;' . $weight . ';600;700';

			$url = add_query_arg(
				array(
					'family'  => $font_query,
					'display' => 'swap',
				),
				'https://fonts.googleapis.com/css2'
			);

			// wp_enqueue_style() outputs the <link rel="stylesheet"> correctly.
			wp_enqueue_style(
				$handle,
				$url,
				array(),
				NOTICEPULSE_VERSION // Use your version constant instead of null
			);
		}
	}

	/**
	 * Add preconnect resource hints for Google Fonts domains.
	 *
	 * FIX: Replaces raw echo of <link rel="preconnect"> with the proper
	 * wp_resource_hints filter. WordPress outputs these in wp_head itself.
	 *
	 * @param array  $urls          Existing resource hint URLs.
	 * @param string $relation_type The type of resource hint (preconnect, dns-prefetch, etc.).
	 * @return array
	 */
	public function add_preconnect_hints( $urls, $relation_type ) {
		// Only add hints if at least one Google Font is being loaded.
		if ( 'preconnect' !== $relation_type || empty( $this->loaded_fonts ) ) {
			return $urls;
		}

		$urls[] = array(
			'href'        => 'https://fonts.googleapis.com',
			'crossorigin' => false,
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => true,
		);

		return $urls;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// INLINE STYLES
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Inject font-family and font-weight into bar inline style.
	 *
	 * @param string   $style Existing inline CSS string.
	 * @param stdClass $bar   Bar object.
	 * @return string
	 */
	public function apply_font( $style, $bar ) {
		$meta   = NoticePulse_DB::get_meta( $bar, 'font' );
		$family = sanitize_text_field( $meta['family'] ?? '' );
		$weight = sanitize_text_field( $meta['weight'] ?? '500' );

		if ( empty( $family ) ) {
			return $style;
		}

		$style .= 'font-family:\'' . esc_attr( $family ) . '\',sans-serif;';
		$style .= 'font-weight:' . esc_attr( $weight ) . ';';

		return $style;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SAVE
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Save font fields into bar_meta when the edit-bar form is submitted.
	 *
	 * @param array $data Bar data being saved.
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

		$family = sanitize_text_field( wp_unslash( $_POST['font_family'] ?? '' ) );
		$weight = sanitize_text_field( wp_unslash( $_POST['font_weight'] ?? '500' ) );

		$valid_weights = array( '300', '400', '500', '600', '700', '800' );

		$values = array(
			'family' => $family,
			'weight' => in_array( $weight, $valid_weights, true ) ? $weight : '500',
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'font', $values );
		return $data;
	}
}
