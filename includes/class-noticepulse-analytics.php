<?php
/**
 * Analytics class for NoticePulse.
 *
 * Handles recording and retrieving impression/click data.
 *
 * @package NoticePulse
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NoticePulse_Analytics
 */
class NoticePulse_Analytics {

	/**
	 * Record an event (impression or click) for a bar.
	 *
	 * @param int    $bar_id     The bar ID.
	 * @param string $event_type 'impression' or 'click'.
	 * @return bool
	 */
	public static function record_event( $bar_id, $event_type ) {
		global $wpdb;

		$bar_id = absint( $bar_id );
		if ( ! $bar_id ) {
			return false;
		}

		$allowed_events = array( 'impression', 'click' );
		if ( ! in_array( $event_type, $allowed_events, true ) ) {
			return false;
		}

		$table      = $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE;
		$event_date = current_time( 'Y-m-d' );

		// Use INSERT ... ON DUPLICATE KEY UPDATE for atomic upsert.
		// Table name is derived from $wpdb->prefix — safe, not user input.
		// Analytics writes must never be cached — always record fresh events.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"INSERT INTO {$table} (bar_id, event_type, event_date, count)
				VALUES (%d, %s, %s, 1)
				ON DUPLICATE KEY UPDATE count = count + 1",
				$bar_id,
				$event_type,
				$event_date
			)
		);

		return false !== $result;
	}

	/**
	 * Get total stats for all bars.
	 *
	 * @return array Keyed by bar_id with impressions and clicks.
	 */
	public static function get_all_stats() {
		global $wpdb;

		$table = $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			"SELECT bar_id, event_type, SUM(count) AS total FROM {$table} GROUP BY bar_id, event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$stats = array();
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$bid = (int) $row['bar_id'];
				if ( ! isset( $stats[ $bid ] ) ) {
					$stats[ $bid ] = array(
						'impressions' => 0,
						'clicks'      => 0,
					);
				}
				if ( 'impression' === $row['event_type'] ) {
					$stats[ $bid ]['impressions'] = (int) $row['total'];
				} elseif ( 'click' === $row['event_type'] ) {
					$stats[ $bid ]['clicks'] = (int) $row['total'];
				}
			}
		}

		return $stats;
	}

	/**
	 * Get stats for a specific bar.
	 *
	 * @param int $bar_id Bar ID.
	 * @return array With 'impressions' and 'clicks' keys.
	 */
	public static function get_bar_stats( $bar_id ) {
		global $wpdb;

		$bar_id = absint( $bar_id );
		$table  = $wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT event_type, SUM(count) AS total FROM {$table} WHERE bar_id = %d GROUP BY event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$bar_id
			),
			ARRAY_A
		);

		$stats = array(
			'impressions' => 0,
			'clicks'      => 0,
		);

		if ( $rows ) {
			foreach ( $rows as $row ) {
				if ( 'impression' === $row['event_type'] ) {
					$stats['impressions'] = (int) $row['total'];
				} elseif ( 'click' === $row['event_type'] ) {
					$stats['clicks'] = (int) $row['total'];
				}
			}
		}

		return $stats;
	}

	/**
	 * Reset analytics for a bar.
	 *
	 * @param int $bar_id Bar ID.
	 * @return bool
	 */
	public static function reset_bar_stats( $bar_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$wpdb->prefix . NoticePulse_DB::ANALYTICS_TABLE,
			array( 'bar_id' => absint( $bar_id ) )
		);

		return false !== $result;
	}
}
