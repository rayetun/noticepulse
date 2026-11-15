<?php
/**
 * NoticePulse — Marquee / News Ticker Bar.
 *
 * A continuously-scrolling bar. Distinct from the `ticker` type, which is the
 * fade/slide carousel. Marquee reuses the standard `.np-bar__message` node and
 * animates it via public/js/np-marquee.js.
 *
 * Additive: a bar with no `marquee` meta is untouched — this class returns
 * early for every non-marquee bar.
 *
 * @package NoticePulse
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Marquee {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_valid_bar_types',      array( $this, 'register_type' ) );
		add_filter( 'noticepulse_bar_data_attributes',  array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',        array( $this, 'save_fields' ) );
		add_action( 'wp_enqueue_scripts',               array( $this, 'maybe_enqueue' ), 20 );
	}

	/**
	 * Register the `marquee` bar type on the shared allow-list.
	 *
	 * @param array $types Valid bar-type slugs.
	 * @return array
	 */
	public function register_type( $types ) {
		if ( ! in_array( 'marquee', $types, true ) ) {
			$types[] = 'marquee';
		}
		return $types;
	}

	/**
	 * Emit the data-* attributes the front-end script reads.
	 *
	 * @param string $attrs Existing attribute string.
	 * @param object $bar   Bar row.
	 * @return string
	 */
	public function add_data_attributes( $attrs, $bar ) {
		if ( ! isset( $bar->bar_type ) || 'marquee' !== $bar->bar_type ) { return $attrs; }
		$meta = NoticePulse_DB::get_meta( $bar, 'marquee' );

		$dir   = ( isset( $meta['direction'] ) && 'right' === $meta['direction'] ) ? 'right' : 'left';
		$speed = isset( $meta['speed'] ) ? min( 300, max( 10, absint( $meta['speed'] ) ) ) : 60;
		$gap   = isset( $meta['gap'] ) ? min( 300, max( 0, absint( $meta['gap'] ) ) ) : 50;
		$pause = isset( $meta['pause_hover'] ) ? (int) (bool) $meta['pause_hover'] : 1;

		$attrs .= sprintf(
			' data-marquee="1" data-marquee-dir="%s" data-marquee-speed="%d" data-marquee-gap="%d" data-marquee-pause="%d"',
			esc_attr( $dir ),
			$speed,
			$gap,
			$pause
		);
		return $attrs;
	}

	/**
	 * Persist marquee settings into bar_meta on save.
	 *
	 * @param array $data Bar data being saved.
	 * @return array
	 */
	public function save_fields( $data ) {
		// Belt-and-braces nonce check (handle_save_bar() already verified it).
		if (
			! isset( $_POST['noticepulse_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['noticepulse_nonce'] ), 'noticepulse_save_bar' )
		) {
			return $data;
		}

		if ( 'marquee' !== sanitize_key( wp_unslash( $_POST['bar_type'] ?? '' ) ) ) {
			return $data;
		}

		$dir = sanitize_key( wp_unslash( $_POST['marquee_direction'] ?? 'left' ) );

		$values = array(
			'direction'   => in_array( $dir, array( 'left', 'right' ), true ) ? $dir : 'left',
			'speed'       => min( 300, max( 10, absint( $_POST['marquee_speed'] ?? 60 ) ) ),
			'gap'         => min( 300, max( 0, absint( $_POST['marquee_gap'] ?? 50 ) ) ),
			'pause_hover' => isset( $_POST['marquee_pause_hover'] ) ? 1 : 0,
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'marquee', $values );
		return $data;
	}

	/**
	 * Enqueue the marquee assets only when a marquee bar is active.
	 */
	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'marquee' === $bar->bar_type ) {
				wp_enqueue_style(
					'np-marquee',
					NOTICEPULSE_PLUGIN_URL . 'public/css/np-marquee.css',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION
				);
				wp_enqueue_script(
					'np-marquee',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-marquee.js',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION,
					true
				);
				return;
			}
		}
	}
}
