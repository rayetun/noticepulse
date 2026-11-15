/**
 * NoticePulse — Marquee / News Ticker.
 *
 * Turns a `.np-bar[data-marquee="1"]` into a continuously-scrolling bar by
 * cloning its message into a seamless track. Constant pixels-per-second speed
 * regardless of message length. Honors prefers-reduced-motion (renders static).
 *
 * Depends on noticepulse-public.js (window.NoticePulse event bus).
 *
 * @package NoticePulse
 * @since   2.3.0
 */
/* global window, document */

( function () {
	'use strict';

	var reduceMotion = window.matchMedia &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function buildMarquee( bar ) {
		if ( ! bar || bar.getAttribute( 'data-np-marquee-ready' ) === '1' ) { return; }

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Message HTML comes from the original node on first build, or from an
		// existing marquee item on a resize rebuild (the node was replaced).
		var msg          = content.querySelector( '.np-bar__message' );
		var existingItem = content.querySelector( '.np-marquee__item' );
		var htmlStr;
		if ( msg ) { htmlStr = msg.innerHTML; }
		else if ( existingItem ) { htmlStr = existingItem.innerHTML; }
		else { return; }

		bar.setAttribute( 'data-np-marquee-ready', '1' );
		bar.classList.add( 'np-marquee-bar' );

		var gap = parseInt( bar.getAttribute( 'data-marquee-gap' ), 10 );
		if ( isNaN( gap ) ) { gap = 50; }

		// Reduced motion → leave the single message in place, no animation.
		if ( reduceMotion ) {
			bar.classList.add( 'np-marquee-bar--static' );
			return;
		}

		var speed = parseInt( bar.getAttribute( 'data-marquee-speed' ), 10 );
		if ( isNaN( speed ) || speed < 10 ) { speed = 60; }
		var dir   = bar.getAttribute( 'data-marquee-dir' ) === 'right' ? 'right' : 'left';
		var pause = bar.getAttribute( 'data-marquee-pause' ) !== '0';

		// Scaffold: .np-marquee > .np-marquee__track > N × .np-marquee__item
		var marquee = document.createElement( 'div' );
		marquee.className = 'np-marquee';
		if ( pause ) { marquee.classList.add( 'np-marquee--pause-hover' ); }

		var track = document.createElement( 'div' );
		track.className = 'np-marquee__track';
		marquee.appendChild( track );

		function makeItem( hidden ) {
			var item = document.createElement( 'span' );
			item.className = 'np-marquee__item';
			item.style.paddingRight = gap + 'px';
			item.innerHTML = htmlStr;
			if ( hidden ) { item.setAttribute( 'aria-hidden', 'true' ); }
			return item;
		}

		// First real item measures the set width; clones fill the viewport + 1.
		var first = makeItem( false );
		track.appendChild( first );
		content.innerHTML = '';
		content.appendChild( marquee );

		var itemWidth = first.getBoundingClientRect().width;
		if ( itemWidth <= 0 ) { itemWidth = content.getBoundingClientRect().width || 300; }

		var containerWidth = content.getBoundingClientRect().width || itemWidth;
		var copies = Math.ceil( containerWidth / itemWidth ) + 1;
		for ( var i = 0; i < copies; i++ ) {
			track.appendChild( makeItem( true ) );
		}

		// Shift exactly one item width → arrangement repeats seamlessly.
		track.style.setProperty( '--np-mq-shift', '-' + itemWidth + 'px' );
		track.style.animationDuration = ( itemWidth / speed ).toFixed( 2 ) + 's';
		track.style.animationDirection = ( 'right' === dir ) ? 'reverse' : 'normal';
	}

	function initMarquees() {
		var bars = document.querySelectorAll( '.np-bar[data-marquee="1"]' );
		Array.prototype.forEach.call( bars, buildMarquee );
	}

	// Preferred: run after core has placed the bars in the DOM.
	if ( window.NoticePulse && typeof window.NoticePulse.on === 'function' ) {
		window.NoticePulse.on( 'np:bars-rendered', initMarquees );
	}

	// Fallback so a marquee still builds if the event was missed or core is absent.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initMarquees );
	} else {
		initMarquees();
	}

	// Rebuild on resize (viewport width changes the number of copies needed).
	var rz;
	window.addEventListener( 'resize', function () {
		clearTimeout( rz );
		rz = setTimeout( function () {
			document.querySelectorAll( '.np-bar[data-marquee="1"]' ).forEach( function ( bar ) {
				bar.removeAttribute( 'data-np-marquee-ready' );
			} );
			initMarquees();
		}, 200 );
	} );

} )();
