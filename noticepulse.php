<?php
/**
 * Plugin Name:       NoticePulse — Notification Bar, Announcement Bar & Cookie Notice
 * Plugin URI:        https://wordpress.org/plugins/noticepulse/
 * Description:       The most powerful free notification bar plugin for WordPress. Countdown timers, exit-intent, A/B testing, geo-targeting, email capture, gradients, animations, Google Fonts, and more — all free.
 * Version:           2.1.4
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Md Rayhan Uddin
 * Author URI:        https://rayetun.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       noticepulse
 * Domain Path:       /languages
 *
 * @package NoticePulse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOTICEPULSE_VERSION',         '2.1.4' );
define( 'NOTICEPULSE_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'NOTICEPULSE_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'NOTICEPULSE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

final class NoticePulse {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		// Core — always required.
		require_once NOTICEPULSE_PLUGIN_DIR . 'includes/class-noticepulse-db.php';
		require_once NOTICEPULSE_PLUGIN_DIR . 'includes/class-noticepulse-analytics.php';
		require_once NOTICEPULSE_PLUGIN_DIR . 'includes/class-noticepulse-features.php';
		require_once NOTICEPULSE_PLUGIN_DIR . 'includes/class-noticepulse-public.php';

		if ( is_admin() ) {
			require_once NOTICEPULSE_PLUGIN_DIR . 'includes/class-noticepulse-admin.php';
		}

		// Feature files — safe load with file_exists().
		// Plugin works fine if a feature ZIP has not been installed yet.
		$feature_files = array(
			'includes/class-np-gdpr-bar.php',
			'includes/class-np-ticker.php',			
			'includes/class-np-click-to-call.php',
			'includes/class-np-countdown.php',
			'includes/class-np-email-capture.php',
			'includes/class-np-coupon-copy.php',
			'includes/class-np-animations.php',
			'includes/class-np-frequency.php',
			'includes/class-np-gradients.php',
			'includes/class-np-google-fonts.php',
			'includes/class-np-triggers.php',
			'includes/class-np-ab-testing.php',
			'includes/class-np-geo-target.php',
			'includes/class-np-ga4.php',
			'includes/class-np-analytics-pro.php',
			'includes/class-np-templates.php',
		);

		foreach ( $feature_files as $file ) {
			$path = NOTICEPULSE_PLUGIN_DIR . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( 'NoticePulse_DB', 'create_tables' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init',           array( $this, 'init' ) );
	}

	public function load_textdomain() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$locale = apply_filters( 'plugin_locale', determine_locale(), 'noticepulse' );
		$mofile = WP_LANG_DIR . '/plugins/noticepulse-' . $locale . '.mo';
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'noticepulse', $mofile );
		}
	}

	public function init() {
		NoticePulse_Public::get_instance();

		if ( is_admin() ) {
			NoticePulse_Admin::get_instance();
		}

		// Boot only classes that were successfully loaded.
		if ( class_exists( 'NoticePulse_GDPR_Bar' ) )       { NoticePulse_GDPR_Bar::get_instance(); }
		if ( class_exists( 'NoticePulse_Ticker' ) )          { NoticePulse_Ticker::get_instance(); }
		if ( class_exists( 'NoticePulse_Click_To_Call' ) )   { NoticePulse_Click_To_Call::get_instance(); }
		if ( class_exists( 'NoticePulse_Countdown' ) )       { NoticePulse_Countdown::get_instance(); }
		if ( class_exists( 'NoticePulse_Email_Capture' ) )   { NoticePulse_Email_Capture::get_instance(); }
		if ( class_exists( 'NoticePulse_Coupon_Copy' ) )     { NoticePulse_Coupon_Copy::get_instance(); }
		if ( class_exists( 'NoticePulse_Animations' ) )      { NoticePulse_Animations::get_instance(); }
		if ( class_exists( 'NoticePulse_Frequency' ) )       { NoticePulse_Frequency::get_instance(); }
		if ( class_exists( 'NoticePulse_Gradients' ) )       { NoticePulse_Gradients::get_instance(); }
		if ( class_exists( 'NoticePulse_Google_Fonts' ) )    { NoticePulse_Google_Fonts::get_instance(); }
		if ( class_exists( 'NoticePulse_Custom_CSS' ) )      { NoticePulse_Custom_CSS::get_instance(); }
		if ( class_exists( 'NoticePulse_Triggers' ) )        { NoticePulse_Triggers::get_instance(); }
		if ( class_exists( 'NoticePulse_AB_Testing' ) )      { NoticePulse_AB_Testing::get_instance(); }
		if ( class_exists( 'NoticePulse_Geo_Target' ) )      { NoticePulse_Geo_Target::get_instance(); }
		if ( class_exists( 'NoticePulse_GA4' ) )             { NoticePulse_GA4::get_instance(); }
		if ( class_exists( 'NoticePulse_Analytics_Pro' ) )   { NoticePulse_Analytics_Pro::get_instance(); }
		if ( class_exists( 'NoticePulse_Templates' ) )       { NoticePulse_Templates::get_instance(); }

		do_action( 'noticepulse_loaded' );
	}

	public function on_deactivation() {
		flush_rewrite_rules();
	}
}

NoticePulse::get_instance();
