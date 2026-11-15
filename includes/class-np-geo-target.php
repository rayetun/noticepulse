<?php
/**
 * NoticePulse — Geo-Targeting.
 *
 * Filters active bars by visitor country. Country is read from
 * request headers set by Cloudflare / CDNs / the host first (no
 * external call); an optional ipapi.co fallback (off by default,
 * cached in a transient) can be enabled for hosts that don't set
 * a country header.
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
	 *
	 * Resolution order:
	 *   1. Request headers set by Cloudflare / CDNs / the host (no external call).
	 *   2. ipapi.co lookup — only if the admin opted in (off by default). This
	 *      preserves pre-2.3.0 behavior for installs that were already using geo.
	 *
	 * @return string  2-letter code (e.g. 'US') or empty string.
	 */
	private function get_visitor_country() {
		// 1. Header-based country (privacy-friendly, no third-party request).
		$header_country = $this->get_country_from_headers();
		if ( '' !== $header_country ) {
			return $header_country;
		}

		// 2. Optional ipapi.co fallback (disabled by default).
		if ( ! get_option( 'noticepulse_geo_ipapi_fallback' ) ) {
			return '';
		}

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
	 * Read a 2-letter country code from common CDN / host request headers.
	 * No external request. Returns '' when no usable code is present.
	 *
	 * @return string
	 */
	private function get_country_from_headers() {
		$headers = array(
			'HTTP_CF_IPCOUNTRY',             // Cloudflare
			'HTTP_CLOUDFRONT_VIEWER_COUNTRY', // AWS CloudFront
			'HTTP_X_COUNTRY_CODE',           // some hosts / load balancers
			'HTTP_GEOIP_COUNTRY_CODE',       // nginx/Apache GeoIP module
			'HTTP_X_GEO_COUNTRY',
		);

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}
			$code = strtoupper(
				substr( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ), 0, 2 )
			);
			// Cloudflare uses XX (unknown) and T1 (Tor) — treat as no match.
			if ( preg_match( '/^[A-Z]{2}$/', $code ) && ! in_array( $code, array( 'XX', 'T1' ), true ) ) {
				return $code;
			}
		}

		return '';
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