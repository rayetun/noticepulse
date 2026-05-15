/**
 * NoticePulse — Display Triggers + Frequency Control Frontend
 *
 * Reads data-trigger and data-frequency attributes.
 * Handles: time-delay, scroll-depth, exit-intent, frequency gating.
 *
 * @package NoticePulse
 * @since   2.1.0
 */
( function () {
	'use strict';

	var STORAGE_KEY_PREFIX = 'np_freq_';

	function getStorage( key ) {
		try { return sessionStorage.getItem( key ); } catch ( e ) { return null; }
	}
	function setStorage( key, val ) {
		try { sessionStorage.setItem( key, val ); } catch ( e ) {}
	}
	function getCookie( name ) {
		var m = document.cookie.match( new RegExp( '(?:^|;\\s*)' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' ) );
		return m ? decodeURIComponent( m[1] ) : null;
	}
	function setCookie( name, value, days ) {
		var exp = new Date();
		exp.setTime( exp.getTime() + days * 864e5 );
		document.cookie = name + '=' + encodeURIComponent( value ) + '; expires=' + exp.toUTCString() + '; path=/; SameSite=Lax';
	}

	function showBar( bar ) {
		bar.style.display = '';
		bar.style.opacity = '1';
	}

	function initBar( bar ) {
		var barId     = bar.getAttribute( 'data-bar-id' );
		var trigger   = bar.getAttribute( 'data-trigger' )   || 'immediate';
		var frequency = bar.getAttribute( 'data-frequency' ) || 'session';
		var freqN     = parseInt( bar.getAttribute( 'data-frequency-n' ) || '5', 10 );

		var storageKey = STORAGE_KEY_PREFIX + barId;
		var cookieKey  = 'np_once_' + barId;
		var pvKey      = 'np_pv_' + barId;

		// ── Frequency gating ────────────────────────────────────────────────
		if ( 'once' === frequency ) {
			if ( getCookie( cookieKey ) ) { bar.style.display = 'none'; return; }
			bar.addEventListener( 'np:hidden', function () {
				setCookie( cookieKey, '1', 3650 );
			} );
		}

		if ( 'session' === frequency ) {
			if ( getStorage( storageKey ) ) { bar.style.display = 'none'; return; }
			bar.addEventListener( 'np:hidden', function () {
				setStorage( storageKey, '1' );
			} );
		}

		if ( 'pageviews' === frequency ) {
			var pv = parseInt( getStorage( pvKey ) || '0', 10 ) + 1;
			setStorage( pvKey, pv );
			if ( pv % freqN !== 1 ) { bar.style.display = 'none'; return; }
		}

		// 'always' → no gating

		// ── Trigger logic ────────────────────────────────────────────────────
		if ( 'immediate' === trigger ) {
			showBar( bar ); return;
		}

		// Hide until triggered.
		bar.style.display = 'none';

		if ( 'delay' === trigger ) {
			var delay = parseInt( bar.getAttribute( 'data-trigger-delay' ) || '3', 10 );
			setTimeout( function () { showBar( bar ); }, delay * 1000 );
			return;
		}

		if ( 'scroll' === trigger ) {
			var scrollPct = parseInt( bar.getAttribute( 'data-trigger-scroll' ) || '30', 10 );
			var shown     = false;
			function onScroll() {
				if ( shown ) { return; }
				var scrolled = ( window.scrollY || window.pageYOffset );
				var docH     = document.documentElement.scrollHeight - window.innerHeight;
				if ( docH <= 0 ) { return; }
				var pct = Math.round( ( scrolled / docH ) * 100 );
				if ( pct >= scrollPct ) {
					shown = true;
					window.removeEventListener( 'scroll', onScroll );
					showBar( bar );
				}
			}
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			return;
		}

		if ( 'exit_intent' === trigger ) {
			var exShown = false;
			function onMouseLeave( e ) {
				if ( exShown ) { return; }
				if ( e.clientY <= 10 ) {
					exShown = true;
					document.removeEventListener( 'mouseleave', onMouseLeave );
					showBar( bar );
				}
			}
			document.addEventListener( 'mouseleave', onMouseLeave );
			return;
		}
	}

	function init() {
		document.querySelectorAll( '.np-bar' ).forEach( initBar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
