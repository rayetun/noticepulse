<?php
/**
 * NoticePulse — WooCommerce Free-Shipping Bar.
 *
 * A `woo_free_shipping` bar whose message uses merge tokens that resolve against
 * the live cart:
 *   {free_shipping_left}  — amount still needed to qualify (formatted price)
 *   {free_shipping_goal}  — the free-shipping threshold (formatted price)
 *   {cart_total}          — the current cart subtotal (formatted price)
 *
 * The threshold is read from WooCommerce's own free_shipping method on the
 * cart's matching shipping zone (or an optional manual override). Live updates
 * ride WooCommerce's cart fragments, so the bar refreshes on add-to-cart and
 * cart/checkout changes with no custom endpoint.
 *
 * Loaded only when WooCommerce is active (see noticepulse.php). Additive: a bar
 * with no woo tokens and a non-woo type is returned untouched.
 *
 * @package NoticePulse
 * @since   2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Woo_Shipping {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_valid_bar_types',        array( $this, 'register_type' ) );
		add_filter( 'noticepulse_bar_message',            array( $this, 'resolve_tokens' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',          array( $this, 'save_fields' ) );
		add_filter( 'woocommerce_add_to_cart_fragments',  array( $this, 'cart_fragments' ) );
		add_action( 'wp_enqueue_scripts',                 array( $this, 'maybe_enqueue' ), 20 );
	}

	/**
	 * Register the `woo_free_shipping` bar type on the shared allow-list.
	 *
	 * @param array $types Valid bar-type slugs.
	 * @return array
	 */
	public function register_type( $types ) {
		if ( ! in_array( 'woo_free_shipping', $types, true ) ) {
			$types[] = 'woo_free_shipping';
		}
		return $types;
	}

	/**
	 * Resolve the free-shipping merge tokens in a bar message.
	 *
	 * @param string $message Raw bar message.
	 * @param object $bar     Bar row.
	 * @return string
	 */
	public function resolve_tokens( $message, $bar ) {
		$is_woo_bar = isset( $bar->bar_type ) && 'woo_free_shipping' === $bar->bar_type;
		if ( ! $is_woo_bar && false === strpos( (string) $message, '{' ) ) {
			return $message;
		}

		$state = $this->cart_state( $bar );
		if ( null === $state ) {
			// No cart / no threshold → drop the tokens rather than show them raw.
			return str_replace(
				array( '{free_shipping_left}', '{free_shipping_goal}', '{cart_total}' ),
				array( '', '', '' ),
				$message
			);
		}

		// Goal reached: optionally swap in the "reached" message.
		if ( $is_woo_bar && $state['remaining'] <= 0 ) {
			$reached = (string) ( NoticePulse_DB::get_meta( $bar, 'woo' )['reached_message'] ?? '' );
			if ( '' !== trim( $reached ) ) {
				$message = $reached;
			}
		}

		return str_replace(
			array( '{free_shipping_left}', '{free_shipping_goal}', '{cart_total}' ),
			array(
				$this->fmt( max( 0, $state['remaining'] ) ),
				$this->fmt( $state['threshold'] ),
				$this->fmt( $state['total'] ),
			),
			$message
		);
	}

	/**
	 * Compute threshold / cart total / remaining, or null if unavailable.
	 *
	 * @param object $bar Bar row (for a manual goal override).
	 * @return array{threshold:float,total:float,remaining:float}|null
	 */
	private function cart_state( $bar ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return null; }

		$override  = (float) ( NoticePulse_DB::get_meta( $bar, 'woo' )['goal_override'] ?? 0 );
		$threshold = $override > 0 ? $override : $this->zone_threshold();
		if ( null === $threshold || $threshold <= 0 ) { return null; }

		$total = (float) WC()->cart->get_subtotal();
		return array(
			'threshold' => (float) $threshold,
			'total'     => $total,
			'remaining' => (float) $threshold - $total,
		);
	}

	/**
	 * Read the free-shipping min_amount from the cart's matching shipping zone.
	 *
	 * @return float|null
	 */
	private function zone_threshold() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) || ! WC()->cart ) { return null; }

		$packages = WC()->cart->get_shipping_packages();
		if ( empty( $packages ) ) { return null; }

		$zone = WC_Shipping_Zones::get_zone_matching_package( reset( $packages ) );
		if ( ! $zone ) { return null; }

		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( ! isset( $method->id ) || 'free_shipping' !== $method->id ) { continue; }
			$requires = $method->get_option( 'requires' );
			$min      = $method->get_option( 'min_amount' );
			if ( in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) && '' !== $min ) {
				return (float) $min;
			}
		}
		return null;
	}

	/**
	 * Format an amount as a plain-text price string (no markup).
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	private function fmt( $amount ) {
		if ( ! function_exists( 'wc_price' ) ) { return (string) $amount; }
		return html_entity_decode(
			wp_strip_all_tags( wc_price( max( 0, (float) $amount ) ) ),
			ENT_QUOTES,
			'UTF-8'
		);
	}

	/**
	 * Refresh woo bars via WooCommerce cart fragments on cart changes.
	 *
	 * @param array $fragments Keyed by CSS selector → replacement HTML.
	 * @return array
	 */
	public function cart_fragments( $fragments ) {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( ! isset( $bar->bar_type ) || 'woo_free_shipping' !== $bar->bar_type ) { continue; }
			$msg = apply_filters( 'noticepulse_bar_message', $bar->message, $bar );
			$sel = '.np-bar[data-bar-id="' . absint( $bar->id ) . '"] .np-bar__message';
			$fragments[ $sel ] = '<span class="np-bar__message">' . wp_kses_post( $msg ) . '</span>';
		}
		return $fragments;
	}

	/**
	 * Persist woo settings into bar_meta on save.
	 *
	 * @param array $data Bar data being saved.
	 * @return array
	 */
	public function save_fields( $data ) {
		if (
			! isset( $_POST['noticepulse_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['noticepulse_nonce'] ), 'noticepulse_save_bar' )
		) {
			return $data;
		}
		if ( 'woo_free_shipping' !== sanitize_key( wp_unslash( $_POST['bar_type'] ?? '' ) ) ) {
			return $data;
		}

		$goal = isset( $_POST['woo_goal_override'] ) ? (float) wp_unslash( $_POST['woo_goal_override'] ) : 0;

		$values = array(
			'goal_override'   => max( 0, $goal ),
			'reached_message' => sanitize_text_field( wp_unslash( $_POST['woo_reached_message'] ?? '' ) ),
		);
		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'woo', $values );
		return $data;
	}

	/**
	 * Bridge WooCommerce cart/checkout events to a fragment refresh so the bar
	 * updates live. Enqueued only when a woo bar is active.
	 */
	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'woo_free_shipping' === $bar->bar_type ) {
				wp_enqueue_script(
					'np-woo-shipping',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-woo-shipping.js',
					array( 'jquery', 'noticepulse-public' ),
					NOTICEPULSE_VERSION,
					true
				);
				return;
			}
		}
	}
}
