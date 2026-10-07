/**
 * NoticePulse — AJAX Save (no page reload).
 *
 * Intercepts the edit-bar form submit and saves via admin-ajax instead of a
 * full-page POST + redirect. Shows an inline toast and, for a brand-new bar,
 * transitions the screen to "edit" mode (sets bar_id, rewrites the URL, swaps
 * the button/title labels) without navigating away.
 *
 * Progressive enhancement: if this script does not run, the form still posts
 * normally to admin-post.php (handle_save_bar) — nothing breaks without JS.
 *
 * Runs AFTER noticepulse-admin.js, whose submit handler validates the required
 * Bar Name and cancels the submit when it is empty. We respect that: if the
 * event was already default-prevented, we do not fire an AJAX request.
 *
 * @package NoticePulse
 * @since   2.2.0
 */
/* global jQuery, noticepulseAdmin, noticepulseAjaxSave */

( function ( $ ) {
	'use strict';

	var saving = false;

	function strings() {
		var d = ( typeof noticepulseAjaxSave !== 'undefined' ) ? noticepulseAjaxSave : {};
		return {
			saving:    d.saving    || 'Saving…',
			publish:   d.publish   || 'Publish Bar',
			update:    d.update    || 'Update Bar',
			editTitle: d.editTitle || 'Edit Bar',
			network:   d.network   || 'Network error. Please try again.',
			generic:   d.generic   || 'Could not save. Please try again.'
		};
	}

	function init() {
		var $form = $( '#np-bar-form' );
		if ( ! $form.length || typeof noticepulseAdmin === 'undefined' ) { return; }

		$form.on( 'submit', function ( e ) {
			// The validation handler in noticepulse-admin.js cancels the submit
			// when the Bar Name is empty. Honour that — don't AJAX-save.
			if ( e.isDefaultPrevented() ) { return; }
			e.preventDefault();
			saveBar( $form );
		} );
	}

	function saveBar( $form ) {
		if ( saving ) { return; }
		saving = true;

		var s         = strings();
		var $saveBtns = $( '.np-edit-save-btn, .np-publish-btn' );
		$saveBtns.prop( 'disabled', true ).attr( 'aria-busy', 'true' ).text( s.saving );

		// Serialize the form, replacing the classic action with the AJAX action.
		var fields = $form.serializeArray().filter( function ( f ) {
			return 'action' !== f.name;
		} );
		fields.push( { name: 'action', value: 'noticepulse_save_bar_ajax' } );

		$.post( noticepulseAdmin.ajaxUrl, $.param( fields ) )
			.done( function ( resp ) {
				if ( resp && resp.success && resp.data ) {
					handleSuccess( resp.data );
				} else {
					handleError( resp && resp.data ? resp.data : null );
				}
			} )
			.fail( function () {
				toast( s.network, 'error' );
			} )
			.always( function () {
				saving = false;
				var editMode = parseInt( $( 'input[name="bar_id"]' ).val(), 10 ) > 0;
				$saveBtns
					.prop( 'disabled', false )
					.removeAttr( 'aria-busy' )
					.text( editMode ? s.update : s.publish );
			} );
	}

	function handleSuccess( data ) {
		var s = strings();
		toast( data.message || s.update, 'success' );

		if ( data.is_new && data.bar_id ) {
			// Transition new → edit without a reload.
			$( 'input[name="bar_id"]' ).val( data.bar_id );

			if ( data.edit_url && window.history && window.history.replaceState ) {
				window.history.replaceState( {}, '', data.edit_url );
			}

			// Header: title + #id chip.
			$( '.np-edit-title' ).text( s.editTitle );
			if ( ! $( '.np-edit-id' ).length ) {
				$( '<span class="np-edit-id">#' + parseInt( data.bar_id, 10 ) + '</span>' )
					.insertAfter( '.np-edit-title' );
			}
		}
	}

	function handleError( data ) {
		var s   = strings();
		var msg = ( data && data.message ) ? data.message : s.generic;
		toast( msg, 'error' );

		if ( data && 'missing_name' === data.notice ) {
			$( '.np-tab-btn[data-tab="content"]' ).trigger( 'click' );
			$( '#np-name' ).addClass( 'np-field-error' ).trigger( 'focus' );
		}
	}

	// ── Toast — reuses .np-tmpl-toast styling loaded on this screen ──────────────
	function toast( message, type ) {
		var $t = $( '<div class="np-tmpl-toast np-tmpl-toast--' + ( 'error' === type ? 'error' : 'success' ) + '"></div>' )
			.text( message );
		$( 'body' ).append( $t );
		setTimeout( function () { $t.addClass( 'np-tmpl-toast--visible' ); }, 10 );
		setTimeout( function () {
			$t.removeClass( 'np-tmpl-toast--visible' );
			setTimeout( function () { $t.remove(); }, 320 );
		}, 3200 );
	}

	$( document ).ready( init );

}( jQuery ) );
