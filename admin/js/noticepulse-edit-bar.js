/**
 * NoticePulse Edit-Bar JS v2.1.2
 *
 * Key fix: single name="message" field shared by all bar types.
 * JS swaps the placeholder and label text when bar type changes.
 *
 * @package NoticePulse
 * @since   2.1.2
 */
( function () {
	'use strict';

	var msgLabels = {
		standard:      'Message',
		gdpr:          'Cookie Notice Message',
		ticker:        'Fallback Message (shown if JS disabled)',
		click_to_call: 'Headline Message',
		countdown:     'Bar Message',
		email_capture: 'Headline Message',
		coupon_copy:   'Bar Message',
	};

	// ── Bar type selector ──────────────────────────────────────────────────────
	function initBarTypeSelector() {
		var selector = document.getElementById( 'np-type-selector' );
		if ( ! selector ) { return; }

		var typeBtns  = selector.querySelectorAll( '.np-type-btn' );
		var msgEl     = document.getElementById( 'np-message' );
		var msgLabelEl= document.getElementById( 'np-message-label' );
		var placeholders = window.noticepulseMsgPlaceholders || {};

		function showBarType( type ) {
			// 1. Active button state.
			typeBtns.forEach( function ( btn ) {
				btn.classList.toggle( 'np-type-btn--active', btn.dataset.type === type );
			} );

			// 2. Show/hide sections with data-show-for.
			document.querySelectorAll( '.np-bt-section' ).forEach( function ( section ) {
				section.style.display = section.getAttribute( 'data-show-for' ) === type ? '' : 'none';
			} );

			// 3. Update message field placeholder and label.
			if ( msgEl && placeholders[ type ] ) {
				msgEl.placeholder = placeholders[ type ];
			}
			if ( msgLabelEl && msgLabels[ type ] ) {
				// Keep the required star.
				var star = msgLabelEl.querySelector( '.np-required' );
				msgLabelEl.textContent = msgLabels[ type ] + ' ';
				if ( star ) { msgLabelEl.appendChild( star ); }
			}
		}

		selector.querySelectorAll( '.np-type-radio' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () { showBarType( this.value ); } );
		} );

		typeBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var radio = btn.querySelector( 'input[type="radio"]' );
				if ( radio ) { radio.checked = true; showBarType( radio.value ); }
			} );
		} );

		var checked = selector.querySelector( '.np-type-radio:checked' );
		showBarType( checked ? checked.value : 'standard' );
	}

	// ── Toggle checkbox → show/hide ───────────────────────────────────────────
	function initToggle( checkboxId, targetId ) {
		var cb = document.getElementById( checkboxId );
		var tg = document.getElementById( targetId );
		if ( ! cb || ! tg ) { return; }
		cb.addEventListener( 'change', function () { tg.style.display = this.checked ? '' : 'none'; } );
	}

	// ── Ticker builder ─────────────────────────────────────────────────────────
	function initTickerBuilder() {
		var addBtn    = document.getElementById( 'np-ticker-add' );
		var itemsEl   = document.getElementById( 'np-ticker-items' );
		var jsonInput = document.getElementById( 'np-ticker-json' );
		var emptyEl   = document.querySelector( '.np-ticker-empty' );
		if ( ! addBtn || ! itemsEl || ! jsonInput ) { return; }

		function esc( s ) {
			return String( s ).replace( /&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		}

		function syncJSON() {
			var msgs = [];
			itemsEl.querySelectorAll( '.np-ticker-item' ).forEach( function ( item ) {
				var t = ( item.querySelector( '.np-ticker-text' )      || {} ).value || '';
				var l = ( item.querySelector( '.np-ticker-cta-label' ) || {} ).value || '';
				var u = ( item.querySelector( '.np-ticker-cta-url' )   || {} ).value || '';
				if ( t.trim() ) { msgs.push( { text: t, cta_label: l, cta_url: u } ); }
			} );
			jsonInput.value = JSON.stringify( msgs );
			if ( emptyEl ) { emptyEl.style.display = msgs.length ? 'none' : ''; }
		}

		function makeItem( t, l, u ) {
			var div = document.createElement( 'div' );
			div.className = 'np-ticker-item';
			div.innerHTML = '<span class="np-ticker-drag">⠿</span><div class="np-ticker-fields"><input type="text" class="np-ticker-text large-text" placeholder="Message text…" value="' + esc(t) + '"><div class="np-ticker-cta"><input type="text" class="np-ticker-cta-label" placeholder="CTA Label" value="' + esc(l) + '"><input type="url" class="np-ticker-cta-url" placeholder="https://" value="' + esc(u) + '"></div></div><button type="button" class="np-ticker-remove">✕</button>';
			return div;
		}

		function bindItem( item ) {
			var rb = item.querySelector( '.np-ticker-remove' );
			if ( rb ) { rb.addEventListener( 'click', function () { item.remove(); syncJSON(); } ); }
			item.querySelectorAll( 'input' ).forEach( function ( i ) { i.addEventListener( 'input', syncJSON ); } );
		}

		itemsEl.querySelectorAll( '.np-ticker-item' ).forEach( bindItem );
		addBtn.addEventListener( 'click', function () {
			var item = makeItem( '', '', '' );
			itemsEl.appendChild( item );
			bindItem( item );
			var fi = item.querySelector( '.np-ticker-text' );
			if ( fi ) { fi.focus(); }
			if ( emptyEl ) { emptyEl.style.display = 'none'; }
		} );
	}

	// ── Frequency selector ─────────────────────────────────────────────────────
	function initFrequencySelector() {
		var radios = document.querySelectorAll( '[name="frequency_type"]' );
		var nRow   = document.getElementById( 'np-freq-n-row' );
		var items  = document.querySelectorAll( '.np-freq-item' );
		radios.forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				if ( nRow ) { nRow.style.display = this.value === 'pageviews' ? '' : 'none'; }
				items.forEach( function ( item ) {
					var ir = item.querySelector( 'input[type="radio"]' );
					item.classList.toggle( 'np-freq-item--active', ir && ir.checked );
				} );
			} );
		} );
	}

	// ── Trigger selector ───────────────────────────────────────────────────────
	function initTriggerSelector() {
		var radios    = document.querySelectorAll( '[name="trigger_type"]' );
		var delayRow  = document.getElementById( 'np-trigger-delay-row' );
		var scrollRow = document.getElementById( 'np-trigger-scroll-row' );
		var cards     = document.querySelectorAll( '.np-trigger-card' );
		radios.forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				if ( delayRow  ) { delayRow.style.display  = this.value === 'delay'  ? '' : 'none'; }
				if ( scrollRow ) { scrollRow.style.display = this.value === 'scroll' ? '' : 'none'; }
				cards.forEach( function ( card ) {
					var ir = card.querySelector( 'input[type="radio"]' );
					card.classList.toggle( 'np-trigger-card--active', ir && ir.checked );
				} );
			} );
		} );
	}

	// ── Animation selector ─────────────────────────────────────────────────────
	function initAnimationSelector() {
		var radios   = document.querySelectorAll( '[name="animation_type"]' );
		var speedRow = document.getElementById( 'np-anim-speed-row' );
		var cards    = document.querySelectorAll( '.np-anim-card' );
		radios.forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				if ( speedRow ) { speedRow.style.display = this.value === 'none' ? 'none' : ''; }
				cards.forEach( function ( card ) {
					var ir = card.querySelector( 'input[type="radio"]' );
					card.classList.toggle( 'np-anim-card--active', ir && ir.checked );
				} );
			} );
		} );
	}

	// ── Email provider ─────────────────────────────────────────────────────────
	function initEmailProvider() {
		var sel     = document.getElementById( 'np-email-provider' );
		var apiRow  = document.getElementById( 'np-email-api-key-row' );
		var listRow = document.getElementById( 'np-email-list-id-row' );
		if ( ! sel ) { return; }
		sel.addEventListener( 'change', function () {
			var show = this.value !== 'none';
			if ( apiRow  ) { apiRow.style.display  = show ? '' : 'none'; }
			if ( listRow ) { listRow.style.display = show ? '' : 'none'; }
		} );
	}

	// ── Gradient type toggle ───────────────────────────────────────────────────
	function initGradient() {
		document.querySelectorAll( '[name="gradient_type"]' ).forEach( function ( r ) {
			r.addEventListener( 'change', function () {
				var row = document.getElementById( 'np-gradient-angle-row' );
				if ( row ) { row.style.display = this.value === 'radial' ? 'none' : ''; }
			} );
		} );
	}

	// ── Boot ──────────────────────────────────────────────────────────────────
	function init() {
		initBarTypeSelector();
		initToggle( 'np-gradient-toggle', 'np-gradient-fields' );
		initToggle( 'np-ab-toggle',       'np-ab-fields' );
		initToggle( 'np-geo-toggle',      'np-geo-fields' );
		initTickerBuilder();
		initFrequencySelector();
		initTriggerSelector();
		initAnimationSelector();
		initEmailProvider();
		initGradient();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
