/**
 * NoticePulse — Email Capture Bar Frontend
 *
 * Reads data-email-capture from bar element.
 * Replaces CTA button area with an inline email form.
 * Submits via AJAX, shows success message.
 *
 * FIX: Button colors now read from CSS custom properties
 * (--np-btn-bg, --np-btn-txt) set on the bar element via
 * class-noticepulse-public.php inline style. Previously the
 * injected <style> tag hardcoded rgba(255,255,255,.9)/#1d2327
 * which always overrode the user's custom colors.
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
	 * @param {string}      prop     CSS property name e.g. '--np-btn-bg'.
	 * @param {string}      fallback Fallback value if unset or empty.
	 * @return {string}
	 */
	function getCSSVar( el, prop, fallback ) {
		var val = getComputedStyle( el ).getPropertyValue( prop ).trim();
		return val || fallback;
	}

	function initEmailCapture( bar ) {
		var placeholder = bar.getAttribute( 'data-email-placeholder' ) || 'Enter your email…';
		var btnLabel    = bar.getAttribute( 'data-email-btn' )         || 'Subscribe';
		var successMsg  = bar.getAttribute( 'data-email-success' )     || '🎉 You\'re in!';
		var nonce       = bar.getAttribute( 'data-email-nonce' )       || '';
		var barId       = bar.getAttribute( 'data-bar-id-email' )      || bar.getAttribute( 'data-bar-id' ) || '0';
		var ajaxUrl     = ( window.noticepulseEmailCapture && window.noticepulseEmailCapture.ajaxUrl ) || '';

		// FIX: Read button colors from CSS vars set by PHP on the bar element.
		var btnBg  = getCSSVar( bar, '--np-btn-bg',  'rgba(255,255,255,0.92)' );
		var btnTxt = getCSSVar( bar, '--np-btn-txt', '#1d2327' );

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Remove default CTA button — email form replaces it.
		var defaultCta = bar.querySelector( '.np-bar__cta' );
		if ( defaultCta ) { defaultCta.remove(); }

		// Build form.
		var form = document.createElement( 'div' );
		form.className = 'np-email-form';
		form.innerHTML =
			'<input type="email" class="np-email-input" placeholder="' + placeholder + '" required>' +
			'<button type="button" class="np-email-btn">' + btnLabel + '</button>';

		content.appendChild( form );

		var input  = form.querySelector( '.np-email-input' );
		var button = form.querySelector( '.np-email-btn' );

		// FIX: Apply custom colors directly to the button element via inline style.
		// Inline style on the element overrides any stylesheet rule, so this
		// correctly applies the user's saved colors regardless of CSS specificity.
		button.style.background = btnBg;
		button.style.color      = btnTxt;

		button.addEventListener( 'click', function () {
			var email = input.value.trim();
			if ( ! email || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				input.style.borderColor = '#ef4444';
				input.focus();
				return;
			}

			input.style.borderColor = '';
			button.disabled    = true;
			button.textContent = '…';

			var body = new URLSearchParams();
			body.append( 'action', 'noticepulse_email_subscribe' );
			body.append( 'nonce',  nonce );
			body.append( 'bar_id', barId );
			body.append( 'email',  email );

			fetch( ajaxUrl, {
				method:  'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body:    body.toString(),
			} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.success ) {
					form.innerHTML = '<span class="np-email-success">' + ( data.data && data.data.message ? data.data.message : successMsg ) + '</span>';
				} else {
					button.disabled    = false;
					button.textContent = btnLabel;
					button.style.background = btnBg;
					button.style.color      = btnTxt;
					input.style.borderColor = '#ef4444';
				}
			} )
			.catch( function () {
				button.disabled    = false;
				button.textContent = btnLabel;
				button.style.background = btnBg;
				button.style.color      = btnTxt;
			} );
		} );

		// Submit on Enter.
		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) { button.click(); }
		} );
	}

	// Styles moved to noticepulse-public.css — enqueued via wp_enqueue_style().

	function init() {
		document.querySelectorAll( '.np-bar[data-email-capture="1"]' ).forEach( initEmailCapture );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
