/**
 * NoticePulse Pro Admin JS — Phase 2 Premium Dashboard
 *
 * Handles:
 *  - Upgrade banner dismiss (localStorage)
 *  - Stat counter animation
 *
 * @package NoticePulse
 * @since   1.1.0
 */
/* global noticepulseAdmin */
( function () {
	'use strict';

	var BANNER_KEY = 'np_banner_dismissed';

	/**
	 * Dismiss promo banner and remember via localStorage.
	 */
	function initBannerDismiss() {
		var banner  = document.getElementById( 'np2-promo-banner' );
		var dismiss = document.getElementById( 'np2-dismiss-banner' );

		if ( ! banner ) { return; }

		// Already dismissed this session? Hide immediately.
		try {
			if ( sessionStorage.getItem( BANNER_KEY ) ) {
				banner.style.display = 'none';
				return;
			}
		} catch ( e ) {}

		if ( dismiss ) {
			dismiss.addEventListener( 'click', function () {
				banner.style.transition = 'opacity .3s, max-height .4s, margin .4s, padding .4s';
				banner.style.opacity    = '0';
				banner.style.maxHeight  = '0';
				banner.style.margin     = '0';
				banner.style.padding    = '0';
				banner.style.overflow   = 'hidden';
				try { sessionStorage.setItem( BANNER_KEY, '1' ); } catch ( e ) {}
			} );
		}
	}

	/**
	 * Animate stat counters from 0 to target value on page load.
	 */
	function initCountUp() {
		var els = document.querySelectorAll( '.np2-countup' );
		if ( ! els.length ) { return; }

		els.forEach( function ( el ) {
			var target   = parseInt( el.getAttribute( 'data-target' ) || '0', 10 );
			var duration = 800; // ms
			var steps    = 30;
			var step     = 0;

			if ( target === 0 ) { return; }

			var timer = setInterval( function () {
				step++;
				var val = Math.round( target * easeOut( step / steps ) );
				el.textContent = val.toLocaleString();
				if ( step >= steps ) {
					clearInterval( timer );
					el.textContent = target.toLocaleString();
				}
			}, duration / steps );
		} );
	}

	/**
	 * Ease-out curve.
	 *
	 * @param {number} t 0–1
	 * @return {number}
	 */
	function easeOut( t ) {
		return 1 - Math.pow( 1 - t, 3 );
	}

	/**
	 * Animate CTR fill bars after a short delay.
	 */
	function initCtrBars() {
		var fills = document.querySelectorAll( '.np2-bar-card__ctr-fill' );
		if ( ! fills.length ) { return; }

		// Reset to 0 first (CSS transition will animate on set).
		fills.forEach( function ( el ) {
			var targetW = el.style.width;
			el.style.width = '0%';
			setTimeout( function () {
				el.style.width = targetW;
			}, 200 );
		} );
	}

	/**
	 * Boot on DOM ready.
	 */
	function init() {
		initBannerDismiss();
		initCountUp();
		initCtrBars();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
