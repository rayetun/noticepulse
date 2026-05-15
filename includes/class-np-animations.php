<?php
/**
 * NoticePulse — Entrance Animations.
 *
 * Adds data-animation and data-animation-speed attributes to bars.
 * The public JS (np-animations.js, loaded when needed) reads these
 * and applies CSS animation classes when the bar first appears.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Animations {

	private static $instance = null;

	/** Valid animation types. */
	const VALID_TYPES  = array( 'none', 'slide', 'fade', 'bounce', 'pulse' );
	const VALID_SPEEDS = array( 'slow', 'normal', 'fast' );

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_data_attributes', array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',        array( $this, 'save_fields' ) );
		add_action( 'wp_enqueue_scripts',               array( $this, 'maybe_enqueue' ), 20 );
	}

	/**
	 * Add animation data attributes.
	 */
	public function add_data_attributes( $attrs, $bar ) {
		$meta  = NoticePulse_DB::get_meta( $bar, 'animation' );
		$type  = isset( $meta['type'] ) && in_array( $meta['type'], self::VALID_TYPES, true )
			? $meta['type'] : 'none';
		$speed = isset( $meta['speed'] ) && in_array( $meta['speed'], self::VALID_SPEEDS, true )
			? $meta['speed'] : 'normal';

		if ( 'none' !== $type ) {
			$attrs .= ' data-animation="' . esc_attr( $type ) . '" data-animation-speed="' . esc_attr( $speed ) . '"';
		}

		return $attrs;
	}

	/**
	 * Enqueue animation CSS/JS if any active bar uses an animation.
	 */
	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'animation' );
			$type = $meta['type'] ?? 'none';
			if ( 'none' !== $type && ! empty( $type ) ) {
				wp_enqueue_style(
					'np-animations',
					NOTICEPULSE_PLUGIN_URL . 'public/css/np-animations.css',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION
				);
				break;
			}
		}
	}

	/**
	 * Save animation fields.
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

		$type  = sanitize_key( wp_unslash( $_POST['animation_type']  ?? 'none' ) );
		$speed = sanitize_key( wp_unslash( $_POST['animation_speed'] ?? 'normal' ) );

		$values = array(
			'type'  => in_array( $type,  self::VALID_TYPES,  true ) ? $type  : 'none',
			'speed' => in_array( $speed, self::VALID_SPEEDS, true ) ? $speed : 'normal',
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'animation', $values );
		return $data;
	}
}
