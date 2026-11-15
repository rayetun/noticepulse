/**
 * NoticePulse — WooCommerce free-shipping bar (live cart updates).
 *
 * The bar's message is a WooCommerce cart fragment (registered server-side), so
 * add-to-cart refreshes it automatically. On the cart and checkout pages,
 * quantity/total changes fire their own events but not a fragment refresh — this
 * bridges those events to one, so the bar stays in sync everywhere.
 *
 * @package NoticePulse
 * @since   2.3.0
 */
/* global window */

( function () {
	'use strict';

	var $ = window.jQuery;
	if ( ! $ ) { return; }

	$( document.body ).on( 'updated_cart_totals updated_checkout', function () {
		$( document.body ).trigger( 'wc_fragment_refresh' );
	} );

} )();
