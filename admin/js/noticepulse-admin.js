/**
 * NoticePulse Admin JS v2.0.0
 *
 * Handles: tab switching, live preview, confirm dialogs,
 * visibility toggle, color pickers, form validation.
 *
 * Fix v2.0.0: initColorPickers() now uses .each() and passes
 * defaultColor per-input from data-default-color attribute.
 * Without defaultColor, WordPress iris initialises the swatch
 * as blank ("Select Color") even when the input has a value.
 *
 * @package NoticePulse
 * @since   2.0.0
 */
/* global noticepulseAdmin, jQuery */

( function ( $ ) {
	'use strict';

	// ── Color pickers ──────────────────────────────────────────────────────────
	//
	// FIX: Previously called wpColorPicker() on all inputs at once without
	// passing defaultColor. WordPress iris uses defaultColor to initialise
	// the colored swatch button. Without it, the button shows "Select Color"
	// with no swatch background regardless of the input's value.
	//
	// Fix: iterate each input individually so we can read its own
	// data-default-color attribute and pass it as defaultColor.
	//
	function initColorPickers() {
		$( '.np-color-picker' ).each( function () {
			var $input       = $( this );
			var defaultColor = $input.data( 'defaultColor' ) || '#ffffff';

			$input.wpColorPicker( {
				defaultColor : defaultColor,
				change       : function () { setTimeout( updatePreview, 50 ); },
				clear        : function () { setTimeout( updatePreview, 50 ); },
			} );
		} );
	}

	// ── Live preview ───────────────────────────────────────────────────────────
	function updatePreview() {
		var $preview = $( '#np-live-preview' );
		if ( ! $preview.length ) { return; }

		var bgColor     = $( '[name="bg_color"]' ).val()     || '#1a73e8';
		var textColor   = $( '[name="text_color"]' ).val()   || '#ffffff';
		var btnBgColor  = $( '[name="btn_bg_color"]' ).val() || '#ffffff';
		var btnTxtColor = $( '[name="btn_txt_color"]' ).val()|| '#1a73e8';
		var message     = $( '#np-message' ).val()           || 'Your bar message appears here.';
		var ctaLabel    = $( '[name="cta_label"]' ).val()    || '';
		var btnRadius   = $( '#np-btn-radius' ).val()        || 'rounded';
		var gradEnabled = $( '#np-gradient-toggle' ).is( ':checked' );
		var gradC1      = $( '[name="gradient_color1"]' ).val() || '#6d28d9';
		var gradC2      = $( '[name="gradient_color2"]' ).val() || '#4f46e5';
		var gradAngle   = $( '#np-gradient-angle' ).val()    || 135;
		var gradType    = $( '[name="gradient_type"]:checked' ).val() || 'linear';
		var position    = $( '[name="position"]:checked' ).val() || 'top';

		var radiusMap = { sharp: '0px', rounded: '4px', pill: '50px' };
		var radius    = radiusMap[ btnRadius ] || '4px';
		var plainMsg  = message.replace( /<[^>]+>/g, '' );

		// Background.
		if ( gradEnabled ) {
			var bg = 'radial' === gradType
				? 'radial-gradient(circle,' + gradC1 + ',' + gradC2 + ')'
				: 'linear-gradient(' + gradAngle + 'deg,' + gradC1 + ',' + gradC2 + ')';
			$preview.css( { 'background': bg, 'background-color': '', 'color': textColor } );
		} else {
			$preview.css( { 'background': '', 'background-color': bgColor, 'color': textColor } );
		}

		$( '#np-preview-message' ).text( plainMsg );
		$( '.np-preview-bar__close' ).css( 'color', textColor );
		$( '#np-preview-pos' ).text( 'bottom' === position ? '↓ Bottom' : '↑ Top' );

		if ( ctaLabel ) {
			$( '#np-preview-cta' ).text( ctaLabel ).css( {
				'background-color': btnBgColor,
				'color':            btnTxtColor,
				'border-radius':    radius,
				'display':          'inline-block',
			} );
		} else {
			$( '#np-preview-cta' ).hide();
		}
	}

	// ── Tab switching ──────────────────────────────────────────────────────────
	function initTabs() {
		$( document ).on( 'click', '.np-tab-btn', function () {
			var tab = $( this ).data( 'tab' );

			$( '.np-tab-btn' ).removeClass( 'np-tab-btn--active' );
			$( this ).addClass( 'np-tab-btn--active' );

			$( '.np-tab-panel' ).removeClass( 'np-tab-panel--active' );
			$( '#np-tab-' + tab ).addClass( 'np-tab-panel--active' );
		} );
	}

	// ── Visibility toggle ──────────────────────────────────────────────────────
	function initVisibilityToggle() {
		$( '[name="visibility"]' ).on( 'change', function () {
			if ( 'specific' === $( this ).val() ) {
				$( '.np-specific-pages' ).slideDown( 200 );
			} else {
				$( '.np-specific-pages' ).slideUp( 200 );
			}
		} );
	}

	// ── Confirm dialogs ────────────────────────────────────────────────────────
	function initConfirmDialogs() {
		$( document ).on( 'click', '.np-confirm-delete', function ( e ) {
			if ( ! window.confirm( noticepulseAdmin.confirmDelete ) ) {
				e.preventDefault(); return false;
			}
		} );

		$( document ).on( 'click', '.np-confirm-reset', function ( e ) {
			if ( ! window.confirm( noticepulseAdmin.confirmResetStats ) ) {
				e.preventDefault(); return false;
			}
		} );
	}

	// ── Form validation ────────────────────────────────────────────────────────
	function initFormValidation() {
		$( '#np-bar-form' ).on( 'submit', function ( e ) {
			var name = $.trim( $( '#np-name' ).val() );
			if ( ! name ) {
				e.preventDefault();
				$( '#np-name' ).addClass( 'np-field-error' ).focus();

				$( '.np-tab-btn[data-tab="content"]' ).trigger( 'click' );

				setTimeout( function () {
					$( '#np-name' ).addClass( 'np-field-error' ).focus();
				}, 50 );

				return false;
			}
		} );

		$( '#np-name' ).on( 'input', function () {
			$( this ).removeClass( 'np-field-error' );
		} );
	}

	// ── Live preview listeners ──────────────────────────────────────────────────
	function initPreviewListeners() {
		$( '#np-message, [name="cta_label"]' ).on( 'input', updatePreview );
		$( '#np-btn-radius' ).on( 'change', updatePreview );
		$( '[name="position"]' ).on( 'change', updatePreview );
		$( '#np-gradient-toggle, [name="gradient_type"]' ).on( 'change', updatePreview );
		$( '#np-gradient-angle' ).on( 'input', function () {
			$( '#np-gradient-angle-val' ).text( $( this ).val() );
			updateGradientPreview();
			updatePreview();
		} );
		$( '[name="gradient_color1"], [name="gradient_color2"]' ).on( 'change', function () {
			updateGradientPreview();
			updatePreview();
		} );
		$( '#np-gradient-toggle' ).on( 'change', function () {
			$( '#np-gradient-fields' ).toggle( $( this ).is( ':checked' ) );
			updatePreview();
		} );
	}

	function updateGradientPreview() {
		var c1    = $( '[name="gradient_color1"]' ).val() || '#6d28d9';
		var c2    = $( '[name="gradient_color2"]' ).val() || '#4f46e5';
		var angle = $( '#np-gradient-angle' ).val() || 135;
		var type  = $( '[name="gradient_type"]:checked' ).val() || 'linear';

		var bg = 'radial' === type
			? 'radial-gradient(circle,' + c1 + ',' + c2 + ')'
			: 'linear-gradient(' + angle + 'deg,' + c1 + ',' + c2 + ')';

		$( '#np-gradient-preview' ).css( 'background', bg );
		$( '#np-gradient-angle-row' ).toggle( 'radial' !== type );
	}

	// ── Reset all confirm ──────────────────────────────────────────────────────
	function initResetAll() {
		var resetAll = document.querySelector( '.np-confirm-reset-all' );
		if ( resetAll ) {
			resetAll.addEventListener( 'click', function ( e ) {
				if ( ! window.confirm( 'This permanently deletes ALL bars and analytics. Cannot be undone. Proceed?' ) ) {
					e.preventDefault();
				}
			} );
		}
	}

	// ── Boot ───────────────────────────────────────────────────────────────────
	$( function () {
		initColorPickers();
		initTabs();
		initVisibilityToggle();
		initConfirmDialogs();
		initPreviewListeners();
		initFormValidation();
		initResetAll();
		updatePreview();
		updateGradientPreview();
	} );

} )( jQuery );