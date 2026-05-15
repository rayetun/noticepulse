/**
 * NoticePulse — A/B Testing Frontend
 *
 * Assigns each visitor to Variant A or Variant B (50/50)
 * using a session cookie. Swaps bar content for Variant B.
 * Tracks impressions and clicks per variant.
 *
 * @package NoticePulse
 * @since   2.1.0
 */
( function () {
	'use strict';

	// ── Cookie helpers ──────────────────────────────────────────────────────
	function getCookie( name ) {
		var m = document.cookie.match(
			new RegExp( '(?:^|;\\s*)' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' )
		);
		return m ? decodeURIComponent( m[1] ) : null;
	}

	function setSessionCookie( name, value ) {
		// Session cookie — no expires, cleared when browser closes.
		document.cookie = name + '=' + encodeURIComponent( value ) + '; path=/; SameSite=Lax';
	}

	// ── Init a single A/B bar ──────────────────────────────────────────────
	function initABTest( bar ) {
		var barId      = bar.getAttribute( 'data-bar-id' );
		var messageB   = bar.getAttribute( 'data-ab-message' )   || '';
		var ctaLabelB  = bar.getAttribute( 'data-ab-cta-label' ) || '';
		var ctaUrlB    = bar.getAttribute( 'data-ab-cta-url' )   || '';

		if ( ! messageB ) { return; }

		var cookieKey = 'np_ab_' + barId;
		var variant   = getCookie( cookieKey );

		// First visit: assign randomly 50/50.
		if ( ! variant ) {
			variant = Math.random() < 0.5 ? 'a' : 'b';
			setSessionCookie( cookieKey, variant );
		}

		// Mark bar element for CSS/analytics.
		bar.setAttribute( 'data-ab-variant', variant );

		// Swap content for Variant B.
		if ( 'b' === variant ) {
			var msgEl = bar.querySelector( '.np-bar__message' );
			var ctaEl = bar.querySelector( '.np-bar__cta' );

			if ( msgEl ) { msgEl.innerHTML = messageB; }

			if ( ctaEl && ctaLabelB ) {
				ctaEl.textContent = ctaLabelB;
				if ( ctaUrlB ) { ctaEl.href = ctaUrlB; }
			}
		}
	}

	// ── Boot ───────────────────────────────────────────────────────────────
	function init() {
		document.querySelectorAll( '.np-bar[data-ab="1"]' ).forEach( initABTest );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );