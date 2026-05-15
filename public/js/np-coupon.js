/**
 * NoticePulse — Click-to-Copy Coupon Frontend
 *
 * Reads data-coupon from bar. Replaces CTA button with a
 * styled coupon code + copy button. On click, copies code
 * to clipboard and shows a success label.
 *
 * FIX: Button colors now read from CSS custom properties
 * (--np-btn-bg, --np-btn-txt) set on the bar element, so
 * the user's saved colors are always applied correctly.
 *
 * @package NoticePulse
 * @since   2.1.2
 */
( function () {
	'use strict';

	/**
	 * Read a CSS custom property from an element, with fallback.
	 *
	 * @param {HTMLElement} el       Element to read from.
	 * @param {string}      prop     CSS property name.
	 * @param {string}      fallback Fallback value if unset.
	 * @return {string}
	 */
	function getCSSVar( el, prop, fallback ) {
		var val = getComputedStyle( el ).getPropertyValue( prop ).trim();
		return val || fallback;
	}

	function initCoupon( bar ) {
		var code         = bar.getAttribute( 'data-coupon' );
		var btnLabel     = bar.getAttribute( 'data-coupon-btn' )     || 'Copy Code';
		var successLabel = bar.getAttribute( 'data-coupon-success' ) || '✓ Copied!';

		if ( ! code ) { return; }

		// FIX: Read button colors from CSS vars on the bar element.
		var btnBg  = getCSSVar( bar, '--np-btn-bg',  'rgba(255,255,255,0.92)' );
		var btnTxt = getCSSVar( bar, '--np-btn-txt', '#1d2327' );

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Remove default CTA button.
		var defaultCta = bar.querySelector( '.np-bar__cta' );
		if ( defaultCta ) { defaultCta.remove(); }

		// Build coupon UI.
		var couponWrap = document.createElement( 'div' );
		couponWrap.className = 'np-coupon-wrap';
		couponWrap.innerHTML =
			'<span class="np-coupon-code">' + code + '</span>' +
			'<button type="button" class="np-coupon-btn">' + btnLabel + '</button>';

		content.appendChild( couponWrap );

		var btn = couponWrap.querySelector( '.np-coupon-btn' );
		if ( ! btn ) { return; }

		// FIX: Apply custom colors directly to the button element.
		btn.style.background = btnBg;
		btn.style.color      = btnTxt;

		btn.addEventListener( 'click', function () {
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( code ).then( function () {
					btn.textContent = successLabel;
					setTimeout( function () {
						btn.textContent = btnLabel;
					}, 2500 );
				} );
			} else {
				// Fallback for older browsers.
				var ta        = document.createElement( 'textarea' );
				ta.value      = code;
				ta.style.position = 'fixed';
				ta.style.opacity  = '0';
				document.body.appendChild( ta );
				ta.select();
				try { document.execCommand( 'copy' ); } catch ( e ) {}
				document.body.removeChild( ta );
				btn.textContent = successLabel;
				setTimeout( function () { btn.textContent = btnLabel; }, 2500 );
			}
		} );
	}

	// Structural CSS only — no color values.
	// Styles moved to noticepulse-public.css — enqueued via wp_enqueue_style().

	function init() {
		document.querySelectorAll( '.np-bar[data-coupon]' ).forEach( initCoupon );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
