<?php
/**
 * NoticePulse — Gradient Backgrounds.
 *
 * Plugs into noticepulse_bar_inline_styles and noticepulse_save_bar_data.
 * Reads gradient settings from bar_meta['gradient'] and overwrites
 * the solid background-color with a linear or radial CSS gradient.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Gradients {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_inline_styles', array( $this, 'apply_gradient' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',     array( $this, 'save_fields' ) );
	}

	/**
	 * Replace solid background with gradient if enabled.
	 */
	public function apply_gradient( $style, $bar ) {
		$meta = NoticePulse_DB::get_meta( $bar, 'gradient' );
		if ( empty( $meta['enabled'] ) ) {
			return $style;
		}

		$c1    = sanitize_hex_color( $meta['color1'] ?? '#7c3aed' ) ?: '#7c3aed';
		$c2    = sanitize_hex_color( $meta['color2'] ?? '#4f46e5' ) ?: '#4f46e5';
		$type  = isset( $meta['type'] ) && 'radial' === $meta['type'] ? 'radial' : 'linear';
		$angle = absint( $meta['angle'] ?? 135 );

		if ( 'radial' === $type ) {
			$bg = 'radial-gradient(circle,' . $c1 . ',' . $c2 . ')';
		} else {
			$bg = 'linear-gradient(' . $angle . 'deg,' . $c1 . ',' . $c2 . ')';
		}

		// Remove existing background-color and inject gradient.
		$style = preg_replace( '/background-color:[^;]+;/', '', $style );
		$style .= 'background:' . $bg . ';';

		return $style;
	}

	/**
	 * Save gradient fields from POST into bar_meta.
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

		$enabled = isset( $_POST['gradient_enabled'] ) ? 1 : 0;
		$type    = sanitize_key( wp_unslash( $_POST['gradient_type']  ?? 'linear' ) );
		$c1      = sanitize_hex_color( wp_unslash( $_POST['gradient_color1'] ?? '#7c3aed' ) );
		$c2      = sanitize_hex_color( wp_unslash( $_POST['gradient_color2'] ?? '#4f46e5' ) );
		$angle   = min( 360, max( 0, absint( $_POST['gradient_angle'] ?? 135 ) ) );

		$values = array(
			'enabled' => $enabled,
			'type'    => in_array( $type, array( 'linear', 'radial' ), true ) ? $type : 'linear',
			'color1'  => $c1 ?: '#7c3aed',
			'color2'  => $c2 ?: '#4f46e5',
			'angle'   => $angle,
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'gradient', $values );
		return $data;
	}
}
