/**
 * NoticePulse — GDPR Cookie Consent Bar Frontend
 *
 * Reads data-gdpr attributes from the bar element and:
 *  - Checks whether the visitor has already made a consent choice.
 *    If yes, removes the bar immediately (no flash).
 *  - Replaces the standard CTA button with Accept + Decline buttons.
 *  - Adds a Privacy Policy link.
 *  - Sets a long-lived browser cookie on Accept or Decline.
 *  - Fires custom DOM events for third-party integrations:
 *      noticepulse:gdpr:accepted
 *      noticepulse:gdpr:declined
 *
 * Dependencies: noticepulse-public (loaded first by wp_enqueue_script deps).
 *
 * @package NoticePulse
 * @since   2.0.0
 */
( function () {
	'use strict';

	/* ── Cookie helpers ──────────────────────────────────────────────────── */

	function np_getCookie( name ) {
		var match = document.cookie.match(
			new RegExp( '(?:^|;\\s*)' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '=([^;]*)' )
		);
		return match ? decodeURIComponent( match[1] ) : null;
	}

	function np_setCookie( name, value, days ) {
		var exp = new Date();
		exp.setTime( exp.getTime() + days * 864e5 );
		document.cookie =
			name + '=' + encodeURIComponent( value ) +
			'; expires=' + exp.toUTCString() +
			'; path=/; SameSite=Lax';
	}

	/* ── Dismiss with animation ──────────────────────────────────────────── */

	function np_dismiss( bar ) {
		bar.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
		bar.style.opacity    = '0';
		bar.style.transform  = bar.classList.contains( 'np-bar--bottom' )
			? 'translateY(100%)' : 'translateY(-100%)';
		setTimeout( function () {
			if ( bar.parentNode ) {
				bar.parentNode.removeChild( bar );
			}
		}, 350 );
	}

	/* ── Init a single GDPR bar ──────────────────────────────────────────── */

	function np_initGDPR( bar ) {
		var cookieName   = bar.getAttribute( 'data-gdpr-cookie' )        || 'np_gdpr_consent';
		var cookieDays   = parseInt( bar.getAttribute( 'data-gdpr-days' ) || '365', 10 );
		var acceptLabel  = bar.getAttribute( 'data-gdpr-accept' )         || 'Accept All';
		var declineLabel = bar.getAttribute( 'data-gdpr-decline' )        || 'Decline';
		var policyUrl    = bar.getAttribute( 'data-gdpr-policy-url' )     || '';
		var policyLabel  = bar.getAttribute( 'data-gdpr-policy-label' )   || 'Privacy Policy';

		// Already consented? Remove bar silently — no flash.
		if ( np_getCookie( cookieName ) ) {
			if ( bar.parentNode ) {
				bar.parentNode.removeChild( bar );
			}
			return;
		}

		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		// Remove the default CTA button (we replace it with GDPR buttons).
		var defaultCta = bar.querySelector( '.np-bar__cta' );
		if ( defaultCta ) { defaultCta.parentNode.removeChild( defaultCta ); }

		// Hide the standard close button — use Accept / Decline only.
		var closeBtn = bar.querySelector( '.np-bar__close' );
		if ( closeBtn ) { closeBtn.style.display = 'none'; }

		// Build action container.
		var actions = document.createElement( 'div' );
		actions.className = 'np-gdpr-actions';

		// Privacy Policy link.
		if ( policyUrl ) {
			var link       = document.createElement( 'a' );
			link.href      = policyUrl;
			link.target    = '_blank';
			link.rel       = 'noopener noreferrer';
			link.className = 'np-gdpr-policy-link';
			link.textContent = policyLabel;
			actions.appendChild( link );
		}

		// Decline button.
		var declineBtn         = document.createElement( 'button' );
		declineBtn.type        = 'button';
		declineBtn.className   = 'np-gdpr-btn np-gdpr-btn--decline';
		declineBtn.textContent = declineLabel;
		actions.appendChild( declineBtn );

		// Accept button.
		var acceptBtn         = document.createElement( 'button' );
		acceptBtn.type        = 'button';
		acceptBtn.className   = 'np-gdpr-btn np-gdpr-btn--accept';
		acceptBtn.textContent = acceptLabel;
		actions.appendChild( acceptBtn );

		content.appendChild( actions );

		// Accept handler.
		acceptBtn.addEventListener( 'click', function () {
			np_setCookie( cookieName, 'accepted', cookieDays );
			np_dismiss( bar );
			document.dispatchEvent( new CustomEvent( 'noticepulse:gdpr:accepted', {
				detail: { cookieName: cookieName }
			} ) );
		} );

		// Decline handler.
		declineBtn.addEventListener( 'click', function () {
			np_setCookie( cookieName, 'declined', cookieDays );
			np_dismiss( bar );
			document.dispatchEvent( new CustomEvent( 'noticepulse:gdpr:declined', {
				detail: { cookieName: cookieName }
			} ) );
		} );
	}

	/* ── Boot ────────────────────────────────────────────────────────────── */

	function np_init() {
		document.querySelectorAll( '.np-bar[data-gdpr="1"]' ).forEach( np_initGDPR );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', np_init );
	} else {
		np_init();
	}

}() );
