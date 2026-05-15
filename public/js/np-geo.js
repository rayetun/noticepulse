/**
 * NoticePulse — Geo-Targeting Client Helper
 *
 * The actual geo-filtering happens server-side in
 * class-np-geo-target.php before the bar HTML is rendered.
 * Bars that don't match the visitor's country are simply
 * never output — so this file has minimal work to do.
 *
 * This script exists as a hook point for future client-side
 * geo features (e.g. showing country-specific pricing,
 * swapping currency symbols, etc.).
 *
 * Currently it just marks the bar with the server-resolved
 * data-geo-country attribute so CSS/other scripts can use it.
 *
 * @package NoticePulse
 * @since   2.1.0
 */
( function () {
	'use strict';

	// Bars already filtered server-side — nothing to hide/show.
	// This function can be extended to add client-side geo features.
	function init() {
		// Mark all rendered bars as "geo-passed" (they've already
		// been filtered server-side, so if they're visible, they passed).
		document.querySelectorAll( '.np-bar' ).forEach( function ( bar ) {
			if ( bar.hasAttribute( 'data-geo-mode' ) ) {
				bar.setAttribute( 'data-geo-ok', '1' );
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );