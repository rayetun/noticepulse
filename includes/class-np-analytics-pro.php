<?php
/**
 * NoticePulse — Pro Analytics.
 *
 * SQL safety strategy (satisfies WordPress Plugin Check / PHPCS):
 *
 *  1. Table names use the %i placeholder (identifier, WP 6.2+) inside
 *     $wpdb->prepare(). This is the only way to pass a table name without
 *     triggering InterpolatedNotPrepared — no string interpolation at all.
 *
 *  2. $wpdb->prepare() is called INLINE inside $wpdb->get_results() /
 *     get_var(). Storing the prepared query in a variable and then passing
 *     that variable triggers NotPrepared because the sniff cannot statically
 *     verify the variable's origin. Inlining removes the variable entirely.
 *
 *  3. The optional bar_id WHERE clause is handled by branching into two
 *     separate prepare() calls (with/without %d) rather than building a
 *     SQL fragment string conditionally.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Analytics_Pro {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_noticepulse_analytics_data', array( $this, 'ajax_get_data' ) );
		add_action( 'wp_ajax_noticepulse_export_csv',     array( $this, 'handle_csv_export' ) );
		add_action( 'wp_ajax_noticepulse_export_leads',   array( $this, 'handle_leads_export' ) );
		add_action( 'admin_enqueue_scripts',     array( $this, 'enqueue_assets' ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// ASSETS
	// ─────────────────────────────────────────────────────────────────────────

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'noticepulse-analytics' ) ) {
			return;
		}

		// Chart.js is bundled locally in vendor/ — no external CDN requests.
		wp_enqueue_script(
			'np-chartjs',
			NOTICEPULSE_PLUGIN_URL . 'vendor/chart.umd.min.js',
			array(),
			'4.5.1',
			true
		);

		wp_enqueue_script(
			'np-analytics-pro',
			NOTICEPULSE_PLUGIN_URL . 'admin/js/np-analytics-pro.js',
			array( 'jquery', 'np-chartjs' ),
			NOTICEPULSE_VERSION,
			true
		);

		wp_localize_script(
			'np-analytics-pro',
			'noticepulseAnalytics',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'noticepulse_analytics' ),
				'exportUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// AJAX — Analytics data
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Return analytics data for the requested date range as JSON.
	 */
	public function ajax_get_data() {
		check_ajax_referer( 'noticepulse_analytics', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$range  = isset( $_POST['range'] ) ? sanitize_key( $_POST['range'] ) : '30d';
		$bar_id = isset( $_POST['bar_id'] ) ? absint( $_POST['bar_id'] ) : 0;

		$days_map = array( '7d' => 7, '30d' => 30, '90d' => 90, 'all' => 3650 );
		$n        = isset( $days_map[ $range ] ) ? $days_map[ $range ] : 30;
		$from     = gmdate( 'Y-m-d', strtotime( "-{$n} days" ) );

		global $wpdb;
		$analytics_table = $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE;

		// ── Daily totals (for the chart) ──────────────────────────────────
		if ( $bar_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$daily = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT event_date, event_type, SUM(count) AS total
					 FROM %i
					 WHERE event_date >= %s AND bar_id = %d
					 GROUP BY event_date, event_type
					 ORDER BY event_date ASC',
					$analytics_table,
					$from,
					$bar_id
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$daily = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT event_date, event_type, SUM(count) AS total
					 FROM %i
					 WHERE event_date >= %s
					 GROUP BY event_date, event_type
					 ORDER BY event_date ASC',
					$analytics_table,
					$from
				),
				ARRAY_A
			);
		}

		// ── Per-bar summary (for the table) ───────────────────────────────
		if ( $bar_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$summary = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT bar_id, event_type, SUM(count) AS total
					 FROM %i
					 WHERE event_date >= %s AND bar_id = %d
					 GROUP BY bar_id, event_type',
					$analytics_table,
					$from,
					$bar_id
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$summary = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT bar_id, event_type, SUM(count) AS total
					 FROM %i
					 WHERE event_date >= %s
					 GROUP BY bar_id, event_type',
					$analytics_table,
					$from
				),
				ARRAY_A
			);
		}

		// ── Assemble chart data ───────────────────────────────────────────
		$chart = array();
		foreach ( (array) $daily as $row ) {
			$date = $row['event_date'];
			if ( ! isset( $chart[ $date ] ) ) {
				$chart[ $date ] = array(
					'date'        => $date,
					'impressions' => 0,
					'clicks'      => 0,
				);
			}
			$key = ( 'impression' === $row['event_type'] ) ? 'impressions' : 'clicks';
			$chart[ $date ][ $key ] += (int) $row['total'];
		}

		// ── Assemble per-bar data ─────────────────────────────────────────
		$per_bar = array();
		foreach ( (array) $summary as $row ) {
			$bid = (int) $row['bar_id'];
			if ( ! isset( $per_bar[ $bid ] ) ) {
				$per_bar[ $bid ] = array(
					'bar_id'      => $bid,
					'impressions' => 0,
					'clicks'      => 0,
				);
			}
			$key = ( 'impression' === $row['event_type'] ) ? 'impressions' : 'clicks';
			$per_bar[ $bid ][ $key ] += (int) $row['total'];
		}

		// Add CTR and bar names.
		$bars      = NoticePulse_DB::get_all_bars();
		$bar_names = array();
		foreach ( $bars as $b ) {
			$bar_names[ (int) $b->id ] = $b->name;
		}

		// Get leads count per bar from LEADS_TABLE.
		$leads_table  = $wpdb->prefix . NoticePulse_DB::LEADS_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$leads_rows   = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT bar_id, COUNT(*) AS total FROM %i GROUP BY bar_id',
				$leads_table
			),
			ARRAY_A
		);
		$leads_by_bar = array();
		foreach ( (array) $leads_rows as $lr ) {
			$leads_by_bar[ (int) $lr['bar_id'] ] = (int) $lr['total'];
		}

		foreach ( $per_bar as &$b ) {
			$b['ctr']   = $b['impressions'] > 0
				? round( ( $b['clicks'] / $b['impressions'] ) * 100, 1 )
				: 0;
			$b['name']  = isset( $bar_names[ $b['bar_id'] ] )
				? $bar_names[ $b['bar_id'] ]
				: 'Bar #' . $b['bar_id'];
			$b['leads'] = isset( $leads_by_bar[ $b['bar_id'] ] )
				? $leads_by_bar[ $b['bar_id'] ]
				: 0;
		}
		unset( $b );

		// Also compute total leads for the stat card.
		$total_leads = array_sum( $leads_by_bar );

		wp_send_json_success(
			array(
				'chart'        => array_values( $chart ),
				'per_bar'      => array_values( $per_bar ),
				'range'        => $range,
				'total_leads'  => $total_leads,
			)
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// CSV EXPORTS
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Download analytics data as CSV.
	 */
	public function handle_csv_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		check_ajax_referer( 'noticepulse_analytics', 'nonce' );

		global $wpdb;
		$analytics_table = $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT bar_id, event_type, event_date, count
				 FROM %i
				 ORDER BY event_date DESC, bar_id ASC',
				$analytics_table
			),
			ARRAY_A
		);

		$this->send_csv_headers( 'noticepulse-analytics-' . gmdate( 'Y-m-d' ) . '.csv' );
		$this->output_csv(
			array( 'Bar ID', 'Event Type', 'Date', 'Count' ),
			(array) $rows,
			array( 'bar_id', 'event_type', 'event_date', 'count' )
		);
		exit;
	}

	/**
	 * Download email leads as CSV.
	 */
	public function handle_leads_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		check_ajax_referer( 'noticepulse_analytics', 'nonce' );

		$bar_id      = isset( $_GET['bar_id'] ) ? absint( $_GET['bar_id'] ) : 0;
		global $wpdb;
		$leads_table = $wpdb->prefix . NoticePulse_DB::LEADS_TABLE;

		if ( $bar_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT bar_id, email, name, created_at
					 FROM %i
					 WHERE bar_id = %d
					 ORDER BY created_at DESC',
					$leads_table,
					$bar_id
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT bar_id, email, name, created_at
					 FROM %i
					 ORDER BY created_at DESC',
					$leads_table
				),
				ARRAY_A
			);
		}

		$this->send_csv_headers( 'noticepulse-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
		$this->output_csv(
			array( 'Bar ID', 'Email', 'Name', 'Date' ),
			(array) $rows,
			array( 'bar_id', 'email', 'name', 'created_at' )
		);
		exit;
	}

	/**
	 * Send CSV download headers.
	 *
	 * @param string $filename Filename (will be sanitised).
	 */
	private function send_csv_headers( $filename ) {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
			header( 'Pragma: no-cache' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Expires: 0' );
		}
	}

	/**
	 * Stream CSV rows directly to php://output — no real filesystem path,
	 * so WP_Filesystem is not applicable here. Sniff suppressed accordingly.
	 *
	 * @param array $headers Column header labels.
	 * @param array $rows    Associative-array rows.
	 * @param array $keys    Keys to extract from each row, in column order.
	 */
	private function output_csv( array $headers, array $rows, array $keys ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$out = fopen( 'php://output', 'w' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		fputcsv( $out, $headers );
		foreach ( $rows as $row ) {
			$line = array();
			foreach ( $keys as $k ) {
				$line[] = isset( $row[ $k ] ) ? $row[ $k ] : '';
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
			fputcsv( $out, $line );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// STATIC HELPERS — called from the analytics view
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Get total leads count, optionally filtered by bar.
	 *
	 * @param int $bar_id Optional bar ID (0 = all bars).
	 * @return int
	 */
	public static function get_leads_count( $bar_id = 0 ) {
		global $wpdb;
		$leads_table = $wpdb->prefix . NoticePulse_DB::LEADS_TABLE;

		if ( $bar_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE bar_id = %d',
					$leads_table,
					absint( $bar_id )
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i',
				$leads_table
			)
		);
	}

	/**
	 * Get recent leads, optionally filtered by bar.
	 *
	 * @param int $bar_id Optional bar ID (0 = all bars).
	 * @param int $limit  Max rows to return.
	 * @return array
	 */
	public static function get_recent_leads( $bar_id = 0, $limit = 50 ) {
		global $wpdb;
		$leads_table = $wpdb->prefix . NoticePulse_DB::LEADS_TABLE;
		$limit       = absint( $limit ) ?: 50;

		if ( $bar_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE bar_id = %d ORDER BY created_at DESC LIMIT %d',
					$leads_table,
					absint( $bar_id ),
					$limit
				),
				ARRAY_A
			) ?: array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d',
				$leads_table,
				$limit
			),
			ARRAY_A
		) ?: array();
	}
}
