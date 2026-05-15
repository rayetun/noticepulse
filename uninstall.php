<?php
/**
 * NoticePulse Uninstall Script
 *
 * Runs when the plugin is deleted (not just deactivated).
 * Removes all plugin data from the database.
 *
 * @package NoticePulse
 * @since   1.0.0
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Only delete data if the user opted in (optional — good practice).
// You could add a setting: "Delete all data on uninstall" and gate this.

require_once plugin_dir_path( __FILE__ ) . 'includes/class-noticepulse-db.php';

// Drop custom tables.
NoticePulse_DB::drop_tables();

// Remove plugin options.
delete_option( 'noticepulse_db_version' );

// Clean up on multisite.
if ( is_multisite() ) {
	$np_sites = get_sites( array( 'number' => 0 ) );
	foreach ( $np_sites as $np_site ) {
		switch_to_blog( $np_site->blog_id );
		NoticePulse_DB::drop_tables();
		delete_option( 'noticepulse_db_version' );
		restore_current_blog();
	}
}
