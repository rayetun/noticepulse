<?php
/**
 * NoticePulse — Countdown Timer Bar.
 *
 * Adds data-countdown-end and label attributes.
 * Frontend JS (np-countdown.js) renders the live timer.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Countdown {

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
		if ( ! isset( $bar->bar_type ) || 'countdown' !== $bar->bar_type ) { return $attrs; }
		$meta = NoticePulse_DB::get_meta( $bar, 'countdown' );
		if ( empty( $meta['end'] ) ) { return $attrs; }

		$end_ts = strtotime( $meta['end'] );
		if ( ! $end_ts || $end_ts < time() ) { return $attrs; }

		$attrs .= sprintf(
			' data-countdown="%d" data-countdown-days="%s" data-countdown-hours="%s" data-countdown-mins="%s" data-countdown-secs="%s" data-countdown-hide="%d" data-countdown-show-hours="%d" data-countdown-show-mins="%d" data-countdown-show-secs="%d"',
			$end_ts,
			esc_attr( $meta['label_days']   ?? 'Days' ),
			esc_attr( $meta['label_hours']  ?? 'Hours' ),
			esc_attr( $meta['label_mins']   ?? 'Mins' ),
			esc_attr( $meta['label_secs']   ?? 'Secs' ),
			(int) ( $meta['hide_on_expire'] ?? 1 ),
			(int) ( $meta['show_hours']     ?? 1 ),
			(int) ( $meta['show_mins']      ?? 1 ),
			(int) ( $meta['show_seconds']   ?? 1 )
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
		if ( 'countdown' !== $np_bar_type ) { return $data; }

		$raw_end = sanitize_text_field( wp_unslash( $_POST['countdown_end'] ?? '' ) );
		$values  = array(
			'end'            => ! empty( $raw_end ) ? gmdate( 'Y-m-d H:i:s', strtotime( $raw_end ) ) : '',
			'label_days'     => sanitize_text_field( wp_unslash( $_POST['countdown_label_days']  ?? 'Days' ) ),
			'label_hours'    => sanitize_text_field( wp_unslash( $_POST['countdown_label_hours'] ?? 'Hours' ) ),
			'label_mins'     => sanitize_text_field( wp_unslash( $_POST['countdown_label_mins']  ?? 'Mins' ) ),
			'label_secs'     => sanitize_text_field( wp_unslash( $_POST['countdown_label_secs']  ?? 'Secs' ) ),
			'show_hours'     => isset( $_POST['countdown_show_hours']   ) ? 1 : 0,
			'show_mins'      => isset( $_POST['countdown_show_mins']    ) ? 1 : 0,
			'show_seconds'   => isset( $_POST['countdown_show_seconds'] ) ? 1 : 0,
			'hide_on_expire' => isset( $_POST['countdown_hide_on_expire'] ) ? 1 : 0,
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'countdown', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'countdown' === $bar->bar_type ) {
				$meta = NoticePulse_DB::get_meta( $bar, 'countdown' );
				if ( ! empty( $meta['end'] ) && strtotime( $meta['end'] ) > time() ) {
					wp_enqueue_script(
						'np-countdown',
						NOTICEPULSE_PLUGIN_URL . 'public/js/np-countdown.js',
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
}
