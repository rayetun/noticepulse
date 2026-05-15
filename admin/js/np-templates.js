/**
 * NoticePulse — Template Library JS
 *
 * Root cause of previous "Could not load template" error:
 *   The AJAX handler (noticepulse_get_template) returns:
 *     { id, name, category, emoji, preview, fields: { bar_type, message, … , bar_meta: {…} } }
 *   The old fillForm() was reading data.bar_type directly — one level too shallow.
 *   Fix: read from data.fields and data.fields.bar_meta.
 *
 * Field name mapping (exact names from edit-bar.php):
 *
 *   STANDARD   : message, cta_label, cta_url, bg_color, text_color,
 *                btn_bg_color, btn_txt_color
 *   GDPR meta  : gdpr_accept_label, gdpr_decline_label, gdpr_policy_label,
 *                gdpr_cookie_days
 *   COUNTDOWN  : countdown_end, countdown_label_days/hours/mins/secs
 *   EMAIL      : email_placeholder, email_btn_label, email_success_msg
 *   COUPON     : coupon_code, coupon_btn_label, coupon_success_label
 *   CALL       : call_phone, call_btn_label
 *
 * @package NoticePulse
 * @since   2.1.0
 */
/* global jQuery, noticepulseAdmin */

( function ( $ ) {
	'use strict';

	// ── State ─────────────────────────────────────────────────────────────────
	var pendingTemplateId    = null;
	var pendingTemplateLabel = null;

	// ── DOM refs ──────────────────────────────────────────────────────────────
	var $overlay       = null;
	var $modal         = null;
	var $grid          = null;
	var $empty         = null;
	var $confirm       = null;
	var $confirmName   = null;
	var $confirmOk     = null;
	var $confirmCancel = null;

	// ── Init ──────────────────────────────────────────────────────────────────

	function init() {
		$overlay       = $( '#np-template-overlay' );
		$modal         = $overlay.find( '.np-tmpl-modal' );
		$grid          = $( '#np-tmpl-grid' );
		$empty         = $( '#np-tmpl-empty' );
		$confirm       = $( '#np-tmpl-confirm' );
		$confirmName   = $( '#np-tmpl-confirm-name' );
		$confirmOk     = $( '#np-tmpl-confirm-ok' );
		$confirmCancel = $( '#np-tmpl-confirm-cancel' );

		if ( ! $overlay.length ) { return; }
		bindEvents();
	}

	// ── Events ────────────────────────────────────────────────────────────────

	function bindEvents() {
		$( document ).on( 'click', '#np-browse-templates', openModal );
		$( '#np-tmpl-close' ).on( 'click', closeModal );
		$overlay.on( 'click', function ( e ) {
			if ( $( e.target ).is( $overlay ) ) { closeModal(); }
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( 27 !== e.which ) { return; }
			if ( $confirm.is( ':visible' ) ) { dismissConfirm(); }
			else if ( $overlay.is( ':visible' ) ) { closeModal(); }
		} );
		$( '#np-tmpl-cats' ).on( 'click', '.np-tmpl-cat', function () {
			$( '#np-tmpl-cats .np-tmpl-cat' ).removeClass( 'np-tmpl-cat--active' );
			$( this ).addClass( 'np-tmpl-cat--active' );
			filterByCategory( $( this ).data( 'cat' ) );
		} );
		$grid.on( 'click', '.np-tmpl-apply-btn', function () {
			pendingTemplateId    = $( this ).data( 'id' );
			pendingTemplateLabel = $( this ).data( 'label' );
			showConfirm( pendingTemplateLabel );
		} );
		$confirmOk.on( 'click', function () {
			var id = pendingTemplateId;
			dismissConfirm();
			closeModal();
			applyTemplate( id );
		} );
		$confirmCancel.on( 'click', dismissConfirm );
	}

	// ── Modal ─────────────────────────────────────────────────────────────────

	function openModal() {
		$overlay.fadeIn( 180 );
		$( 'body' ).addClass( 'np-tmpl-open' );
		$( '#np-tmpl-cats .np-tmpl-cat' ).removeClass( 'np-tmpl-cat--active' );
		$( '#np-tmpl-cats .np-tmpl-cat[data-cat="all"]' ).addClass( 'np-tmpl-cat--active' );
		filterByCategory( 'all' );
		$modal.scrollTop( 0 );
	}

	function closeModal() {
		$overlay.fadeOut( 150 );
		$( 'body' ).removeClass( 'np-tmpl-open' );
		dismissConfirm();
	}

	// ── Category filter ───────────────────────────────────────────────────────

	function filterByCategory( cat ) {
		var $cards  = $grid.find( '.np-tmpl-card' );
		var visible = 0;
		$cards.each( function () {
			var show = ( 'all' === cat ) || ( $( this ).data( 'cat' ) === cat );
			$( this ).toggle( show );
			if ( show ) { visible++; }
		} );
		$empty.toggle( visible === 0 );
		$grid.toggle( visible > 0 );
	}

	// ── Confirm ───────────────────────────────────────────────────────────────

	function showConfirm( label ) {
		$confirmName.text( '"' + label + '"' );
		$confirm.fadeIn( 150 );
	}

	function dismissConfirm() {
		$confirm.fadeOut( 120 );
		pendingTemplateId    = null;
		pendingTemplateLabel = null;
	}

	// ── AJAX ──────────────────────────────────────────────────────────────────

	function applyTemplate( templateId ) {
		if ( ! templateId ) { return; }

		var $btn  = $( '#np-browse-templates' );
		var orig  = $btn.html();
		$btn.html( '⏳ Applying…' ).prop( 'disabled', true );

		$.ajax( {
			url    : noticepulseAdmin.ajaxUrl,
			method : 'POST',
			data   : {
				action      : 'noticepulse_get_template',
				nonce       : noticepulseAdmin.nonce,
				template_id : templateId,
			},
			success : function ( response ) {
				if ( response && response.success && response.data ) {
					fillForm( response.data );
					showToast( '✅ Template applied! Adjust settings then save.', 'success' );
				} else {
					showToast( '❌ Template not found. Please try again.', 'error' );
				}
			},
			error : function () {
				showToast( '❌ Network error. Please try again.', 'error' );
			},
			complete : function () {
				$btn.html( orig ).prop( 'disabled', false );
			},
		} );
	}

	// ── Form fill ─────────────────────────────────────────────────────────────

	/**
	 * Pre-fill the edit-bar form from a template object.
	 *
	 * AJAX response shape (from class-np-templates.php wp_send_json_success):
	 * response.data = {
	 *   id, name, category, emoji, preview,
	 *   fields: {
	 *     bar_type, message, cta_label, cta_url,
	 *     bg_color, text_color, btn_bg_color, btn_txt_color,
	 *     btn_radius, bar_padding, font_size,
	 *     bar_meta: {
	 *       gdpr:      { accept_label, decline_label, policy_label, cookie_days },
	 *       countdown: { label_days, label_hours, label_mins, label_secs, hide_on_expire },
	 *       email:     { placeholder, btn_label, success_msg, provider },
	 *       coupon:    { code, btn_label, success_label },
	 *       call:      { btn_label, mobile_only }
	 *     }
	 *   }
	 * }
	 *
	 * @param {Object} data  response.data from AJAX.
	 */
	function fillForm( data ) {
		// ── Unpack — fields are ONE level deep inside data.fields ────────────
		var fields  = data.fields  || {};
		var barMeta = fields.bar_meta || {};

		// ── 1. Bar type — click the .np-type-btn label so existing JS fires ──
		if ( fields.bar_type ) {
			var $label = $( '.np-type-btn[data-type="' + fields.bar_type + '"]' );
			if ( $label.length ) {
				$label.find( '.np-type-radio' ).prop( 'checked', true );
				$label.trigger( 'click' );
			}
		}

		// ── 2. Shared text fields ─────────────────────────────────────────────
		setField( '#np-message',   fields.message   );
		setField( '#np-cta-label', fields.cta_label );
		setField( '#np-cta-url',   fields.cta_url   );

		// ── 3. Colour pickers ─────────────────────────────────────────────────
		setColor( 'bg_color',      fields.bg_color      );
		setColor( 'text_color',    fields.text_color    );
		setColor( 'btn_bg_color',  fields.btn_bg_color  );
		setColor( 'btn_txt_color', fields.btn_txt_color );

		// ── 4. Design options (if present in template) ────────────────────────
		setSelectField( 'select[name="btn_radius"]',  fields.btn_radius  );
		setSelectField( 'select[name="bar_padding"]', fields.bar_padding );
		setSelectField( 'select[name="font_size"]',   fields.font_size   );

		// ── 5. GDPR bar_meta fields ───────────────────────────────────────────
		if ( barMeta.gdpr ) {
			setField( 'input[name="gdpr_accept_label"]',  barMeta.gdpr.accept_label  );
			setField( 'input[name="gdpr_decline_label"]', barMeta.gdpr.decline_label );
			setField( 'input[name="gdpr_policy_label"]',  barMeta.gdpr.policy_label  );
			setField( 'input[name="gdpr_cookie_days"]',   barMeta.gdpr.cookie_days   );
		}

		// ── 6. Countdown bar_meta fields ─────────────────────────────────────
		if ( barMeta.countdown ) {
			// End date not set by template — user must set their real date.
			// Label overrides are applied.
			setField( 'input[name="countdown_label_days"]',  barMeta.countdown.label_days  );
			setField( 'input[name="countdown_label_hours"]', barMeta.countdown.label_hours );
			setField( 'input[name="countdown_label_mins"]',  barMeta.countdown.label_mins  );
			setField( 'input[name="countdown_label_secs"]',  barMeta.countdown.label_secs  );
		}

		// ── 7. Email capture bar_meta fields ─────────────────────────────────
		if ( barMeta.email ) {
			setField( 'input[name="email_placeholder"]',  barMeta.email.placeholder );
			setField( 'input[name="email_btn_label"]',    barMeta.email.btn_label   );
			setField( 'input[name="email_success_msg"]',  barMeta.email.success_msg );
		}

		// ── 8. Coupon copy bar_meta fields ────────────────────────────────────
		if ( barMeta.coupon ) {
			setField( 'input[name="coupon_code"]',          barMeta.coupon.code          );
			setField( 'input[name="coupon_btn_label"]',     barMeta.coupon.btn_label     );
			setField( 'input[name="coupon_success_label"]', barMeta.coupon.success_label );
		}

		// ── 9. Click-to-call bar_meta fields ─────────────────────────────────
		if ( barMeta.call ) {
			// phone not in templates — user enters their own number.
			setField( 'input[name="call_btn_label"]', barMeta.call.btn_label );
		}

		// ── 10. Text Carousel bar_meta fields ────────────────────────────────
		if ( barMeta.ticker && barMeta.ticker.messages ) {
			rebuildTickerMessages(
				'#np-ticker-items',
				'#np-ticker-json',
				barMeta.ticker.messages
			);
			setSelectField( 'input[name="ticker_speed"]',      barMeta.ticker.speed      );
			setSelectField( 'input[name="ticker_transition"]', barMeta.ticker.transition  );
		}

		// ── 11. Switch to Content tab so user sees the pre-filled fields ──────
		var $contentTab = $( '.np-tab-btn[data-tab="content"]' );
		if ( $contentTab.length && ! $contentTab.hasClass( 'np-tab-btn--active' ) ) {
			$contentTab.trigger( 'click' );
		}

		// ── 12. Scroll to form top ────────────────────────────────────────────
		var $form = $( '#np-bar-form' );
		if ( $form.length ) {
			$( 'html, body' ).animate( { scrollTop: $form.offset().top - 60 }, 280 );
		}
	}

	/**
	 * Set a text / textarea / number / url / tel / datetime-local field.
	 * Silently skipped if value is undefined/null or element not found.
	 */
	function setField( selector, value ) {
		if ( value === undefined || value === null ) { return; }
		var $el = $( selector );
		if ( ! $el.length ) { return; }
		$el.val( value ).trigger( 'input' ).trigger( 'change' );
	}

	/**
	 * Set a <select> field.
	 * Silently skipped if value is undefined/null or option doesn't exist.
	 */
	function setSelectField( selector, value ) {
		if ( ! value ) { return; }
		var $el = $( selector );
		if ( ! $el.length ) { return; }
		// Only set if the option actually exists.
		if ( $el.find( 'option[value="' + value + '"]' ).length ) {
			$el.val( value ).trigger( 'change' );
		}
	}

	/**
	 * Set a WordPress wpColorPicker field.
	 * Updates the input value AND the iris picker widget.
	 *
	 * @param {string} fieldName  The input's name attribute (e.g. 'bg_color').
	 * @param {string} color      Hex colour string (e.g. '#7c5cfc').
	 */
	function setColor( fieldName, color ) {
		if ( ! color ) { return; }
		var $input = $( 'input[name="' + fieldName + '"]' );
		if ( ! $input.length ) { return; }

		$input.val( color );

		// Update the colour picker widget (iris / wpColorPicker).
		try {
			if ( $.fn.wpColorPicker && $input.data( 'wpWpColorPicker' ) ) {
				$input.wpColorPicker( 'color', color );
			} else if ( $.fn.iris && $input.data( 'iris' ) ) {
				$input.iris( 'color', color );
			}
		} catch ( e ) {
			// Picker not yet initialised — raw value will be read on focus.
		}

		$input.trigger( 'change' );
	}

	/**
	 * Rebuild a ticker/carousel message list from template data.
	 * Works for Text Carousel (#np-ticker-items)
	 *
	 * @param {string} containerSel  Selector for the rows container.
	 * @param {string} jsonFieldSel  Selector for the hidden JSON field.
	 * @param {Array}  messages      Array of {text, cta_label, cta_url} objects.
	 */
	function rebuildTickerMessages( containerSel, jsonFieldSel, messages ) {
		if ( ! Array.isArray( messages ) || ! messages.length ) { return; }

		var $container = $( containerSel );
		var $json      = $( jsonFieldSel );
		if ( ! $container.length ) { return; }

		// Determine which class prefix to use based on container.
		var isNews    = containerSel.indexOf( 'news' ) !== -1;
		var textClass = isNews ? 'np-news-text'      : 'np-ticker-text';
		var ctaLabel  = isNews ? 'np-news-cta-label' : 'np-ticker-cta-label';
		var ctaUrl    = isNews ? 'np-news-cta-url'   : 'np-ticker-cta-url';
		var removeBtn = isNews ? 'np-news-remove'    : 'np-ticker-remove';

		$container.empty();

		$.each( messages, function ( i, msg ) {
			if ( typeof msg === 'string' ) {
				msg = { text: msg, cta_label: '', cta_url: '' };
			}
			var $row = $(
				'<div class="np-ticker-item" data-index="' + i + '">' +
					'<span class="np-ticker-drag">⠿</span>' +
					'<div class="np-ticker-fields">' +
						'<input type="text" class="' + textClass + ' large-text" placeholder="Message text…" value="">' +
						'<div class="np-ticker-cta">' +
							'<input type="text" class="' + ctaLabel + '" placeholder="CTA Label (optional)" value="">' +
							'<input type="url"  class="' + ctaUrl   + '" placeholder="https://" value="">' +
						'</div>' +
					'</div>' +
					'<button type="button" class="' + removeBtn + '">✕</button>' +
				'</div>'
			);
			$row.find( '.' + textClass ).val( msg.text      || '' );
			$row.find( '.' + ctaLabel  ).val( msg.cta_label || '' );
			$row.find( '.' + ctaUrl    ).val( msg.cta_url   || '' );
			$container.append( $row );
		} );

		// Update hidden JSON field.
		if ( $json.length ) {
			$json.val( JSON.stringify( messages ) );
		}

		// Hide "no messages" placeholder.
		$container.closest( '.np-panel-section' ).find( '.np-ticker-empty' ).hide();
	}

	// ── Toasts ────────────────────────────────────────────────────────────────

	function showToast( message, type ) {
		var $toast = $( '<div class="np-tmpl-toast np-tmpl-toast--' + type + '">' +
			escHtml( message ) + '</div>' );
		$( 'body' ).append( $toast );
		setTimeout( function () { $toast.addClass( 'np-tmpl-toast--visible' ); }, 10 );
		setTimeout( function () {
			$toast.removeClass( 'np-tmpl-toast--visible' );
			setTimeout( function () { $toast.remove(); }, 320 );
		}, 3500 );
	}

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;'  )
			.replace( />/g, '&gt;'  );
	}

	// ── Boot ──────────────────────────────────────────────────────────────────
	$( document ).ready( init );

}( jQuery ) );
