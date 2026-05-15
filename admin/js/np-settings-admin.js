/**
 * NoticePulse — Settings Page JS
 *
 * Handles the file drop zone on the Settings & Tools page.
 * Enables the Import Bars button when a file is chosen via
 * click-to-browse OR drag-and-drop.
 *
 * FIX: The previous version relied on fileInput.dispatchEvent('change')
 * after drag-drop, but some browsers silently ignore programmatic
 * assignment to fileInput.files and never fire the change handler.
 * The button is now enabled directly inside the drop handler so
 * drag-and-drop always works regardless of browser quirks.
 *
 * @package NoticePulse
 * @since   2.1.3
 */
/* global document */
( function () {
	'use strict';

	var dropzone  = document.getElementById( 'np-dropzone' );
	var fileInput = document.getElementById( 'np-import-file' );
	var importBtn = document.getElementById( 'np-import-btn' );
	var labelEl   = document.getElementById( 'np-dropzone-label' );
	var iconEl    = dropzone ? dropzone.querySelector( '.np-dropzone__icon' ) : null;

	if ( ! dropzone || ! fileInput || ! importBtn ) { return; }

	/* ── Helper: update UI to show a chosen file ──────────────────────── */
	function showFile( name ) {
		if ( labelEl ) { labelEl.textContent = name; }
		if ( iconEl  ) { iconEl.textContent  = '✅'; }
		dropzone.classList.add( 'np-dropzone--has-file' );
		dropzone.classList.remove( 'np-dropzone--active' );
		importBtn.disabled = false;   // ← always enable directly here
	}

	/* ── Helper: reset to default state ──────────────────────────────── */
	function resetDropzone() {
		if ( labelEl ) { labelEl.textContent = 'Drop your JSON file here or click to browse'; }
		if ( iconEl  ) { iconEl.textContent  = '📄'; }
		dropzone.classList.remove( 'np-dropzone--has-file', 'np-dropzone--active' );
		importBtn.disabled = true;
	}

	/* ── Click-to-browse: standard file input change ──────────────────── */
	fileInput.addEventListener( 'change', function () {
		var file = fileInput.files && fileInput.files[0];
		if ( file ) {
			showFile( file.name );
		} else {
			resetDropzone();
		}
	} );

	/* ── Drag over: highlight the zone ───────────────────────────────── */
	dropzone.addEventListener( 'dragover', function ( e ) {
		e.preventDefault();
		dropzone.classList.add( 'np-dropzone--active' );
	} );

	dropzone.addEventListener( 'dragleave', function ( e ) {
		// Only remove highlight when leaving the dropzone itself,
		// not when moving over a child element inside it.
		if ( ! dropzone.contains( e.relatedTarget ) ) {
			dropzone.classList.remove( 'np-dropzone--active' );
		}
	} );

	/* ── Drop: handle the dragged file ───────────────────────────────── */
	dropzone.addEventListener( 'drop', function ( e ) {
		e.preventDefault();
		dropzone.classList.remove( 'np-dropzone--active' );

		var files = e.dataTransfer && e.dataTransfer.files;
		if ( ! files || ! files.length ) { return; }

		var file = files[0];

		// Try to assign the file to the hidden input so the form submits it.
		// DataTransfer constructor works in all modern browsers.
		try {
			var dt = new DataTransfer();
			dt.items.add( file );
			fileInput.files = dt.files;
		} catch ( err ) {
			// DataTransfer not available (very old browsers).
			// The form will submit without the file — handled below.
		}

		// Enable the button and update the label DIRECTLY here.
		// Do NOT rely on dispatching a 'change' event — it is unreliable
		// after programmatic fileInput.files assignment across browsers.
		showFile( file.name );
	} );

} () );
