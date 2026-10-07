/**
 * NoticePulse — WYSIWYG Live Preview (v2.2.0).
 *
 * Renders a true-to-front-end preview of the bar inside an isolated <iframe>
 * that loads the REAL public stylesheet (noticepulse-public.css). Because the
 * iframe has its own viewport width, the real responsive @media rules fire,
 * so the desktop/mobile toggle is genuine WYSIWYG — not an approximation.
 *
 * Why an iframe and a dedicated renderer (not the front-end scripts):
 *   - Style isolation: admin CSS can't leak into the bar and vice-versa.
 *   - Zero risk to the live front-end — no front-end file is modified or loaded.
 *   - Full lifecycle control: the countdown/carousel timers are OWNED here and
 *     cleared on every rebuild, so nothing leaks as the user types.
 *
 * The bar markup and class names mirror class-noticepulse-public.php + the
 * feature scripts exactly, so the real stylesheet styles it identically.
 *
 * @package NoticePulse
 * @since   2.2.0
 */
/* global jQuery, noticepulsePreview */

( function ( $ ) {
	'use strict';

	var frame, doc, stageEl;
	var device        = 'desktop';
	var cdTimer       = null;
	var tickerTimer   = null;
	var debounceId    = null;

	var RADIUS = { sharp: '0px', rounded: '4px', pill: '50px' };

	// ── Boot ────────────────────────────────────────────────────────────────────
	function init() {
		frame   = document.getElementById( 'np-preview-frame' );
		stageEl = document.getElementById( 'np-preview-stage' );
		if ( ! frame ) { return; }

		writeSkeleton();
		bind();
		initCollapse();
		// Expose a refresh hook so the color pickers (iris) can nudge us.
		window.npRefreshPreview = schedule;
		render();
	}

	// ── Collapse (remembered per browser) ───────────────────────────────────────
	var COLLAPSE_KEY = 'np_preview_collapsed';

	function initCollapse() {
		var dock = document.getElementById( 'np-preview-dock' );
		var btn  = document.getElementById( 'np-preview-collapse' );
		if ( ! dock || ! btn ) { return; }

		var collapsed = false;
		try { collapsed = '1' === window.localStorage.getItem( COLLAPSE_KEY ); } catch ( e ) {}
		applyCollapse( dock, btn, collapsed );

		btn.addEventListener( 'click', function () {
			var next = ! dock.classList.contains( 'np-preview-dock--collapsed' );
			applyCollapse( dock, btn, next );
			try { window.localStorage.setItem( COLLAPSE_KEY, next ? '1' : '0' ); } catch ( e ) {}
		} );
	}

	function applyCollapse( dock, btn, collapsed ) {
		dock.classList.toggle( 'np-preview-dock--collapsed', collapsed );
		document.body.classList.toggle( 'np-dock-collapsed', collapsed );
		btn.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
		// Re-reserve page space for the new dock height (after the CSS transition).
		setTimeout( syncDockSpace, 240 );
	}

	// ── Form value helpers ────────────────────────────────────────────────────
	function val( name ) { return $( '#np-bar-form [name="' + name + '"]' ).val(); }
	function checked( name ) { return $( '#np-bar-form [name="' + name + '"]' ).is( ':checked' ); }
	function radio( name ) { return $( '#np-bar-form [name="' + name + '"]:checked' ).val(); }
	function hasField( name ) { return $( '#np-bar-form [name="' + name + '"]' ).length > 0; }

	function collect() {
		var type = radio( 'bar_type' ) || 'standard';
		return {
			type:        type,
			message:     $( '#np-message' ).val() || '',
			// Per-element visibility (default on when the control is absent).
			iconOn:      checked( 'element_icon_enabled' ),
			iconVal:     val( 'element_icon_value' ) || '',
			msgOn:       hasField( 'element_message_enabled' ) ? checked( 'element_message_enabled' ) : true,
			ctaOn:       hasField( 'element_cta_enabled' )     ? checked( 'element_cta_enabled' )     : true,
			declineOn:   hasField( 'element_decline_enabled' ) ? checked( 'element_decline_enabled' ) : true,
			position:    radio( 'position' ) || 'top',
			showClose:   checked( 'show_close' ),
			fontSize:    val( 'font_size' )   || 'medium',
			padding:     val( 'bar_padding' ) || 'normal',
			align:       radio( 'text_align' ) || 'center',
			btnRadius:   val( 'btn_radius' )  || 'rounded',
			bg:          val( 'bg_color' )      || '#1a73e8',
			text:        val( 'text_color' )    || '#ffffff',
			btnBg:       val( 'btn_bg_color' )  || '#ffffff',
			btnTxt:      val( 'btn_txt_color' ) || '#1a73e8',
			close:       val( 'close_color' )   || '#ffffff',
			gradOn:      checked( 'gradient_enabled' ),
			gradC1:      val( 'gradient_color1' ) || '#7c3aed',
			gradC2:      val( 'gradient_color2' ) || '#4f46e5',
			gradType:    radio( 'gradient_type' ) || 'linear',
			gradAngle:   val( 'gradient_angle' )  || 135,
			// Standard CTA.
			ctaLabel:    val( 'cta_label' ) || '',
			// Countdown.
			cdEnd:       val( 'countdown_end' ) || '',
			cdLabels:    {
				d: val( 'countdown_label_days' )  || 'Days',
				h: val( 'countdown_label_hours' ) || 'Hours',
				m: val( 'countdown_label_mins' )  || 'Mins',
				s: val( 'countdown_label_secs' )  || 'Secs'
			},
			cdShow:      {
				h: checked( 'countdown_show_hours' ),
				m: checked( 'countdown_show_mins' ),
				s: checked( 'countdown_show_seconds' )
			},
			cdCta:       val( 'countdown_cta_label' ) || '',
			// Email.
			emailPh:     val( 'email_placeholder' ) || 'Enter your email…',
			emailBtn:    val( 'email_btn_label' )   || 'Subscribe',
			// Coupon.
			couponCode:  val( 'coupon_code' )      || 'SAVE20',
			couponBtn:   val( 'coupon_btn_label' ) || 'Copy Code',
			// Call.
			callPhone:   val( 'call_phone' )     || '',
			callBtn:     val( 'call_btn_label' ) || '📞 Call Us',
			callHours:   val( 'call_hours' )     || '',
			// GDPR.
			gAccept:     val( 'gdpr_accept_label' )  || 'Accept All',
			gDecline:    val( 'gdpr_decline_label' ) || 'Decline',
			gPolicyUrl:  val( 'gdpr_policy_url' )    || '',
			gPolicyLbl:  val( 'gdpr_policy_label' )  || 'Privacy Policy',
			// Ticker.
			ticker:      collectTicker(),
			tickSpeed:   parseInt( val( 'ticker_speed' ) || '4', 10 ),
			tickTrans:   radio( 'ticker_transition' ) || 'fade',
			tickArrows:  checked( 'ticker_show_arrows' ),
			tickDots:    checked( 'ticker_show_dots' )
		};
	}

	function collectTicker() {
		var out = [];
		$( '#np-ticker-items .np-ticker-item' ).each( function () {
			var $r = $( this );
			var t  = $.trim( $r.find( '.np-ticker-text' ).val() || '' );
			if ( ! t ) { return; }
			out.push( {
				text:      t,
				cta_label: $r.find( '.np-ticker-cta-label' ).val() || '',
				cta_url:   $r.find( '.np-ticker-cta-url' ).val()   || ''
			} );
		} );
		return out;
	}

	// ── Iframe skeleton (written once; CSS persists across renders) ─────────────
	function writeSkeleton() {
		doc = frame.contentDocument;
		var css = ( typeof noticepulsePreview !== 'undefined' && noticepulsePreview.publicCss ) ? noticepulsePreview.publicCss : '';
		doc.open();
		doc.write(
			'<!doctype html><html><head><meta charset="utf-8">' +
			'<meta name="viewport" content="width=device-width, initial-scale=1">' +
			( css ? '<link rel="stylesheet" href="' + css + '">' : '' ) +
			'<style>' + frameCss() + '</style></head>' +
			'<body><div class="np-pv-page" id="np-pv-page"></div></body></html>'
		);
		doc.close();
	}

	function frameCss() {
		return [
			'html,body{margin:0;padding:0;}',
			'body{background:#eef1f5;}',
			'.np-pv-page{box-sizing:border-box;}',
			// Neutralise entrance animation + fixed positioning inside the preview.
			'.np-bar,.np-bar--bottom{animation:none !important;position:relative !important;}'
		].join( '' );
	}

	// ── Render ──────────────────────────────────────────────────────────────────
	function schedule() {
		clearTimeout( debounceId );
		debounceId = setTimeout( render, 180 );
	}

	function render() {
		if ( ! doc ) { return; }
		var page = doc.getElementById( 'np-pv-page' );
		if ( ! page ) { return; }

		clearTimers();

		var v   = collect();
		var bar = buildBar( v );

		// Show the bar only — no faux page / top-vs-bottom simulation. The dock
		// already sits at the bottom of the screen, so the bar alone is enough.
		page.innerHTML = '';
		page.appendChild( bar );

		// Start dynamic behaviours that need owned timers.
		if ( 'countdown' === v.type ) { startCountdown( bar, v ); }
		if ( 'ticker' === v.type )    { startTicker( bar, v ); }

		// Fit the dock to the bar's real height (now, and once CSS/fonts settle).
		autoSize();
		setTimeout( autoSize, 250 );
	}

	// ── Auto-size the frame to the bar, and reserve page space for the dock ──────
	function autoSize() {
		if ( ! doc ) { return; }
		var bar = doc.querySelector( '.np-bar' );
		var h   = bar ? Math.ceil( bar.getBoundingClientRect().height ) : 56;
		h = Math.max( 44, Math.min( 240, h ) );
		if ( frame ) { frame.style.height = h + 'px'; }
		syncDockSpace();
	}

	// Grow (never shrink) the frame to fit the bar — used as the carousel rotates
	// to taller/wrapping messages so a vertical scrollbar never appears.
	function growFrame() {
		if ( ! doc || ! frame ) { return; }
		var bar = doc.querySelector( '.np-bar' );
		if ( ! bar ) { return; }
		var h   = Math.min( 240, Math.ceil( bar.getBoundingClientRect().height ) );
		var cur = parseInt( frame.style.height, 10 ) || 0;
		if ( h > cur ) { frame.style.height = h + 'px'; syncDockSpace(); }
	}

	// Reserve exactly enough bottom padding so the fixed dock never hides content.
	function syncDockSpace() {
		var dock = document.getElementById( 'np-preview-dock' );
		var wrap = document.getElementById( 'wpbody-content' ) || document.body;
		if ( ! dock || ! wrap ) { return; }
		wrap.style.paddingBottom = ( dock.offsetHeight + 24 ) + 'px';
	}

	function clearTimers() {
		if ( cdTimer )     { clearInterval( cdTimer );     cdTimer = null; }
		if ( tickerTimer ) { clearInterval( tickerTimer ); tickerTimer = null; }
	}

	// ── Bar builder ───────────────────────────────────────────────────────────
	function buildBar( v ) {
		var bar = doc.createElement( 'div' );
		bar.className = [
			'np-bar',
			'bottom' === v.position ? 'np-bar--bottom' : 'np-bar--top',
			'np-bar--size-' + v.fontSize,
			'np-bar--padding-' + v.padding,
			'np-bar--align-' + v.align,
			'np-bar--type-' + v.type
		].join( ' ' );
		bar.setAttribute( 'data-bar-type', v.type );
		bar.setAttribute( 'role', 'banner' );

		var bg = v.gradOn
			? ( 'radial' === v.gradType
				? 'radial-gradient(circle,' + v.gradC1 + ',' + v.gradC2 + ')'
				: 'linear-gradient(' + v.gradAngle + 'deg,' + v.gradC1 + ',' + v.gradC2 + ')' )
			: v.bg;
		var radius = RADIUS[ v.btnRadius ] || '4px';
		bar.style.cssText = 'background:' + bg + ';color:' + v.text +
			';--np-btn-bg:' + v.btnBg + ';--np-btn-txt:' + v.btnTxt +
			';--np-btn-radius:' + radius + ';';

		var inner = doc.createElement( 'div' );
		inner.className = 'np-bar__inner';

		var content = doc.createElement( 'div' );
		content.className = 'np-bar__content';

		if ( v.iconOn && v.iconVal ) {
			var ic = doc.createElement( 'span' );
			ic.className = 'np-bar__icon';
			ic.textContent = v.iconVal;
			content.appendChild( ic );
		}

		// The carousel needs the message node to exist, so force it on for ticker.
		if ( v.msgOn || 'ticker' === v.type ) {
			var msg = doc.createElement( 'span' );
			msg.className = 'np-bar__message';
			msg.innerHTML = v.message || placeholderFor( v.type );
			content.appendChild( msg );
		}

		buildTypeExtras( content, v );

		inner.appendChild( content );

		if ( v.showClose && 'gdpr' !== v.type ) {
			var close = doc.createElement( 'button' );
			close.className = 'np-bar__close';
			close.type = 'button';
			close.style.color = v.close;
			close.innerHTML = '&times;';
			inner.appendChild( close );
		}

		bar.appendChild( inner );
		return bar;
	}

	function placeholderFor( type ) {
		var map = {
			standard:      'Your bar message appears here.',
			gdpr:          'We use cookies to improve your experience.',
			countdown:     '⏳ Sale ends in:',
			ticker:        'Your rotating messages appear here.',
			email_capture: '📧 Join our newsletter for 10% off!',
			coupon_copy:   '🎟 Use the code for 20% off!',
			click_to_call: 'Questions? Call our team now.'
		};
		return map[ type ] || 'Your bar message appears here.';
	}

	// Build the per-type UI (mirrors the real feature scripts' markup/classes).
	function buildTypeExtras( content, v ) {
		switch ( v.type ) {
			case 'standard':
				if ( v.ctaLabel && v.ctaOn ) { content.appendChild( ctaEl( v.ctaLabel, v ) ); }
				break;
			case 'countdown':
				// Timer is inserted by startCountdown(); CTA sits after it.
				if ( v.cdCta && v.ctaOn ) { content.appendChild( ctaEl( v.cdCta, v ) ); }
				break;
			case 'email_capture':
				content.appendChild( emailForm( v ) );
				break;
			case 'coupon_copy':
				content.appendChild( couponWrap( v ) );
				break;
			case 'click_to_call':
				content.appendChild( callBtn( v ) );
				break;
			case 'gdpr':
				content.appendChild( gdprActions( v ) );
				break;
			// ticker handled by startTicker().
		}
	}

	function ctaEl( label, v ) {
		var a = doc.createElement( 'a' );
		a.className = 'np-bar__cta';
		a.href = '#';
		a.textContent = label;
		a.style.background   = v.btnBg;
		a.style.color        = v.btnTxt;
		a.style.borderRadius = RADIUS[ v.btnRadius ] || '4px';
		return a;
	}

	function emailForm( v ) {
		var f = doc.createElement( 'div' );
		f.className = 'np-email-form';
		var input = doc.createElement( 'input' );
		input.type = 'email'; input.className = 'np-email-input'; input.placeholder = v.emailPh;
		var btn = doc.createElement( 'button' );
		btn.type = 'button'; btn.className = 'np-email-btn'; btn.textContent = v.emailBtn;
		btn.style.background = v.btnBg; btn.style.color = v.btnTxt;
		f.appendChild( input ); f.appendChild( btn );
		return f;
	}

	function couponWrap( v ) {
		var w = doc.createElement( 'div' );
		w.className = 'np-coupon-wrap';
		var code = doc.createElement( 'span' );
		code.className = 'np-coupon-code'; code.textContent = v.couponCode;
		var btn = doc.createElement( 'button' );
		btn.type = 'button'; btn.className = 'np-coupon-btn'; btn.textContent = v.couponBtn;
		btn.style.background = v.btnBg; btn.style.color = v.btnTxt;
		w.appendChild( code ); w.appendChild( btn );
		return w;
	}

	function callBtn( v ) {
		var frag = doc.createDocumentFragment();
		var a = doc.createElement( 'a' );
		a.className = 'np-call-btn'; a.href = '#'; a.textContent = v.callBtn;
		frag.appendChild( a );
		if ( v.callHours ) {
			var h = doc.createElement( 'span' );
			h.className = 'np-call-hours'; h.textContent = v.callHours;
			frag.appendChild( h );
		}
		return frag;
	}

	function gdprActions( v ) {
		var wrap = doc.createElement( 'div' );
		wrap.className = 'np-gdpr-actions';
		if ( v.gPolicyLbl ) {
			var link = doc.createElement( 'a' );
			link.className = 'np-gdpr-policy-link'; link.href = '#'; link.textContent = v.gPolicyLbl;
			wrap.appendChild( link );
		}
		if ( v.declineOn ) {
			var dec = doc.createElement( 'button' );
			dec.type = 'button'; dec.className = 'np-gdpr-btn np-gdpr-btn--decline'; dec.textContent = v.gDecline;
			wrap.appendChild( dec );
		}
		var acc = doc.createElement( 'button' );
		acc.type = 'button'; acc.className = 'np-gdpr-btn np-gdpr-btn--accept'; acc.textContent = v.gAccept;
		wrap.appendChild( acc );
		return wrap;
	}

	// ── Countdown (owned timer) ─────────────────────────────────────────────────
	function startCountdown( bar, v ) {
		var content = bar.querySelector( '.np-bar__content' );
		if ( ! content ) { return; }

		var endTs = v.cdEnd ? Math.floor( new Date( v.cdEnd.replace( ' ', 'T' ) ).getTime() / 1000 ) : 0;
		var now   = Math.floor( Date.now() / 1000 );
		if ( ! endTs || endTs <= now ) {
			// No real/future date set — show a representative demo countdown.
			endTs = now + ( 2 * 86400 + 14 * 3600 + 37 * 60 + 12 );
		}

		var widget = doc.createElement( 'div' );
		widget.className = 'np-countdown';
		var html = block( 'd', v.cdLabels.d );
		if ( v.cdShow.h ) { html += sep() + block( 'h', v.cdLabels.h ); }
		if ( v.cdShow.m ) { html += sep() + block( 'm', v.cdLabels.m ); }
		if ( v.cdShow.s ) { html += sep() + block( 's', v.cdLabels.s ); }
		widget.innerHTML = html;

		var cta = content.querySelector( '.np-bar__cta' );
		if ( cta ) { content.insertBefore( widget, cta ); } else { content.appendChild( widget ); }

		function block( k, lbl ) {
			return '<span class="np-cd-block"><span class="np-cd-num" data-k="' + k + '">00</span>' +
				'<span class="np-cd-lbl">' + esc( lbl ) + '</span></span>';
		}
		function sep() { return '<span class="np-cd-sep">:</span>'; }

		function tick() {
			var diff = endTs - Math.floor( Date.now() / 1000 );
			if ( diff < 0 ) { diff = 0; }
			var d = Math.floor( diff / 86400 );
			var h = Math.floor( ( diff % 86400 ) / 3600 );
			var m = Math.floor( ( diff % 3600 ) / 60 );
			var s = diff % 60;
			set( widget, 'd', d ); set( widget, 'h', h ); set( widget, 'm', m ); set( widget, 's', s );
		}
		tick();
		cdTimer = setInterval( tick, 1000 );
	}

	function set( widget, k, n ) {
		var el = widget.querySelector( '.np-cd-num[data-k="' + k + '"]' );
		if ( el ) { el.textContent = n < 10 ? '0' + n : String( n ); }
	}

	// ── Ticker / carousel (owned timer) ─────────────────────────────────────────
	function startTicker( bar, v ) {
		var content = bar.querySelector( '.np-bar__content' );
		var msgEl   = content ? content.querySelector( '.np-bar__message' ) : null;
		if ( ! msgEl ) { return; }

		var msgs = v.ticker.length ? v.ticker : [ { text: v.message || placeholderFor( 'ticker' ), cta_label: '', cta_url: '' } ];

		var cta = doc.createElement( 'a' );
		cta.className = 'np-bar__cta np-carousel-cta'; cta.href = '#';
		cta.style.background = v.btnBg; cta.style.color = v.btnTxt; cta.style.display = 'none';
		content.appendChild( cta );

		var dotsWrap = null;
		if ( ( v.tickArrows || v.tickDots ) ) {
			var nav = doc.createElement( 'div' );
			nav.className = 'np-ticker-nav';
			if ( v.tickArrows ) { nav.appendChild( arrow( '‹' ) ); }
			if ( v.tickDots ) {
				dotsWrap = doc.createElement( 'div' ); dotsWrap.className = 'np-ticker-dots';
				msgs.forEach( function ( _, i ) {
					var dot = doc.createElement( 'span' );
					dot.className = 'np-ticker-dot' + ( 0 === i ? ' np-ticker-dot--active' : '' );
					dotsWrap.appendChild( dot );
				} );
				nav.appendChild( dotsWrap );
			}
			if ( v.tickArrows ) { nav.appendChild( arrow( '›' ) ); }
			content.appendChild( nav );
		}

		function arrow( glyph ) {
			var b = doc.createElement( 'button' );
			b.type = 'button'; b.className = 'np-ticker-arrow'; b.innerHTML = glyph;
			return b;
		}

		var i = 0;
		function show( idx ) {
			var m = msgs[ idx ];
			msgEl.textContent = m.text;
			if ( m.cta_label ) { cta.textContent = m.cta_label; cta.style.display = ''; }
			else { cta.style.display = 'none'; }
			if ( dotsWrap ) {
				$( dotsWrap ).children().each( function ( j ) {
					this.className = 'np-ticker-dot' + ( j === idx ? ' np-ticker-dot--active' : '' );
				} );
			}
			growFrame(); // taller/wrapping messages must not clip (no scrollbar)
		}
		show( 0 );

		if ( msgs.length > 1 ) {
			tickerTimer = setInterval( function () {
				i = ( i + 1 ) % msgs.length;
				if ( 'slide' === v.tickTrans ) {
					msgEl.style.transition = 'transform .28s ease, opacity .28s ease';
					msgEl.style.opacity = '0'; msgEl.style.transform = 'translateY(-8px)';
					setTimeout( function () { show( i ); msgEl.style.transform = 'translateY(0)'; msgEl.style.opacity = '1'; }, 200 );
				} else {
					msgEl.style.transition = 'opacity .3s ease';
					msgEl.style.opacity = '0';
					setTimeout( function () { show( i ); msgEl.style.opacity = '1'; }, 260 );
				}
			}, Math.max( 1, v.tickSpeed ) * 1000 );
		}
	}

	function esc( s ) {
		return String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
	}

	// ── Device toggle ────────────────────────────────────────────────────────
	function setDevice( d ) {
		device = d;
		$( '.np-preview-device' ).removeClass( 'np-preview-device--active' );
		$( '.np-preview-device[data-device="' + d + '"]' ).addClass( 'np-preview-device--active' );
		$( stageEl ).toggleClass( 'np-preview-stage--mobile', 'mobile' === d );
		// Width changed → the bar re-wraps, so re-measure its height.
		autoSize();
		setTimeout( autoSize, 60 );
	}

	// ── Bind ────────────────────────────────────────────────────────────────────
	function bind() {
		$( '#np-bar-form' ).on( 'input change', schedule );

		// The bar-type buttons set the radio with .checked = true (no native
		// change event), and ticker rows add/remove without firing input — nudge
		// the preview on those clicks too. setTimeout lets their own handlers run
		// first so collect() reads the updated state.
		$( document ).on( 'click', '.np-type-btn, .np-ticker-remove, #np-ticker-add', function () {
			setTimeout( schedule, 0 );
		} );

		$( '.np-preview-device' ).on( 'click', function () {
			setDevice( $( this ).data( 'device' ) );
		} );
	}

	$( document ).ready( init );

}( jQuery ) );
