<?php
/**
 * NoticePulse Feature Registry.
 *
 * Single source of truth for what features are available.
 * Every method returns true — all features are free.
 *
 * This class intentionally has no Pro gates, no upgrade URLs
 * registry that other classes can query.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NoticePulse_Features
 */
class NoticePulse_Features {

	/**
	 * All features are available — no bar limit.
	 *
	 * @return int PHP_INT_MAX (unlimited).
	 */
	public static function max_bars() {
		return PHP_INT_MAX;
	}

	// ── Bar Types ──────────────────────────────────────────────────────────────

	/** @return bool */
	public static function has_gdpr_bar() { return true; }

	/** @return bool */
	public static function has_ticker_bar() { return true; }

	/** @return bool */
	public static function has_click_to_call() { return true; }

	/** @return bool */
	public static function has_countdown() { return true; }

	/** @return bool */
	public static function has_email_capture() { return true; }

	/** @return bool */
	public static function has_coupon_copy() { return true; }

	// ── Display & Design ──────────────────────────────────────────────────────

	/** @return bool */
	public static function has_animations() { return true; }

	/** @return bool */
	public static function has_frequency_control() { return true; }

	/** @return bool */
	public static function has_gradients() { return true; }

	/** @return bool */
	public static function has_google_fonts() { return true; }

	// ── Triggers ──────────────────────────────────────────────────────────────

	/** @return bool */
	public static function has_exit_intent() { return true; }

	/** @return bool */
	public static function has_scroll_trigger() { return true; }

	/** @return bool */
	public static function has_time_delay() { return true; }

	// ── Targeting & Testing ───────────────────────────────────────────────────

	/** @return bool */
	public static function has_ab_testing() { return true; }

	/** @return bool */
	public static function has_geo_targeting() { return true; }

	// ── Analytics & Integrations ──────────────────────────────────────────────

	/** @return bool */
	public static function has_ga4() { return true; }

	/** @return bool */
	public static function has_pro_analytics() { return true; }

	/** @return bool */
	public static function has_csv_export() { return true; }

	/** @return bool */
	public static function has_email_integrations() { return true; }

	// ── Templates ─────────────────────────────────────────────────────────────

	/** @return bool */
	public static function has_templates() { return true; }
}
