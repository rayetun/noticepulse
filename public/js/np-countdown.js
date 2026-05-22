/**
 * NoticePulse — Countdown Timer Frontend
 *
 * Reads data-countdown (Unix timestamp) from bar element.
 * LAYOUT: [Message] [Timer] [CTA Button]
 * The timer is inserted before the CTA button so the
 * CTA always appears to the right of the countdown.
 *
 * @package NoticePulse
 * @since   2.1.3
 */
( function () {
	'use strict';

	function pad( n ) { return n < 10 ? '0' + n : String( n ); }

	function initCountdown( bar ) {
		var endTs    = parseInt( bar.getAttribute( 'data-countdown' ) || '0', 10 );
		var labelD   = bar.getAttribute( 'data-countdown-days' )  || 'Days';
		var labelH   = bar.getAttribute( 'data-countdown-hours' ) || 'Hours';
		var labelM   = bar.getAttribute( 'data-countdown-mins' )  || 'Mins';
		var labelS    = bar.getAttribute( 'data-countdown-secs' )       || 'Secs';
		var showHours = bar.getAttribute( 'data-countdown-show-hours' ) !== '0';
		var showMins  = bar.getAttribute( 'data-countdown-show-mins' )  !== '0';
		var showSecs  = bar.getAttribute( 'data-countdown-show-secs' )  !== '0';
		var hideOnEnd= bar.getAttribute( 'data-countdown-hide' ) === '1';
		var barId    = bar.getAttribute( 'data-bar-id' ) || '0';

		if ( ! endTs || endTs <= Math.floor( Date.now() / 1000 ) ) {
			if ( hideOnEnd ) { bar.style.display = 'none'; }
			return;
		}

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Build countdown widget.
		var widget = document.createElement( 'div' );
		widget.className = 'np-countdown';
		// Build timer HTML — Days always shown; Hours, Mins, Secs are optional.
		var timerHtml = '<span class="np-cd-block"><span class="np-cd-num" id="np-cd-d-' + barId + '">00</span><span class="np-cd-lbl">' + labelD + '</span></span>';
		if ( showHours ) {
			timerHtml += '<span class="np-cd-sep">:</span>' +
			             '<span class="np-cd-block"><span class="np-cd-num" id="np-cd-h-' + barId + '">00</span><span class="np-cd-lbl">' + labelH + '</span></span>';
		}
		if ( showMins ) {
			timerHtml += '<span class="np-cd-sep">:</span>' +
			             '<span class="np-cd-block"><span class="np-cd-num" id="np-cd-m-' + barId + '">00</span><span class="np-cd-lbl">' + labelM + '</span></span>';
		}
		if ( showSecs ) {
			timerHtml += '<span class="np-cd-sep">:</span>' +
			             '<span class="np-cd-block"><span class="np-cd-num" id="np-cd-s-' + barId + '">00</span><span class="np-cd-lbl">' + labelS + '</span></span>';
		}
		widget.innerHTML = timerHtml;

		// Insert timer BEFORE the CTA button so layout is:
		// [Message text] [Timer] [CTA Button]
		var ctaBtn = content.querySelector( '.np-bar__cta' );
		if ( ctaBtn ) {
			content.insertBefore( widget, ctaBtn );
		} else {
			content.appendChild( widget );
		}

		var dEl = document.getElementById( 'np-cd-d-' + barId );
		var hEl = document.getElementById( 'np-cd-h-' + barId );
		var mEl = document.getElementById( 'np-cd-m-' + barId );
		var sEl = document.getElementById( 'np-cd-s-' + barId );

		function tick() {
			var now  = Math.floor( Date.now() / 1000 );
			var diff = endTs - now;

			if ( diff <= 0 ) {
				if ( dEl ) { dEl.textContent = '00'; }
				if ( hEl ) { hEl.textContent = '00'; }
				if ( mEl ) { mEl.textContent = '00'; }
				if ( sEl ) { sEl.textContent = '00'; }
				clearInterval( timer );
				if ( hideOnEnd ) {
					setTimeout( function () { bar.style.display = 'none'; }, 1000 );
				}
				return;
			}

			var d = Math.floor( diff / 86400 );
			var h = Math.floor( ( diff % 86400 ) / 3600 );
			var m = Math.floor( ( diff % 3600 ) / 60 );
			var s = diff % 60;

			if ( dEl ) { dEl.textContent = pad( d ); }
			if ( hEl ) { hEl.textContent = pad( h ); }
			if ( mEl ) { mEl.textContent = pad( m ); }
			if ( sEl ) { sEl.textContent = pad( s ); }
		}

		tick();
		var timer = setInterval( tick, 1000 );
	}

	function init() {
		document.querySelectorAll( '.np-bar[data-countdown]' ).forEach( initCountdown );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
