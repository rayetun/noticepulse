/**
 * NoticePulse Public JavaScript
 *
 * Handles bar display, cookie-based dismissal, and AJAX analytics tracking.
 * Vanilla JS only – no jQuery dependency.
 *
 * @package NoticePulse
 * @since   1.0.0
 */
/* global noticepulseData */

( function () {
	'use strict';

	/**
	 * Cookie helpers.
	 */
	var Cookie = {
		get: function ( name ) {
			var match = document.cookie.match(
				new RegExp( '(?:^|;\\s*)' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' )
			);
			return match ? decodeURIComponent( match[ 1 ] ) : null;
		},
		set: function ( name, value, days ) {
			var expires = '';
			if ( days ) {
				var d = new Date();
				d.setTime( d.getTime() + days * 24 * 60 * 60 * 1000 );
				expires = '; expires=' + d.toUTCString();
			}
			document.cookie = name + '=' + encodeURIComponent( value ) + expires + '; path=/; SameSite=Lax';
		},
	};

	/**
	 * Send analytics event via AJAX.
	 *
	 * @param {number} barId     The bar ID.
	 * @param {string} eventType 'impression' or 'click'.
	 */
	function trackEvent( barId, eventType ) {
		if ( ! noticepulseData || ! noticepulseData.ajaxUrl ) {
			return;
		}

		var data = new FormData();
		data.append( 'action',     'noticepulse_track' );
		data.append( 'nonce',      noticepulseData.nonce );
		data.append( 'bar_id',     barId );
		data.append( 'event_type', eventType );

		// Fire-and-forget; we don't block on the response.
		if ( navigator.sendBeacon ) {
			var blob = new Blob(
				[ new URLSearchParams( {
					action:     'noticepulse_track',
					nonce:      noticepulseData.nonce,
					bar_id:     barId,
					event_type: eventType,
				} ).toString() ],
				{ type: 'application/x-www-form-urlencoded' }
			);
			navigator.sendBeacon( noticepulseData.ajaxUrl, blob );
		} else {
			fetch( noticepulseData.ajaxUrl, {
				method:      'POST',
				credentials: 'same-origin',
				body:        data,
			} ).catch( function () {} );
		}
	}

	/**
	 * Dismiss (hide) a notification bar.
	 *
	 * @param {HTMLElement} bar         The bar element.
	 * @param {number}      barId       The bar's numeric ID.
	 * @param {number}      cookieDays  How many days to suppress after dismiss.
	 */
	function dismissBar( bar, barId, cookieDays ) {
		bar.classList.add( 'np-bar--hiding' );

		bar.addEventListener( 'animationend', function () {
			bar.remove();
			adjustBodyPadding();
		}, { once: true } );

		if ( cookieDays > 0 ) {
			Cookie.set( 'np_dismissed_' + barId, '1', cookieDays );
		}
	}

	/**
	 * Adjust body top/bottom padding to accommodate sticky bars.
	 */
	function adjustBodyPadding() {
		var topBars    = document.querySelectorAll( '.np-bar--top.np-bar--sticky' );
		var bottomBars = document.querySelectorAll( '.np-bar--bottom.np-bar--sticky' );

		var topHeight    = 0;
		var bottomHeight = 0;

		topBars.forEach( function ( b ) {
			topHeight += b.offsetHeight;
		} );

		bottomBars.forEach( function ( b ) {
			bottomHeight += b.offsetHeight;
		} );

		document.documentElement.style.setProperty( '--np-bar-height', topHeight + 'px' );

		if ( topHeight > 0 ) {
			document.body.classList.add( 'np-has-top-bar' );
			document.body.style.paddingTop = topHeight + 'px';
		} else {
			document.body.classList.remove( 'np-has-top-bar' );
			document.body.style.paddingTop = '';
		}

		if ( bottomHeight > 0 ) {
			document.body.classList.add( 'np-has-bottom-bar' );
			document.body.style.paddingBottom = bottomHeight + 'px';
		} else {
			document.body.classList.remove( 'np-has-bottom-bar' );
			document.body.style.paddingBottom = '';
		}
	}

	/**
	 * Initialise a single notification bar.
	 *
	 * @param {HTMLElement} bar The bar element.
	 */
	function initBar( bar ) {
		var barId      = parseInt( bar.getAttribute( 'data-bar-id' ), 10 );
		var cookieDays = parseInt( bar.getAttribute( 'data-cookie-days' ), 10 ) || 0;

		// Check dismiss cookie.
		if ( cookieDays > 0 && Cookie.get( 'np_dismissed_' + barId ) ) {
			bar.remove();
			return;
		}

		// Insert into DOM at correct position.
		var position = bar.getAttribute( 'data-position' ) || 'top';
		if ( 'bottom' === position ) {
			document.body.appendChild( bar );
		} else {
			document.body.insertBefore( bar, document.body.firstChild );
		}

		// Adjust body padding for sticky bars.
		adjustBodyPadding();

		// Track impression.
		trackEvent( barId, 'impression' );

		// Close button.
		var closeBtn = bar.querySelector( '.np-bar__close' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
				dismissBar( bar, barId, cookieDays );
			} );
		}

		// CTA click tracking.
		var ctaBtn = bar.querySelector( '.np-bar__cta' );
		if ( ctaBtn ) {
			ctaBtn.addEventListener( 'click', function () {
				trackEvent( barId, 'click' );
			} );
		}
	}

	/**
	 * Initialize all bars on the page.
	 */
	function initAllBars() {
		var bars = document.querySelectorAll( '.np-bar' );
		if ( ! bars.length ) {
			return;
		}
		bars.forEach( initBar );
	}

	/**
	 * Re-calculate padding on window resize.
	 */
	var resizeTimer;
	window.addEventListener( 'resize', function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( adjustBodyPadding, 150 );
	} );

	/**
	 * Boot when DOM is ready.
	 */
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initAllBars );
	} else {
		initAllBars();
	}

} )();
