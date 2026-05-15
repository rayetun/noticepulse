/**
 * NoticePulse — Google Analytics 4 Frontend
 *
 * Fires GA4 events when:
 *   1. A bar becomes visible (impression event)
 *   2. Visitor clicks the CTA button (click event)
 *
 * Uses gtag() if GA4 is already loaded on the page.
 * Falls back silently if gtag() is not available.
 *
 * Data attributes read from the bar element:
 *   data-ga4-id         — GA4 Measurement ID (G-XXXXXXXX)
 *   data-ga4-event      — Base event name for CTA clicks
 *   data-ga4-bar-name   — Bar name for event params
 *
 * @package NoticePulse
 * @since   2.1.0
 */
( function () {
	'use strict';

	// ── GA4 helper ──────────────────────────────────────────────────────────
	function pushEvent( measurementId, eventName, params ) {
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', eventName, params );
			return;
		}

		// gtag not loaded — try dataLayer (GTM compatibility).
		if ( window.dataLayer ) {
			window.dataLayer.push( Object.assign( { event: eventName }, params ) );
		}
	}

	// ── Intersection Observer for impressions ───────────────────────────────
	var observer = null;

	if ( 'IntersectionObserver' in window ) {
		observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) { return; }

				var bar  = entry.target;
				var id   = bar.getAttribute( 'data-ga4-id' );
				var name = bar.getAttribute( 'data-ga4-bar-name' ) || 'NoticePulse Bar';

				if ( id ) {
					pushEvent( id, 'noticepulse_impression', {
						bar_name: name,
						bar_id:   bar.getAttribute( 'data-bar-id' ),
					} );
				}

				// Only track once.
				observer.unobserve( bar );
			} );
		}, { threshold: 0.5 } );
	}

	// ── Init a single bar ───────────────────────────────────────────────────
	function initGA4Bar( bar ) {
		var measurementId = bar.getAttribute( 'data-ga4-id' );
		var eventName     = bar.getAttribute( 'data-ga4-event' ) || 'noticepulse_cta_click';
		var barName       = bar.getAttribute( 'data-ga4-bar-name' ) || 'NoticePulse Bar';
		var barId         = bar.getAttribute( 'data-bar-id' ) || '0';

		if ( ! measurementId ) { return; }

		// Track impression when bar enters viewport.
		if ( observer ) {
			observer.observe( bar );
		} else {
			// Fallback: fire immediately.
			pushEvent( measurementId, 'noticepulse_impression', {
				bar_name: barName,
				bar_id:   barId,
			} );
		}

		// Track CTA clicks.
		var cta = bar.querySelector( '.np-bar__cta, .np-email-btn, .np-call-btn, .np-coupon-btn' );
		if ( cta ) {
			cta.addEventListener( 'click', function () {
				pushEvent( measurementId, eventName, {
					bar_name:  barName,
					bar_id:    barId,
					cta_label: cta.textContent.trim().substring( 0, 50 ),
				} );
			} );
		}
	}

	// ── Boot ───────────────────────────────────────────────────────────────
	function init() {
		document.querySelectorAll( '.np-bar[data-ga4-id]' ).forEach( initGA4Bar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );