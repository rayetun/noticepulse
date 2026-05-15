/**
 * NoticePulse — Click-to-Call Frontend
 *
 * Reads data-phone-* attributes from the bar element.
 * Replaces the default CTA button with a styled phone button
 * that opens the dialler on mobile (tel: link).
 *
 * @package NoticePulse
 * @since   2.1.0
 */
( function () {
	'use strict';

	function initClickToCall( bar ) {
		var phone      = bar.getAttribute( 'data-phone' )             || '';
		var tel        = bar.getAttribute( 'data-phone-tel' )         || '';
		var btnLabel   = bar.getAttribute( 'data-phone-btn' )         || '📞 Call Us';
		var mobileOnly = bar.getAttribute( 'data-phone-mobile-only' ) === '1';
		var hours      = bar.getAttribute( 'data-phone-hours' )       || '';

		if ( ! phone ) { return; }

		// Mobile-only: hide button on desktop.
		if ( mobileOnly && window.innerWidth > 768 ) { return; }

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Remove default CTA if present.
		var defaultCta = bar.querySelector( '.np-bar__cta' );
		if ( defaultCta ) { defaultCta.remove(); }

		// Build call button.
		var callLink       = document.createElement( 'a' );
		callLink.href      = 'tel:' + tel;
		callLink.className = 'np-call-btn';
		callLink.setAttribute( 'aria-label', 'Call ' + phone );
		callLink.textContent = btnLabel;

		content.appendChild( callLink );

		// Hours of operation (shown next to button).
		if ( hours ) {
			var hoursEl       = document.createElement( 'span' );
			hoursEl.className = 'np-call-hours';
			hoursEl.textContent = hours;
			content.appendChild( hoursEl );
		}
	}

	function init() {
		document.querySelectorAll( '.np-bar[data-phone]' ).forEach( initClickToCall );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
