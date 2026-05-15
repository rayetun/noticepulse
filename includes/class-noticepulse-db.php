<?php
/**
 * Database class for NoticePulse.
 *
 * Handles table creation, upgrades, and CRUD operations.
 * v2.1.0: bar_meta JSON column stores all feature-specific data.
 *
 * @package NoticePulse
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NoticePulse_DB
 */
class NoticePulse_DB {

	const BARS_TABLE      = 'noticepulse_bars';
	const ANALYTICS_TABLE = 'noticepulse_analytics';
	const LEADS_TABLE     = 'noticepulse_leads';

	/**
	 * Create all plugin tables on activation.
	 * Uses dbDelta() — safe for both fresh installs and upgrades.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$cc   = $wpdb->get_charset_collate();
		$bars = $wpdb->prefix . self::BARS_TABLE;
		$anl  = $wpdb->prefix . self::ANALYTICS_TABLE;
		$leads= $wpdb->prefix . self::LEADS_TABLE;

		$sql_bars = "CREATE TABLE {$bars} (
			id            BIGINT(20) UNSIGNED  NOT NULL AUTO_INCREMENT,
			name          VARCHAR(200)         NOT NULL DEFAULT '',
			bar_type      VARCHAR(30)          NOT NULL DEFAULT 'standard',
			message       TEXT                 NOT NULL,
			cta_label     VARCHAR(200)         NOT NULL DEFAULT '',
			cta_url       TEXT                 NOT NULL DEFAULT '',
			cta_target    VARCHAR(10)          NOT NULL DEFAULT '_self',
			position      VARCHAR(10)          NOT NULL DEFAULT 'top',
			is_sticky     TINYINT(1)           NOT NULL DEFAULT 1,
			show_desktop  TINYINT(1)           NOT NULL DEFAULT 1,
			show_tablet   TINYINT(1)           NOT NULL DEFAULT 1,
			show_mobile   TINYINT(1)           NOT NULL DEFAULT 1,
			show_close    TINYINT(1)           NOT NULL DEFAULT 1,
			cookie_days   SMALLINT(5)          NOT NULL DEFAULT 7,
			visibility    VARCHAR(20)          NOT NULL DEFAULT 'all',
			page_ids      TEXT                 NOT NULL DEFAULT '',
			user_status   VARCHAR(20)          NOT NULL DEFAULT 'all',
			font_size     VARCHAR(10)          NOT NULL DEFAULT 'medium',
			bar_padding   VARCHAR(10)          NOT NULL DEFAULT 'normal',
			btn_radius    VARCHAR(10)          NOT NULL DEFAULT 'rounded',
			text_align    VARCHAR(10)          NOT NULL DEFAULT 'center',
			bg_color      VARCHAR(20)          NOT NULL DEFAULT '#1a73e8',
			text_color    VARCHAR(20)          NOT NULL DEFAULT '#ffffff',
			btn_bg_color  VARCHAR(20)          NOT NULL DEFAULT '#ffffff',
			btn_txt_color VARCHAR(20)          NOT NULL DEFAULT '#1a73e8',
			close_color   VARCHAR(20)          NOT NULL DEFAULT '#ffffff',
			bar_meta      LONGTEXT             NOT NULL DEFAULT '',
			date_start    DATETIME                      DEFAULT NULL,
			date_end      DATETIME                      DEFAULT NULL,
			is_active     TINYINT(1)           NOT NULL DEFAULT 1,
			sort_order    SMALLINT(5)          NOT NULL DEFAULT 0,
			created_at    DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at    DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY is_active   (is_active),
			KEY sort_order  (sort_order),
			KEY bar_type    (bar_type)
		) {$cc};";

		$sql_analytics = "CREATE TABLE {$anl} (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			bar_id     BIGINT(20) UNSIGNED NOT NULL,
			event_type VARCHAR(30)         NOT NULL DEFAULT 'impression',
			event_date DATE                NOT NULL,
			count      BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			variant    VARCHAR(10)         NOT NULL DEFAULT 'a',
			PRIMARY KEY  (id),
			UNIQUE KEY bar_event_date_variant (bar_id, event_type, event_date, variant),
			KEY bar_id     (bar_id),
			KEY event_date (event_date)
		) {$cc};";

		$sql_leads = "CREATE TABLE {$leads} (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			bar_id     BIGINT(20) UNSIGNED NOT NULL,
			email      VARCHAR(200)        NOT NULL DEFAULT '',
			name       VARCHAR(200)        NOT NULL DEFAULT '',
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY bar_id (bar_id),
			KEY email  (email(10))
		) {$cc};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_bars );
		dbDelta( $sql_analytics );
		dbDelta( $sql_leads );

		update_option( 'noticepulse_db_version', NOTICEPULSE_VERSION );
	}

	/**
	 * Upgrade DB schema for users coming from earlier versions.
	 * Safe to run on every admin page load — checks stored version first.
	 *
	 * @return void
	 */
	public static function maybe_upgrade_db() {
		$installed = get_option( 'noticepulse_db_version', '1.0.0' );
		if ( version_compare( $installed, '2.1.0', '>=' ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::BARS_TABLE;

		// One-time schema check — SHOW COLUMNS is unavoidable for migration compatibility.
		// Table name is our own constant prefixed by $wpdb->prefix — never user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cols = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SHOW COLUMNS FROM %i',
				$table
			),
			0
		);

		// Columns to add — both keys and values are hardcoded here, never user input.
		$add = array(
			'bar_type' => "VARCHAR(30) NOT NULL DEFAULT 'standard' AFTER name",
			'bar_meta' => "LONGTEXT NOT NULL DEFAULT '' AFTER close_color",
		);

		foreach ( $add as $col => $def ) {
		    // @codingStandardsIgnoreStart
			if ( ! in_array( $col, (array) $cols, true ) ) {
				// One-time schema migration — ALTER TABLE is intentional.
				// $col is an allowlisted key from our own $add array (never user input).
				// $def is a hardcoded string literal (never user input).
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
				    // @codingStandardsIgnoreEnd
					$wpdb->prepare(
					    // @codingStandardsIgnoreStart
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						'ALTER TABLE %i ADD COLUMN ' . $col . ' ' . $def,
						$table
						// @codingStandardsIgnoreEnd
					)
				);
			}
		}

		// Create leads table if missing (fresh 2.1 install over old DB).
		self::create_tables();
	}

	/**
	 * Drop all tables on uninstall.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . self::LEADS_TABLE ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . self::ANALYTICS_TABLE ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . self::BARS_TABLE ) );
		// phpcs:enable
	}

	// ── READ ──────────────────────────────────────────────────────────────────

	/**
	 * Get all active, date-eligible bars.
	 *
	 * @return array
	 */
	public static function get_active_bars() {
		global $wpdb;
		$table = $wpdb->prefix . self::BARS_TABLE;
		$now   = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table}
			 WHERE is_active = 1
			   AND (date_start IS NULL OR date_start <= %s)
			   AND (date_end   IS NULL OR date_end   >= %s)
			 ORDER BY sort_order ASC, id ASC",
			$now, $now
		) );
		return $rows ?: array();
	}

	/**
	 * Get a single bar by ID.
	 *
	 * @param int $bar_id Bar ID.
	 * @return object|null
	 */
	public static function get_bar( $bar_id ) {
		global $wpdb;
		$bar_id = absint( $bar_id );
		if ( ! $bar_id ) { return null; }
		$table = $wpdb->prefix . self::BARS_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} WHERE id = %d",
			$bar_id
		) );
	}

	/**
	 * Get all bars.
	 *
	 * @return array
	 */
	public static function get_all_bars() {
		global $wpdb;
		$table = $wpdb->prefix . self::BARS_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC"
		) ?: array();
	}

	/**
	 * Count all bars.
	 *
	 * @return int
	 */
	public static function count_bars() {
		global $wpdb;
		$table = $wpdb->prefix . self::BARS_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	// ── WRITE ─────────────────────────────────────────────────────────────────

	/**
	 * Insert a new bar.
	 *
	 * @param array $data Bar data.
	 * @return int|false Inserted ID or false.
	 */
	public static function insert_bar( $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( $wpdb->prefix . self::BARS_TABLE, $data );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a bar.
	 *
	 * @param int   $bar_id Bar ID.
	 * @param array $data   Data to update.
	 * @return bool
	 */
	public static function update_bar( $bar_id, $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->update(
			$wpdb->prefix . self::BARS_TABLE,
			$data,
			array( 'id' => absint( $bar_id ) )
		);
	}

	/**
	 * Delete a bar and its analytics/leads.
	 *
	 * @param int $bar_id Bar ID.
	 * @return bool
	 */
	public static function delete_bar( $bar_id ) {
		global $wpdb;
		$bar_id = absint( $bar_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . self::ANALYTICS_TABLE, array( 'bar_id' => $bar_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . self::LEADS_TABLE, array( 'bar_id' => $bar_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->delete( $wpdb->prefix . self::BARS_TABLE, array( 'id' => $bar_id ) );
	}

	// ── bar_meta helpers ──────────────────────────────────────────────────────

	/**
	 * Read a typed key from a bar's bar_meta JSON.
	 *
	 * @param object $bar  Bar object.
	 * @param string $key  Meta key (e.g. 'gdpr', 'triggers').
	 * @return array
	 */
	public static function get_meta( $bar, $key ) {
		if ( empty( $bar->bar_meta ) ) {
			return array();
		}
		$all = json_decode( $bar->bar_meta, true );
		return ( is_array( $all ) && isset( $all[ $key ] ) ) ? (array) $all[ $key ] : array();
	}

	/**
	 * Merge a typed key into a bar_meta JSON string.
	 * Preserves all other keys already stored.
	 *
	 * @param string $existing Existing bar_meta JSON (may be empty).
	 * @param string $key      Meta key.
	 * @param array  $values   Values to store.
	 * @return string Updated JSON string.
	 */
	public static function set_meta( $existing, $key, $values ) {
		$all = array();
		if ( ! empty( $existing ) ) {
			$decoded = json_decode( $existing, true );
			if ( is_array( $decoded ) ) {
				$all = $decoded;
			}
		}
		$all[ $key ] = $values;
		return wp_json_encode( $all );
	}
}
