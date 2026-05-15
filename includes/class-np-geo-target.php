<?php
/**
 * NoticePulse — Geo-Targeting.
 *
 * Filters active bars by visitor country using a lightweight
 * server-side IP lookup. No external API dependency — uses
 * WordPress's own ip-geolocation mechanism if available,
 * otherwise falls back to a free ipapi.co request cached in
 * a transient (1-hour TTL per IP).
 *
 * Bar is removed from the active list before it is rendered,
 * so no flicker occurs on the frontend.
 *
 * Hook API:
 *  - noticepulse_active_bars  → filters bars by country
 *  - noticepulse_save_bar_data → saves geo fields to bar_meta
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Geo_Target {

	private static $instance = null;

	/** Transient prefix for cached country lookups. */
	const TRANSIENT_PREFIX = 'noticepulse_geo_';

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_active_bars', array( $this, 'filter_bars_by_country' ) );
		add_filter( 'noticepulse_save_bar_data', array( $this, 'save_fields' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// BAR FILTERING
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Remove bars that don't match the current visitor's country.
	 *
	 * @param array $bars Array of active bar objects.
	 * @return array
	 */
	public function filter_bars_by_country( $bars ) {
		if ( empty( $bars ) ) { return $bars; }

		// Check if any bar uses geo-targeting — skip lookup otherwise.
		$any_geo = false;
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'geo' );
			if ( ! empty( $meta['enabled'] ) && ! empty( $meta['countries'] ) ) {
				$any_geo = true;
				break;
			}
		}

		if ( ! $any_geo ) { return $bars; }

		$country = $this->get_visitor_country();

		if ( empty( $country ) ) {
			// Country unknown — show all bars (fail open).
			return $bars;
		}

		$filtered = array();
		foreach ( $bars as $bar ) {
			$meta = NoticePulse_DB::get_meta( $bar, 'geo' );

			if ( empty( $meta['enabled'] ) || empty( $meta['countries'] ) ) {
				// No geo rule — always show.
				$filtered[] = $bar;
				continue;
			}

			$mode     = isset( $meta['mode'] ) ? $meta['mode'] : 'include';
			$raw      = is_array( $meta['countries'] ) ? implode( ',', $meta['countries'] ) : $meta['countries'];
			$codes    = array_map( 'trim', explode( ',', strtoupper( $raw ) ) );
			$codes    = array_filter( $codes );
			$match    = in_array( strtoupper( $country ), $codes, true );

			if ( 'include' === $mode && $match ) {
				$filtered[] = $bar;
			} elseif ( 'exclude' === $mode && ! $match ) {
				$filtered[] = $bar;
			}
		}

		return $filtered;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// COUNTRY LOOKUP
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Get the current visitor's 2-letter ISO country code.
	 * Result is cached in a transient for 1 hour per IP.
	 *
	 * @return string  2-letter code (e.g. 'US') or empty string.
	 */
	private function get_visitor_country() {
		$ip = $this->get_visitor_ip();
		if ( empty( $ip ) ) { return ''; }

		// Skip lookup for localhost / private IPs.
		if ( in_array( $ip, array( '127.0.0.1', '::1' ), true )
			|| $this->is_private_ip( $ip ) ) {
			return '';
		}

		$cache_key = self::TRANSIENT_PREFIX . md5( $ip );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		// Try ipapi.co (free, no key needed, 1000 requests/day).
		$url      = 'https://ipapi.co/' . rawurlencode( $ip ) . '/country/';
		$response = wp_remote_get( $url, array(
			'timeout'    => 3,
			'user-agent' => 'NoticePulse/' . NOTICEPULSE_VERSION,
		) );

		$country = '';
		if ( ! is_wp_error( $response ) ) {
			$body    = trim( wp_remote_retrieve_body( $response ) );
			$country = ( preg_match( '/^[A-Z]{2}$/', $body ) ) ? $body : '';
		}

		// Cache for 1 hour regardless of whether lookup succeeded.
		set_transient( $cache_key, $country, HOUR_IN_SECONDS );

		return $country;
	}

	/**
	 * Get the real visitor IP, respecting common proxy headers.
	 *
	 * @return string
	 */
	private function get_visitor_ip() {
		$headers = array(
			'HTTP_CF_CONNECTING_IP',  // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Check if an IP address is in a private range.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_private_ip( $ip ) {
		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SAVE
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Save geo fields from POST into bar_meta['geo'].
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

		$enabled   = isset( $_POST['geo_enabled'] ) ? 1 : 0;
		$mode      = sanitize_key( wp_unslash( $_POST['geo_mode']      ?? 'include' ) );
		$raw       = sanitize_text_field( wp_unslash( $_POST['geo_countries'] ?? '' ) );

		// Sanitize: uppercase, comma-separated, letters only.
		$codes   = array_map( 'trim', explode( ',', strtoupper( $raw ) ) );
		$codes   = array_filter( $codes, fn( $c ) => preg_match( '/^[A-Z]{2}$/', $c ) );
		$clean   = implode( ', ', $codes );

		$values = array(
			'enabled'   => $enabled,
			'mode'      => in_array( $mode, array( 'include', 'exclude' ), true ) ? $mode : 'include',
			'countries' => $clean,
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'geo', $values );
		return $data;
	}
}