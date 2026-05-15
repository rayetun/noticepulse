<?php
/**
 * NoticePulse — Rotating Ticker Bar.
 *
 * @package NoticePulse
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Ticker {

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
		if ( ! isset( $bar->bar_type ) || 'ticker' !== $bar->bar_type ) { return $attrs; }
		$meta     = NoticePulse_DB::get_meta( $bar, 'ticker' );
		$messages = $meta['messages'] ?? array();
		if ( empty( $messages ) || ! is_array( $messages ) ) { return $attrs; }

		$clean = array();
		foreach ( $messages as $msg ) {
			if ( ! empty( $msg['text'] ) ) {
				$clean[] = array(
					'text'      => wp_strip_all_tags( $msg['text'] ),
					'cta_label' => sanitize_text_field( $msg['cta_label'] ?? '' ),
					'cta_url'   => esc_url( $msg['cta_url'] ?? '' ),
				);
			}
		}
		if ( empty( $clean ) ) { return $attrs; }

		$attrs .= sprintf(
			' data-ticker="1" data-ticker-messages="%s" data-ticker-speed="%d" data-ticker-transition="%s" data-ticker-pause="%d" data-ticker-arrows="%d" data-ticker-dots="%d"',
			esc_attr( wp_json_encode( $clean ) ),
			absint( $meta['speed'] ?? 4 ),
			esc_attr( in_array( $meta['transition'] ?? 'fade', array( 'fade', 'slide' ), true ) ? $meta['transition'] : 'fade' ),
			(int) ( $meta['pause_hover'] ?? 1 ),
			(int) ( $meta['show_arrows'] ?? 0 ),
			(int) ( $meta['show_dots']   ?? 1 )
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

		$np_bar_type = sanitize_key( wp_unslash( $_POST['bar_type'] ?? '' ) );
		if ( 'ticker' !== $np_bar_type ) { return $data; }
		$raw      = isset( $_POST['ticker_messages'] ) ? sanitize_text_field( wp_unslash( $_POST['ticker_messages'] ) ) : '[]';
		$decoded  = json_decode( $raw, true );
		$messages = array();
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $msg ) {
				if ( ! empty( $msg['text'] ) ) {
					$messages[] = array(
						'text'      => sanitize_text_field( $msg['text'] ),
						'cta_label' => sanitize_text_field( $msg['cta_label'] ?? '' ),
						'cta_url'   => esc_url_raw( $msg['cta_url'] ?? '' ),
					);
				}
			}
		}
		$trans = sanitize_key( wp_unslash( $_POST['ticker_transition'] ?? 'fade' ) );
		$values = array(
			'messages'    => $messages,
			'speed'       => min( 30, max( 1, absint( $_POST['ticker_speed'] ?? 4 ) ) ),
			'transition'  => in_array( $trans, array( 'fade', 'slide' ), true ) ? $trans : 'fade',
			'pause_hover' => isset( $_POST['ticker_pause_hover'] ) ? 1 : 0,
			'show_arrows' => isset( $_POST['ticker_show_arrows'] ) ? 1 : 0,
			'show_dots'   => isset( $_POST['ticker_show_dots'] )   ? 1 : 0,
		);
		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'ticker', $values );
		return $data;
	}

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'ticker' === $bar->bar_type ) {
				wp_enqueue_script( 'np-carousel', NOTICEPULSE_PLUGIN_URL . 'public/js/np-carousel.js', array( 'noticepulse-public' ), NOTICEPULSE_VERSION, true );
				wp_enqueue_style( 'noticepulse-pro-public', NOTICEPULSE_PLUGIN_URL . 'public/css/noticepulse-public.css', array( 'noticepulse-public' ), NOTICEPULSE_VERSION );
				return;
			}
		}
	}
}
