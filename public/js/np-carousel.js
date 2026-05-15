/**
 * NoticePulse — Text Carousel Frontend
 *
 * Renamed from np-ticker.js. The bar type formerly called
 * "Rotating Ticker" is now "Text Carousel" — it cycles through
 * messages with fade or slide transitions (carousel behaviour).
 *
 *
 * FIX: Per-message CTA buttons now display correctly. Previously
 * ctaEl.style.display = '' did not work when the default CTA
 * anchor was removed or hidden by other bar-type JS. Now the
 * carousel builds its own CTA element inside the nav area.
 *
 * @package NoticePulse
 * @since   2.1.2
 */
( function () {
	'use strict';

	function np_initCarousel( bar ) {
		var rawMessages = bar.getAttribute( 'data-ticker-messages' );
		var speedSec    = parseInt( bar.getAttribute( 'data-ticker-speed' )   || '4', 10 );
		var transition  = bar.getAttribute( 'data-ticker-transition' )         || 'fade';
		var pauseHover  = bar.getAttribute( 'data-ticker-pause' )             === '1';
		var showArrows  = bar.getAttribute( 'data-ticker-arrows' )            === '1';
		var showDots    = bar.getAttribute( 'data-ticker-dots' )              === '1';

		var messages;
		try { messages = JSON.parse( rawMessages ); } catch ( e ) { return; }
		if ( ! messages || ! messages.length ) { return; }

		// Read button colors from CSS vars (set by PHP on the bar element).
		var btnBg  = getComputedStyle( bar ).getPropertyValue( '--np-btn-bg'  ).trim() || 'rgba(255,255,255,.92)';
		var btnTxt = getComputedStyle( bar ).getPropertyValue( '--np-btn-txt' ).trim() || '#1d2327';

		var content = bar.querySelector( '.np-bar__content' );
		var msgEl   = content ? content.querySelector( '.np-bar__message' ) : null;
		if ( ! content || ! msgEl ) { return; }

		// Remove the static PHP-rendered CTA — carousel manages its own.
		var staticCta = bar.querySelector( '.np-bar__cta' );
		if ( staticCta ) { staticCta.remove(); }

		// Build a carousel-managed CTA anchor.
		var ctaEl      = document.createElement( 'a' );
		ctaEl.className = 'np-bar__cta np-carousel-cta';
		ctaEl.style.display = 'none';
		ctaEl.style.background = btnBg;
		ctaEl.style.color      = btnTxt;
		content.appendChild( ctaEl );

		// Single message — display it and stop.
		if ( messages.length === 1 ) {
			msgEl.textContent = messages[0].text;
			if ( messages[0].cta_label && messages[0].cta_url ) {
				ctaEl.textContent  = messages[0].cta_label;
				ctaEl.href         = messages[0].cta_url;
				ctaEl.style.display = '';
			}
			return;
		}

		var current = 0;
		var paused  = false;
		var timer   = null;

		/* ── Show a message ─────────────────────────────────────────────── */

		function applyMessage( idx ) {
			var msg = messages[ idx ];
			if ( ! msg ) { return; }
			msgEl.textContent = msg.text;

			if ( msg.cta_label && msg.cta_url ) {
				ctaEl.textContent   = msg.cta_label;
				ctaEl.href          = msg.cta_url;
				ctaEl.style.display = '';
				// Re-apply colors in case they were lost.
				ctaEl.style.background = btnBg;
				ctaEl.style.color      = btnTxt;
			} else {
				ctaEl.style.display = 'none';
			}
			updateDots( idx );
		}

		function showMessage( idx, animate ) {
			if ( ! animate ) {
				applyMessage( idx );
				return;
			}

			if ( 'slide' === transition ) {
				msgEl.style.transition = 'transform 0.28s ease, opacity 0.28s ease';
				msgEl.style.transform  = 'translateY(-10px)';
				msgEl.style.opacity    = '0';
				setTimeout( function () {
					applyMessage( idx );
					msgEl.style.transform = 'translateY(10px)';
					setTimeout( function () {
						msgEl.style.transform = 'translateY(0)';
						msgEl.style.opacity   = '1';
					}, 20 );
				}, 290 );
			} else {
				// Fade (default).
				msgEl.style.transition = 'opacity 0.32s ease';
				msgEl.style.opacity    = '0';
				setTimeout( function () {
					applyMessage( idx );
					msgEl.style.opacity = '1';
				}, 340 );
			}
		}

		/* ── Dots ────────────────────────────────────────────────────────── */

		function updateDots( idx ) {
			if ( ! showDots ) { return; }
			bar.querySelectorAll( '.np-ticker-dot' ).forEach( function ( dot, i ) {
				dot.classList.toggle( 'np-ticker-dot--active', i === idx );
			} );
		}

		/* ── Navigation ──────────────────────────────────────────────────── */

		function next() {
			current = ( current + 1 ) % messages.length;
			showMessage( current, true );
		}

		function prev() {
			current = ( current - 1 + messages.length ) % messages.length;
			showMessage( current, true );
		}

		function startTimer() {
			timer = setInterval( function () {
				if ( ! paused ) { next(); }
			}, speedSec * 1000 );
		}

		/* ── Build nav UI ────────────────────────────────────────────────── */

		var nav = document.createElement( 'div' );
		nav.className = 'np-ticker-nav';

		if ( showArrows ) {
			var prevBtn       = document.createElement( 'button' );
			prevBtn.type      = 'button';
			prevBtn.className = 'np-ticker-arrow np-ticker-arrow--prev';
			prevBtn.innerHTML = '&#8249;';
			prevBtn.setAttribute( 'aria-label', 'Previous' );
			prevBtn.addEventListener( 'click', function () {
				prev(); clearInterval( timer ); startTimer();
			} );
			nav.appendChild( prevBtn );
		}

		if ( showDots ) {
			var dotsEl = document.createElement( 'div' );
			dotsEl.className = 'np-ticker-dots';
			messages.forEach( function ( _, i ) {
				var dot       = document.createElement( 'span' );
				dot.className = 'np-ticker-dot' + ( i === 0 ? ' np-ticker-dot--active' : '' );
				dot.addEventListener( 'click', function () {
					current = i; showMessage( i, true );
					clearInterval( timer ); startTimer();
				} );
				dotsEl.appendChild( dot );
			} );
			nav.appendChild( dotsEl );
		}

		if ( showArrows ) {
			var nextBtn       = document.createElement( 'button' );
			nextBtn.type      = 'button';
			nextBtn.className = 'np-ticker-arrow np-ticker-arrow--next';
			nextBtn.innerHTML = '&#8250;';
			nextBtn.setAttribute( 'aria-label', 'Next' );
			nextBtn.addEventListener( 'click', function () {
				next(); clearInterval( timer ); startTimer();
			} );
			nav.appendChild( nextBtn );
		}

		if ( showArrows || showDots ) {
			content.appendChild( nav );
		}

		/* ── Pause on hover ──────────────────────────────────────────────── */

		if ( pauseHover ) {
			bar.addEventListener( 'mouseenter', function () { paused = true; } );
			bar.addEventListener( 'mouseleave', function () { paused = false; } );
		}

		/* ── Boot ────────────────────────────────────────────────────────── */

		showMessage( 0, false );
		startTimer();
	}

	function np_init() {
		// data-ticker="1" and data-ticker-type="carousel" (or unset = carousel)
		document.querySelectorAll( '.np-bar[data-ticker="1"]:not([data-ticker-type="news"])' ).forEach( np_initCarousel );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', np_init );
	} else {
		np_init();
	}

}() );
