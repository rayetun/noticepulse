<?php
/**
 * Admin view: Add / Edit notification bar.
 *
 * ARCHITECTURE NOTE — single name="message" field:
 * All bar types share ONE message textarea. The JS swaps the
 * placeholder text when bar type changes. This ensures the
 * correct message always submits regardless of bar type.
 *
 * Per-type sections only contain their UNIQUE fields.
 *
 * @package NoticePulse
 * @since   2.1.4
 *
 * @var object|null $bar    Bar object or null for new bar.
 * @var bool        $is_new True when creating a new bar.
 * @var string      $notice Admin notice key.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// ── Defaults ──────────────────────────────────────────────────────────────────
$np_d = array(
	'name'          => '',
	'bar_type'      => 'standard',
	'message'       => '',
	'cta_label'     => '',
	'cta_url'       => '',
	'cta_target'    => '_self',
	'position'      => 'top',
	'is_sticky'     => 1,
	'show_desktop'  => 1,
	'show_tablet'   => 1,
	'show_mobile'   => 1,
	'show_close'    => 1,
	'cookie_days'   => 7,
	'visibility'    => 'all',
	'page_ids'      => '',
	'user_status'   => 'all',
	'font_size'     => 'medium',
	'bar_padding'   => 'normal',
	'btn_radius'    => 'rounded',
	'text_align'    => 'center',
	'bg_color'      => '#1a73e8',
	'text_color'    => '#ffffff',
	'btn_bg_color'  => '#ffffff',
	'btn_txt_color' => '#1a73e8',
	'close_color'   => '#ffffff',
	'date_start'    => '',
	'date_end'      => '',
	'is_active'     => 1,
	'bar_meta'      => '',
);

$np_meta = array();

if ( $bar ) {
	foreach ( $np_d as $np_k => $np_v ) {
		if ( isset( $bar->$np_k ) ) { $np_d[ $np_k ] = $bar->$np_k; }
	}
	if ( ! empty( $bar->bar_meta ) ) {
		$np_meta = json_decode( $bar->bar_meta, true ) ?: array();
	}
}

$np_bar_id     = $bar ? absint( $bar->id ) : 0;
$np_page_title = $is_new ? __( 'Add New Bar', 'noticepulse' ) : __( 'Edit Bar', 'noticepulse' );
$np_cur_type   = $np_d['bar_type'];

// bar_meta sub-arrays
$np_gdpr      = $np_meta['gdpr']      ?? array();
$np_ticker    = $np_meta['ticker']    ?? array();
$np_call      = $np_meta['call']      ?? array();
$np_countdown = $np_meta['countdown'] ?? array();
$np_email     = $np_meta['email']     ?? array();
$np_coupon    = $np_meta['coupon']    ?? array();
$np_gradient  = $np_meta['gradient']  ?? array();
$np_font      = $np_meta['font']      ?? array();
$np_css_meta  = $np_meta['css']       ?? array();
$np_triggers  = $np_meta['triggers']  ?? array();
$np_ab        = $np_meta['ab']        ?? array();
$np_geo       = $np_meta['geo']       ?? array();
$np_ga4       = $np_meta['ga4']       ?? array();
$np_freq      = $np_meta['frequency'] ?? array();
$np_anim      = $np_meta['animation'] ?? array();

// Message placeholders per bar type (used by JS)
$np_msg_placeholders = array(
	'standard'      => '🎉 Free shipping on all orders today!',
	'gdpr'          => 'We use cookies to improve your experience. Accept to continue.',
	'ticker'        => 'Shown in the preview — your carousel messages appear here.',
	'click_to_call' => 'Get a free quote today! Call us now.',
	'countdown'     => '⏳ Sale ends in:',
	'email_capture' => '📧 Get 10% off — join our newsletter!',
	'coupon_copy'   => '🎟 Use code below for 20% off your order!',
);

// Bar type definitions
$np_bar_types = array(
	'standard'      => array( 'icon' => '📢', 'label' => 'Announcement' ),
	'gdpr'          => array( 'icon' => '🍪', 'label' => 'Cookie / GDPR' ),
	'ticker'        => array( 'icon' => '🎠', 'label' => 'Text Carousel' ),
	'click_to_call' => array( 'icon' => '📞', 'label' => 'Click-to-Call' ),
	'countdown'     => array( 'icon' => '⏱',  'label' => 'Countdown' ),
	'email_capture' => array( 'icon' => '📧', 'label' => 'Email Capture' ),
	'coupon_copy'   => array( 'icon' => '🎟', 'label' => 'Coupon Copy' ),
);
?>

<div class="wrap np-wrap np-wrap--edit np-wrap--v2">

<?php if ( $notice ) : $this->render_notice( $notice ); endif; ?>

<!-- ── PAGE HEADER ─────────────────────────────────────────────────────────── -->
<div class="np-edit-header">
	<div class="np-edit-header__left">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse' ) ); ?>" class="np-edit-back">← <?php esc_html_e( 'All Bars', 'noticepulse' ); ?></a>
		<h1 class="np-edit-title"><?php echo esc_html( $np_page_title ); ?></h1>
		<?php if ( ! $is_new ) : ?><span class="np-edit-id">#<?php echo esc_html( $np_bar_id ); ?></span><?php endif; ?>
	</div>
	<div class="np-edit-header__right">
		<button type="button" id="np-browse-templates" title="<?php esc_attr_e( 'Browse pre-designed templates', 'noticepulse' ); ?>">
			🎨 <?php esc_html_e( 'Browse Templates', 'noticepulse' ); ?>
		</button>
		<button type="submit" form="np-bar-form" class="np-edit-save-btn">
			<?php echo $is_new ? esc_html__( 'Publish Bar', 'noticepulse' ) : esc_html__( 'Update Bar', 'noticepulse' ); ?>
		</button>
	</div>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="np-bar-form" novalidate>
	<input type="hidden" name="action"  value="noticepulse_save_bar">
	<input type="hidden" name="bar_id"  value="<?php echo esc_attr( $np_bar_id ); ?>">
	<?php wp_nonce_field( 'noticepulse_save_bar', 'noticepulse_nonce' ); ?>


	<div class="np-edit-layout">
		<div class="np-edit-main">

			<!-- ── BAR TYPE SELECTOR ────────────────────────────────────── -->
			<div class="np-type-selector" id="np-type-selector">
				<?php foreach ( $np_bar_types as $np_tk => $np_tv ) : ?>
				<label class="np-type-btn <?php echo esc_attr( $np_cur_type === $np_tk ? 'np-type-btn--active' : '' ); ?>" data-type="<?php echo esc_attr( $np_tk ); ?>">
					<input type="radio" name="bar_type" value="<?php echo esc_attr( $np_tk ); ?>" <?php checked( $np_cur_type, $np_tk ); ?> class="np-type-radio">
					<span class="np-type-btn__icon"><?php echo esc_html( $np_tv['icon']);?></span>
					<span class="np-type-btn__label"><?php echo esc_html( $np_tv['label'] ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>

			<!-- ── TABS ─────────────────────────────────────────────────── -->
			<div class="np-tabs" id="np-tabs">
				<button type="button" class="np-tab-btn np-tab-btn--active" data-tab="content"><span class="np-tab-btn__icon">✏️</span><?php esc_html_e( 'Content', 'noticepulse' ); ?></button>
				<button type="button" class="np-tab-btn" data-tab="design"><span class="np-tab-btn__icon">🎨</span><?php esc_html_e( 'Design', 'noticepulse' ); ?></button>
				<button type="button" class="np-tab-btn" data-tab="display"><span class="np-tab-btn__icon">📐</span><?php esc_html_e( 'Display', 'noticepulse' ); ?></button>
				<button type="button" class="np-tab-btn" data-tab="triggers"><span class="np-tab-btn__icon">⚡</span><?php esc_html_e( 'Triggers', 'noticepulse' ); ?></button>
				<button type="button" class="np-tab-btn" data-tab="advanced"><span class="np-tab-btn__icon">🔬</span><?php esc_html_e( 'Advanced', 'noticepulse' ); ?></button>
			</div>

			<!-- ══════════════════════════════════════════════════════════ -->
			<!-- TAB: CONTENT                                               -->
			<!-- ══════════════════════════════════════════════════════════ -->
			<div class="np-tab-panel np-tab-panel--active" id="np-tab-content" data-panel="content">

				<!-- ─── Bar Name — always visible ─────────────────────── -->
				<div class="np-panel-section">
					<div class="np-field">
						<label for="np-name"><?php esc_html_e( 'Bar Name', 'noticepulse' ); ?> <span class="np-required">*</span></label>
						<input type="text" id="np-name" name="name" value="<?php echo esc_attr( $np_d['name'] ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Summer Sale Banner', 'noticepulse' ); ?>" required>
						<p class="np-field__desc"><?php esc_html_e( 'Internal name — not shown to visitors.', 'noticepulse' ); ?></p>
					</div>
				</div>

				<!-- ─── Message — ONE field, shared by ALL bar types ─── -->
				<div class="np-panel-section">
					<div class="np-field">
						<label for="np-message" id="np-message-label">
							<?php
							// Label changes per bar type
							$np_msg_labels = array(
								'standard'      => __( 'Message', 'noticepulse' ),
								'gdpr'          => __( 'Cookie Notice Message', 'noticepulse' ),
								'ticker'        => __( 'Fallback Message (shown if JS disabled)', 'noticepulse' ),
								'click_to_call' => __( 'Headline Message', 'noticepulse' ),
								'countdown'     => __( 'Bar Message', 'noticepulse' ),
								'email_capture' => __( 'Headline Message', 'noticepulse' ),
								'coupon_copy'   => __( 'Bar Message', 'noticepulse' ),
							);
							echo esc_html( $np_msg_labels[ $np_cur_type ] ?? __( 'Message', 'noticepulse' ) );
							?> <span class="np-required">*</span>
						</label>
						<textarea id="np-message" name="message" rows="3" class="large-text"
							placeholder="<?php echo esc_attr( $np_msg_placeholders[ $np_cur_type ] ?? '' ); ?>"><?php echo esc_textarea( $np_d['message'] ); ?></textarea>
						<p class="np-field__desc" id="np-message-desc">
							<?php
							if ( 'gdpr' === $np_cur_type ) {
								esc_html_e( 'Accept & Decline buttons are added automatically below this message.', 'noticepulse' );
							} elseif ( 'ticker' === $np_cur_type ) {
								esc_html_e( 'Add your rotating messages below. This text is shown only if JS is disabled.', 'noticepulse' );
							} else {
								esc_html_e( 'Supports: <strong>, <em>, <a>, <br>. Emoji ✓', 'noticepulse' );
							}
							?>
						</p>
					</div>
				</div>

				<!-- ─── STANDARD: CTA Button ──────────────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="standard">
					<h3 class="np-section-title"><?php esc_html_e( 'CTA Button', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Button Label', 'noticepulse' ); ?></label>
							<input type="text" name="cta_label" id="np-cta-label" value="<?php echo esc_attr( $np_d['cta_label'] ); ?>" placeholder="<?php esc_attr_e( 'Shop Now', 'noticepulse' ); ?>">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Button URL', 'noticepulse' ); ?></label>
							<input type="url" name="cta_url" id="np-cta-url" value="<?php echo esc_url( $np_d['cta_url'] ); ?>" placeholder="https://">
						</div>
					</div>
					<div class="np-field">
						<label><?php esc_html_e( 'Opens In', 'noticepulse' ); ?></label>
						<div class="np-radio-inline">
							<label><input type="radio" name="cta_target" value="_self"  <?php checked( $np_d['cta_target'], '_self' ); ?>><?php esc_html_e( 'Same Tab', 'noticepulse' ); ?></label>
							<label><input type="radio" name="cta_target" value="_blank" <?php checked( $np_d['cta_target'], '_blank' ); ?>><?php esc_html_e( 'New Tab', 'noticepulse' ); ?></label>
						</div>
					</div>
				</div>

				<!-- ─── GDPR: Consent settings only ───────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="gdpr">
					<h3 class="np-section-title">🍪 <?php esc_html_e( 'Consent Button Settings', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Accept Button', 'noticepulse' ); ?></label>
							<input type="text" name="gdpr_accept_label" value="<?php echo esc_attr( $np_gdpr['accept_label'] ?? __( 'Accept All', 'noticepulse' ) ); ?>">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Decline Button', 'noticepulse' ); ?></label>
							<input type="text" name="gdpr_decline_label" value="<?php echo esc_attr( $np_gdpr['decline_label'] ?? __( 'Decline', 'noticepulse' ) ); ?>">
						</div>
					</div>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Privacy Policy URL', 'noticepulse' ); ?></label>
							<input type="url" name="gdpr_policy_url" value="<?php echo esc_url( $np_gdpr['policy_url'] ?? get_privacy_policy_url() ); ?>">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Policy Link Text', 'noticepulse' ); ?></label>
							<input type="text" name="gdpr_policy_label" value="<?php echo esc_attr( $np_gdpr['policy_label'] ?? __( 'Privacy Policy', 'noticepulse' ) ); ?>">
						</div>
					</div>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Cookie Name', 'noticepulse' ); ?></label>
							<input type="text" name="gdpr_cookie_name" value="<?php echo esc_attr( $np_gdpr['cookie_name'] ?? 'np_gdpr_consent' ); ?>">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Remember for (days)', 'noticepulse' ); ?></label>
							<input type="number" name="gdpr_cookie_days" value="<?php echo esc_attr( $np_gdpr['cookie_days'] ?? 365 ); ?>" min="1" max="3650" class="small-text">
						</div>
					</div>
				</div>

				<!-- ─── TEXT CAROUSEL: Messages + options ──────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="ticker">
					<h3 class="np-section-title">🎠 <?php esc_html_e( 'Carousel Messages', 'noticepulse' ); ?></h3>
					<?php $np_ticker_msgs = $np_ticker['messages'] ?? array(); ?>
					<input type="hidden" name="ticker_messages" id="np-ticker-json" value="<?php echo esc_attr( wp_json_encode( $np_ticker_msgs ) ); ?>">
					<div id="np-ticker-items">
						<?php foreach ( $np_ticker_msgs as $np_ti => $np_tm ) : ?>
						<div class="np-ticker-item" data-index="<?php echo esc_attr( $np_ti ); ?>">
							<span class="np-ticker-drag">⠿</span>
							<div class="np-ticker-fields">
								<input type="text" class="np-ticker-text large-text" placeholder="<?php esc_attr_e( 'Message text…', 'noticepulse' ); ?>" value="<?php echo esc_attr( $np_tm['text'] ?? '' ); ?>">
								<div class="np-ticker-cta">
									<input type="text" class="np-ticker-cta-label" placeholder="<?php esc_attr_e( 'CTA Label (optional)', 'noticepulse' ); ?>" value="<?php echo esc_attr( $np_tm['cta_label'] ?? '' ); ?>">
									<input type="url"  class="np-ticker-cta-url"   placeholder="https://" value="<?php echo esc_url( $np_tm['cta_url'] ?? '' ); ?>">
								</div>
							</div>
							<button type="button" class="np-ticker-remove">✕</button>
						</div>
						<?php endforeach; ?>
					</div>
					<?php if ( empty( $np_ticker_msgs ) ) : ?><p class="np-ticker-empty"><?php esc_html_e( 'No messages yet. Add one below.', 'noticepulse' ); ?></p><?php endif; ?>
					<button type="button" id="np-ticker-add" class="np-add-item-btn">+ <?php esc_html_e( 'Add Message', 'noticepulse' ); ?></button>
					<div class="np-field-row" style="margin-top:14px">
						<div class="np-field">
							<label class="np-field__lbl"><?php esc_html_e( 'Duration (seconds)', 'noticepulse' ); ?></label>
							<input type="number" name="ticker_speed" value="<?php echo esc_attr( $np_ticker['speed'] ?? 4 ); ?>" min="1" max="30" class="small-text">
						</div>
						<div class="np-field">
							<label class="np-field__lbl"><?php esc_html_e( 'Transition', 'noticepulse' ); ?></label>
							<div class="np-radio-inline">
								<label><input type="radio" name="ticker_transition" value="fade"  <?php checked( $np_ticker['transition'] ?? 'fade', 'fade' ); ?>><?php esc_html_e( 'Fade', 'noticepulse' ); ?></label>
								<label><input type="radio" name="ticker_transition" value="slide" <?php checked( $np_ticker['transition'] ?? 'fade', 'slide' ); ?>><?php esc_html_e( 'Slide', 'noticepulse' ); ?></label>
							</div>
						</div>
					</div>
					<div class="np-field">
						<div class="np-checks-row">
							<label><input type="checkbox" name="ticker_pause_hover" value="1" <?php checked( $np_ticker['pause_hover'] ?? 1, 1 ); ?>><?php esc_html_e( 'Pause on hover', 'noticepulse' ); ?></label>
							<label><input type="checkbox" name="ticker_show_arrows" value="1" <?php checked( $np_ticker['show_arrows'] ?? 0, 1 ); ?>><?php esc_html_e( 'Show arrows', 'noticepulse' ); ?></label>
							<label><input type="checkbox" name="ticker_show_dots"   value="1" <?php checked( $np_ticker['show_dots']   ?? 1, 1 ); ?>><?php esc_html_e( 'Show dots', 'noticepulse' ); ?></label>
						</div>
					</div>
				</div>

				<!-- ─── CLICK-TO-CALL: Phone fields ───────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="click_to_call">
					<h3 class="np-section-title">📞 <?php esc_html_e( 'Click-to-Call Settings', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Phone Number', 'noticepulse' ); ?></label>
							<input type="tel" name="call_phone" value="<?php echo esc_attr( $np_call['phone'] ?? '' ); ?>" placeholder="+1 (555) 000-0000">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Button Label', 'noticepulse' ); ?></label>
							<input type="text" name="call_btn_label" value="<?php echo esc_attr( $np_call['btn_label'] ?? '📞 Call Us' ); ?>">
						</div>
					</div>
					<div class="np-field">
						<label><?php esc_html_e( 'Hours of Operation (optional)', 'noticepulse' ); ?></label>
						<input type="text" name="call_hours" value="<?php echo esc_attr( $np_call['hours'] ?? '' ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Mon–Fri 9am–6pm', 'noticepulse' ); ?>">
					</div>
					<div class="np-field">
						<label class="np-checks-row"><input type="checkbox" name="call_mobile_only" value="1" <?php checked( $np_call['mobile_only'] ?? 0, 1 ); ?>><?php esc_html_e( 'Show call button on mobile only', 'noticepulse' ); ?></label>
					</div>
				</div>

				<!-- ─── COUNTDOWN: Date/time + labels ─────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="countdown">
					<h3 class="np-section-title">⏱ <?php esc_html_e( 'Countdown Settings', 'noticepulse' ); ?></h3>
					<div class="np-field">
						<label><?php esc_html_e( 'End Date & Time', 'noticepulse' ); ?></label>
						<input type="datetime-local" name="countdown_end" value="<?php echo esc_attr( isset( $np_countdown['end'] ) && $np_countdown['end'] ? gmdate( 'Y-m-d\TH:i', strtotime( $np_countdown['end'] ) ) : '' ); ?>">
					</div>
					<div class="np-field-row">
						<div class="np-field"><label><?php esc_html_e( 'Days Label', 'noticepulse' ); ?></label><input type="text" name="countdown_label_days"  value="<?php echo esc_attr( $np_countdown['label_days']  ?? 'Days' ); ?>" class="small-text"></div>
						<div class="np-field"><label><?php esc_html_e( 'Hours Label', 'noticepulse' ); ?></label><input type="text" name="countdown_label_hours" value="<?php echo esc_attr( $np_countdown['label_hours'] ?? 'Hours' ); ?>" class="small-text"></div>
						<div class="np-field"><label><?php esc_html_e( 'Mins Label', 'noticepulse' ); ?></label><input type="text" name="countdown_label_mins"  value="<?php echo esc_attr( $np_countdown['label_mins']  ?? 'Mins' ); ?>" class="small-text"></div>
						<div class="np-field"><label><?php esc_html_e( 'Secs Label', 'noticepulse' ); ?></label><input type="text" name="countdown_label_secs"  value="<?php echo esc_attr( $np_countdown['label_secs'] ?? 'Secs' ); ?>" class="small-text"></div>
						<div class="np-field">
							<label class="np-field__lbl"><?php esc_html_e( 'Display Units', 'noticepulse' ); ?></label>
							<p class="np-field__desc" style="margin-bottom:8px;"><?php esc_html_e( 'Choose which time units to show. Days is always displayed.', 'noticepulse' ); ?></p>
							<div class="np-checks-col">
								<label class="np-check-item">
									<input type="checkbox" disabled checked> <?php esc_html_e( 'Days (always shown)', 'noticepulse' ); ?>
								</label>
								<label class="np-check-item">
									<input type="checkbox" name="countdown_show_hours" value="1" <?php checked( $np_countdown['show_hours'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Hours', 'noticepulse' ); ?>
								</label>
								<label class="np-check-item">
									<input type="checkbox" name="countdown_show_mins" value="1" <?php checked( $np_countdown['show_mins'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Minutes', 'noticepulse' ); ?>
								</label>
								<label class="np-check-item">
									<input type="checkbox" name="countdown_show_seconds" value="1" <?php checked( $np_countdown['show_seconds'] ?? 1, 1 ); ?>> <?php esc_html_e( 'Seconds', 'noticepulse' ); ?>
								</label>
							</div>
						</div>
					</div>
					<div class="np-field-row">
						<div class="np-field"><label class="np-field__lbl"><?php esc_html_e( 'CTA Button Label', 'noticepulse' ); ?></label><input type="text" name="countdown_cta_label" value="<?php echo esc_attr( $np_d['cta_label'] ); ?>" placeholder="<?php esc_attr_e( 'Shop Now', 'noticepulse' ); ?>"></div>
						<div class="np-field"><label class="np-field__lbl"><?php esc_html_e( 'CTA Button URL', 'noticepulse' ); ?></label><input type="url" name="countdown_cta_url" value="<?php echo esc_url( $np_d['cta_url'] ); ?>" placeholder="https://"></div>
					</div>
					<div class="np-field">
						<label class="np-checks-row"><input type="checkbox" name="countdown_hide_on_expire" value="1" <?php checked( $np_countdown['hide_on_expire'] ?? 1, 1 ); ?>><?php esc_html_e( 'Auto-hide bar when countdown expires', 'noticepulse' ); ?></label>
					</div>
				</div>

				<!-- ─── EMAIL CAPTURE: Form settings ──────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="email_capture">
					<h3 class="np-section-title">📧 <?php esc_html_e( 'Email Form Settings', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Email Placeholder', 'noticepulse' ); ?></label>
							<input type="text" name="email_placeholder" value="<?php echo esc_attr( $np_email['placeholder'] ?? __( 'Enter your email…', 'noticepulse' ) ); ?>">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Submit Button', 'noticepulse' ); ?></label>
							<input type="text" name="email_btn_label" value="<?php echo esc_attr( $np_email['btn_label'] ?? __( 'Subscribe', 'noticepulse' ) ); ?>">
						</div>
					</div>
					<div class="np-field">
						<label><?php esc_html_e( 'Success Message', 'noticepulse' ); ?></label>
						<input type="text" name="email_success_msg" value="<?php echo esc_attr( $np_email['success_msg'] ?? __( '🎉 You\'re in! Check your inbox.', 'noticepulse' ) ); ?>" class="large-text">
					</div>
					<div class="np-field">
						<label><?php esc_html_e( 'Email Provider', 'noticepulse' ); ?></label>
						<select id="np-email-provider" name="email_provider">
							<?php $np_cur_prov = $np_email['provider'] ?? 'none'; ?>
							<option value="none"           <?php selected( $np_cur_prov, 'none' ); ?>><?php esc_html_e( 'Store locally only (no integration)', 'noticepulse' ); ?></option>
							<option value="mailchimp"      <?php selected( $np_cur_prov, 'mailchimp' ); ?>>Mailchimp</option>
							<option value="klaviyo"        <?php selected( $np_cur_prov, 'klaviyo' ); ?>>Klaviyo</option>
							<option value="convertkit"     <?php selected( $np_cur_prov, 'convertkit' ); ?>>ConvertKit</option>
							<option value="activecampaign" <?php selected( $np_cur_prov, 'activecampaign' ); ?>>ActiveCampaign</option>
							<option value="mailerlite"     <?php selected( $np_cur_prov, 'mailerlite' ); ?>>MailerLite</option>
							<option value="brevo"          <?php selected( $np_cur_prov, 'brevo' ); ?>>Brevo</option>
						</select>
					</div>
					<div id="np-email-api-key-row" class="np-field" style="<?php echo esc_attr( $np_cur_prov === 'none' ? 'display:none' : '' ); ?>">
						<label><?php esc_html_e( 'API Key', 'noticepulse' ); ?></label>
						<input type="text" name="email_api_key" value="<?php echo esc_attr( $np_email['api_key'] ?? '' ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Paste your API key here', 'noticepulse' ); ?>">
					</div>
					<div id="np-email-list-id-row" class="np-field" style="<?php echo esc_attr( $np_cur_prov === 'none' ? 'display:none' : '' ); ?>">
						<label><?php esc_html_e( 'List / Audience ID', 'noticepulse' ); ?></label>
						<input type="text" name="email_list_id" value="<?php echo esc_attr( $np_email['list_id'] ?? '' ); ?>" class="regular-text">
					</div>
				</div>

				<!-- ─── COUPON COPY: Code fields ───────────────────────── -->
				<div class="np-panel-section np-bt-section" data-show-for="coupon_copy">
					<h3 class="np-section-title">🎟 <?php esc_html_e( 'Coupon Settings', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field">
							<label><?php esc_html_e( 'Coupon Code', 'noticepulse' ); ?></label>
							<input type="text" name="coupon_code" value="<?php echo esc_attr( $np_coupon['code'] ?? '' ); ?>" placeholder="SAVE20">
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Copy Button Label', 'noticepulse' ); ?></label>
							<input type="text" name="coupon_btn_label" value="<?php echo esc_attr( $np_coupon['btn_label'] ?? __( 'Copy Code', 'noticepulse' ) ); ?>">
						</div>
					</div>
					<div class="np-field">
						<label><?php esc_html_e( 'After-Copy Label', 'noticepulse' ); ?></label>
						<input type="text" name="coupon_success_label" value="<?php echo esc_attr( $np_coupon['success_label'] ?? '✓ Copied!' ); ?>" class="regular-text">
					</div>
				</div>

			</div><!-- /#np-tab-content -->

			<!-- ══════════════════════════════════════════════════════════ -->
			<!-- TAB: DESIGN                                                -->
			<!-- ══════════════════════════════════════════════════════════ -->
			<div class="np-tab-panel" id="np-tab-design" data-panel="design">

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Colors', 'noticepulse' ); ?></h3>
					<div class="np-colors-grid">
						<div class="np-field"><label><?php esc_html_e( 'Bar Background', 'noticepulse' ); ?></label><input type="text" name="bg_color"      value="<?php echo esc_attr( $np_d['bg_color'] ); ?>"      class="np-color-picker" data-default-color="#1a73e8"></div>
						<div class="np-field"><label><?php esc_html_e( 'Message Text',   'noticepulse' ); ?></label><input type="text" name="text_color"    value="<?php echo esc_attr( $np_d['text_color'] ); ?>"    class="np-color-picker" data-default-color="#ffffff"></div>
						<div class="np-field"><label><?php esc_html_e( 'Button Bg',      'noticepulse' ); ?></label><input type="text" name="btn_bg_color"  value="<?php echo esc_attr( $np_d['btn_bg_color'] ); ?>"  class="np-color-picker" data-default-color="#ffffff"></div>
						<div class="np-field"><label><?php esc_html_e( 'Button Text',    'noticepulse' ); ?></label><input type="text" name="btn_txt_color" value="<?php echo esc_attr( $np_d['btn_txt_color'] ); ?>" class="np-color-picker" data-default-color="#1a73e8"></div>
						<div class="np-field"><label><?php esc_html_e( 'Close Button',   'noticepulse' ); ?></label><input type="text" name="close_color"   value="<?php echo esc_attr( $np_d['close_color'] ); ?>"   class="np-color-picker" data-default-color="#ffffff"></div>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Gradient Background', 'noticepulse' ); ?></h3>
					<div class="np-toggle-row">
						<label class="np-toggle-switch-label"><input type="checkbox" name="gradient_enabled" value="1" id="np-gradient-toggle" <?php checked( $np_gradient['enabled'] ?? 0, 1 ); ?>><span class="np-toggle-pill"></span><?php esc_html_e( 'Enable gradient (overrides solid background)', 'noticepulse' ); ?></label>
					</div>
					<div id="np-gradient-fields" <?php echo empty( $np_gradient['enabled'] ) ? 'style="display:none"' : ''; ?>>
						<div class="np-field-row">
							<div class="np-field"><label><?php esc_html_e( 'Color 1', 'noticepulse' ); ?></label><input type="text" name="gradient_color1" value="<?php echo esc_attr( $np_gradient['color1'] ?? '#7c3aed' ); ?>" class="np-color-picker" data-default-color="#7c3aed"></div>
							<div class="np-field"><label><?php esc_html_e( 'Color 2', 'noticepulse' ); ?></label><input type="text" name="gradient_color2" value="<?php echo esc_attr( $np_gradient['color2'] ?? '#4f46e5' ); ?>" class="np-color-picker" data-default-color="#4f46e5"></div>
						</div>
						<div class="np-field">
							<label><?php esc_html_e( 'Type', 'noticepulse' ); ?></label>
							<div class="np-radio-inline">
								<label><input type="radio" name="gradient_type" value="linear" <?php checked( $np_gradient['type'] ?? 'linear', 'linear' ); ?>><?php esc_html_e( 'Linear', 'noticepulse' ); ?></label>
								<label><input type="radio" name="gradient_type" value="radial"  <?php checked( $np_gradient['type'] ?? 'linear', 'radial' ); ?>><?php esc_html_e( 'Radial', 'noticepulse' ); ?></label>
							</div>
						</div>
						<div class="np-field" id="np-gradient-angle-row" <?php echo ( $np_gradient['type'] ?? 'linear' ) === 'radial' ? 'style="display:none"' : ''; ?>>
							<label><?php esc_html_e( 'Angle', 'noticepulse' ); ?> — <span id="np-gradient-angle-val"><?php echo esc_html( $np_gradient['angle'] ?? 135 ); ?></span>°</label>
							<input type="range" id="np-gradient-angle" name="gradient_angle" value="<?php echo esc_attr( $np_gradient['angle'] ?? 135 ); ?>" min="0" max="360" step="5">
							<div class="np-gradient-preview" id="np-gradient-preview"></div>
						</div>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Typography & Layout', 'noticepulse' ); ?></h3>
					<div class="np-field-row np-field-row--3">
						<div class="np-field"><label><?php esc_html_e( 'Font Size', 'noticepulse' ); ?></label><select name="font_size"><option value="small" <?php selected( $np_d['font_size'],'small' ); ?>><?php esc_html_e('Small','noticepulse');?></option><option value="medium" <?php selected( $np_d['font_size'],'medium' ); ?>><?php esc_html_e('Medium','noticepulse');?></option><option value="large" <?php selected( $np_d['font_size'],'large' ); ?>><?php esc_html_e('Large','noticepulse');?></option></select></div>
						<div class="np-field"><label><?php esc_html_e( 'Bar Height', 'noticepulse' ); ?></label><select name="bar_padding"><option value="compact" <?php selected( $np_d['bar_padding'],'compact' ); ?>><?php esc_html_e('Compact','noticepulse');?></option><option value="normal" <?php selected( $np_d['bar_padding'],'normal' ); ?>><?php esc_html_e('Normal','noticepulse');?></option><option value="tall" <?php selected( $np_d['bar_padding'],'tall' ); ?>><?php esc_html_e('Tall','noticepulse');?></option></select></div>
						<div class="np-field"><label><?php esc_html_e( 'Button Style', 'noticepulse' ); ?></label><select id="np-btn-radius" name="btn_radius"><option value="sharp" <?php selected( $np_d['btn_radius'],'sharp' ); ?>><?php esc_html_e('Sharp','noticepulse');?></option><option value="rounded" <?php selected( $np_d['btn_radius'],'rounded' ); ?>><?php esc_html_e('Rounded','noticepulse');?></option><option value="pill" <?php selected( $np_d['btn_radius'],'pill' ); ?>><?php esc_html_e('Pill','noticepulse');?></option></select></div>
					</div>
					<div class="np-field"><label><?php esc_html_e( 'Text Alignment', 'noticepulse' ); ?></label><div class="np-radio-inline"><label><input type="radio" name="text_align" value="left" <?php checked( $np_d['text_align'],'left' ); ?>><?php esc_html_e('Left','noticepulse');?></label><label><input type="radio" name="text_align" value="center" <?php checked( $np_d['text_align'],'center' ); ?>><?php esc_html_e('Center','noticepulse');?></label><label><input type="radio" name="text_align" value="right" <?php checked( $np_d['text_align'],'right' ); ?>><?php esc_html_e('Right','noticepulse');?></label></div></div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Google Font', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field"><label><?php esc_html_e( 'Font Family', 'noticepulse' ); ?></label><input type="text" name="font_family" value="<?php echo esc_attr( $np_font['family'] ?? '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Poppins, Lato', 'noticepulse' ); ?>"><p class="np-field__desc"><?php esc_html_e( 'Any Google Fonts name. Leave blank for default.', 'noticepulse' ); ?></p></div>
						<div class="np-field"><label><?php esc_html_e( 'Weight', 'noticepulse' ); ?></label><select name="font_weight"><?php foreach ( array( '300'=>'Light 300','400'=>'Regular 400','500'=>'Medium 500','600'=>'SemiBold 600','700'=>'Bold 700','800'=>'ExtraBold 800' ) as $np_w=>$np_wl ) : ?><option value="<?php echo esc_attr($np_w);?>" <?php selected( $np_font['weight']??'500',$np_w);?>><?php echo esc_html($np_wl);?></option><?php endforeach;?></select></div>
					</div>
				</div>

				<div class="np-panel-section">
					<div class="np-info-box">
						💡 <?php esc_html_e( 'Need custom CSS? Use', 'noticepulse' ); ?>
						<a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>" target="_blank"><?php esc_html_e( 'Appearance → Customize → Additional CSS', 'noticepulse' ); ?></a>
						<?php esc_html_e( 'and target', 'noticepulse' ); ?>
						<code>.np-bar[data-bar-id="<?php echo absint( $np_bar->id ?? 0 ); ?>"]</code>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Entrance Animation', 'noticepulse' ); ?></h3>
					<?php $np_cur_anim = $np_anim['type'] ?? 'none'; ?>
					<div class="np-anim-grid">
						<?php foreach ( array( 'none'=>array('▬','None'),'slide'=>array('⬇','Slide'),'fade'=>array('✦','Fade'),'bounce'=>array('↕','Bounce'),'pulse'=>array('⬡','Pulse CTA') ) as $np_ak=>$np_av ) : ?>
						<label class="np-anim-card <?php echo esc_attr( $np_cur_anim === $np_ak ? 'np-anim-card--active' : '' ); ?>"><input type="radio" name="animation_type" value="<?php echo esc_attr($np_ak);?>" <?php checked($np_cur_anim,$np_ak);?> class="np-anim-radio"><span class="np-anim-icon"><?php echo esc_html( $np_av[0] ) ?></span><span class="np-anim-label"><?php echo esc_html($np_av[1]);?></span></label>
						<?php endforeach; ?>
					</div>
					<div class="np-field" id="np-anim-speed-row" style="<?php echo esc_attr( $np_cur_anim === 'none' ? 'display:none' : '' ); ?>"><label><?php esc_html_e('Speed','noticepulse');?></label><div class="np-radio-inline"><label><input type="radio" name="animation_speed" value="slow"   <?php checked($np_anim['speed']??'normal','slow');?>><?php esc_html_e('Slow','noticepulse');?></label><label><input type="radio" name="animation_speed" value="normal" <?php checked($np_anim['speed']??'normal','normal');?>><?php esc_html_e('Normal','noticepulse');?></label><label><input type="radio" name="animation_speed" value="fast"   <?php checked($np_anim['speed']??'normal','fast');?>><?php esc_html_e('Fast','noticepulse');?></label></div></div>
				</div>

			</div><!-- /#np-tab-design -->

			<!-- ══════════════════════════════════════════════════════════ -->
			<!-- TAB: DISPLAY                                               -->
			<!-- ══════════════════════════════════════════════════════════ -->
			<div class="np-tab-panel" id="np-tab-display" data-panel="display">

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Position & Behaviour', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field"><label><?php esc_html_e('Position','noticepulse');?></label><div class="np-radio-inline"><label><input type="radio" name="position" value="top" <?php checked($np_d['position'],'top');?>>↑ <?php esc_html_e('Top','noticepulse');?></label><label><input type="radio" name="position" value="bottom" <?php checked($np_d['position'],'bottom');?>>↓ <?php esc_html_e('Bottom','noticepulse');?></label></div></div>
						<div class="np-field"><label><?php esc_html_e('Behaviour','noticepulse');?></label><div class="np-checks-col"><label><input type="checkbox" name="is_sticky" value="1" <?php checked($np_d['is_sticky'],1);?>><?php esc_html_e('Sticky (stays while scrolling)','noticepulse');?></label><label><input type="checkbox" name="show_close" value="1" <?php checked($np_d['show_close'],1);?>><?php esc_html_e('Show close button','noticepulse');?></label></div></div>
					</div>
					<div class="np-field"><label><?php esc_html_e('Hide after dismissal for (days)','noticepulse');?></label><input type="number" name="cookie_days" value="<?php echo esc_attr($np_d['cookie_days']);?>" min="0" max="365" class="small-text"><p class="np-field__desc"><?php esc_html_e('0 = always show after reload.','noticepulse');?></p></div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Display Frequency', 'noticepulse' ); ?></h3>
					<?php $np_cur_freq = $np_freq['type'] ?? 'session'; ?>
					<div class="np-freq-list">
						<?php foreach ( array( 'session'=>array('⭕',__('Once per session','noticepulse'),__('Show once per browser session','noticepulse')),'always'=>array('▶',__('Always','noticepulse'),__('Show on every page load','noticepulse')),'once'=>array('✓',__('Once ever','noticepulse'),__('Show once, never again','noticepulse')),'pageviews'=>array('📊',__('Every N page views','noticepulse'),__('Show again after N pages','noticepulse')) ) as $np_fk=>$np_fv ) : ?>
						<label class="np-freq-item <?php echo esc_attr( $np_cur_freq === $np_fk ? 'np-freq-item--active' : '' ); ?>"><input type="radio" name="frequency_type" value="<?php echo esc_attr($np_fk);?>" <?php checked($np_cur_freq,$np_fk);?> class="np-freq-radio"><span class="np-freq-icon"><?php echo esc_html( $np_fv[0] ) ?></span><div><strong><?php echo esc_html($np_fv[1]);?></strong><small><?php echo esc_html($np_fv[2]);?></small></div></label>
						<?php endforeach; ?>
					</div>
					<div class="np-field" id="np-freq-n-row" style="<?php echo esc_attr( $np_cur_freq !== 'pageviews' ? 'display:none' : '' ); ?>"><label><?php esc_html_e('Show every','noticepulse');?> <input type="number" name="frequency_value" value="<?php echo esc_attr($np_freq['value']??5);?>" min="1" max="999" class="small-text"> <?php esc_html_e('page views','noticepulse');?></label></div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Devices', 'noticepulse' ); ?></h3>
					<div class="np-checks-row">
						<label class="np-device-check"><input type="checkbox" name="show_desktop" value="1" <?php checked($np_d['show_desktop'],1);?>><span>🖥</span><?php esc_html_e('Desktop','noticepulse');?></label>
						<label class="np-device-check"><input type="checkbox" name="show_tablet"  value="1" <?php checked($np_d['show_tablet'],1);?>><span>📱</span><?php esc_html_e('Tablet','noticepulse');?></label>
						<label class="np-device-check"><input type="checkbox" name="show_mobile"  value="1" <?php checked($np_d['show_mobile'],1);?>><span>📲</span><?php esc_html_e('Mobile','noticepulse');?></label>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Page Visibility', 'noticepulse' ); ?></h3>
					<div class="np-radio-inline"><label><input type="radio" name="visibility" value="all" <?php checked($np_d['visibility'],'all');?>><?php esc_html_e('All pages','noticepulse');?></label><label><input type="radio" name="visibility" value="specific" <?php checked($np_d['visibility'],'specific');?>><?php esc_html_e('Specific pages','noticepulse');?></label></div>
					<div class="np-specific-pages" <?php echo 'specific'!==$np_d['visibility']?'style="display:none"':'';?>><input type="text" name="page_ids" value="<?php echo esc_attr($np_d['page_ids']);?>" class="large-text" placeholder="<?php esc_attr_e('12, 45, 89 (comma-separated IDs)','noticepulse');?>"></div>
					<div class="np-field" style="margin-top:12px"><label><?php esc_html_e('Show To','noticepulse');?></label><select name="user_status"><option value="all" <?php selected($np_d['user_status'],'all');?>><?php esc_html_e('All visitors','noticepulse');?></option><option value="logged_in" <?php selected($np_d['user_status'],'logged_in');?>><?php esc_html_e('Logged-in only','noticepulse');?></option><option value="logged_out" <?php selected($np_d['user_status'],'logged_out');?>><?php esc_html_e('Logged-out only','noticepulse');?></option></select></div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Scheduling', 'noticepulse' ); ?></h3>
					<p class="np-section-desc"><?php esc_html_e('Leave blank to show always.','noticepulse');?></p>
					<div class="np-field-row">
						<div class="np-field"><label><?php esc_html_e('Start','noticepulse');?></label><input type="datetime-local" name="date_start" value="<?php echo esc_attr($np_d['date_start']?gmdate('Y-m-d\TH:i',strtotime($np_d['date_start'])):'');?>"></div>
						<div class="np-field"><label><?php esc_html_e('End','noticepulse');?></label><input type="datetime-local" name="date_end" value="<?php echo esc_attr($np_d['date_end']?gmdate('Y-m-d\TH:i',strtotime($np_d['date_end'])):'');?>"></div>
					</div>
				</div>

			</div><!-- /#np-tab-display -->

			<!-- ══════════════════════════════════════════════════════════ -->
			<!-- TAB: TRIGGERS                                              -->
			<!-- ══════════════════════════════════════════════════════════ -->
			<div class="np-tab-panel" id="np-tab-triggers" data-panel="triggers">
				<div class="np-panel-section">
					<h3 class="np-section-title"><?php esc_html_e( 'Display Trigger', 'noticepulse' ); ?></h3>
					<p class="np-section-desc"><?php esc_html_e( 'When does the bar appear? Default is immediately.', 'noticepulse' ); ?></p>
					<?php $np_cur_trig = $np_triggers['type'] ?? 'immediate'; ?>
					<div class="np-trigger-grid">
						<?php foreach ( array( 'immediate'=>array('⚡',__('Immediate','noticepulse'),__('Show as soon as page loads','noticepulse')),'delay'=>array('⏳',__('Time Delay','noticepulse'),__('Show after N seconds','noticepulse')),'scroll'=>array('📜',__('Scroll Depth','noticepulse'),__('Show when visitor scrolls N%','noticepulse')),'exit_intent'=>array('🚪',__('Exit Intent','noticepulse'),__('Show when visitor moves to leave','noticepulse')) ) as $np_tk=>$np_tv ) : ?>
						<label class="np-trigger-card <?php echo esc_attr( $np_cur_trig === $np_tk ? 'np-trigger-card--active' : '' ); ?>"><input type="radio" name="trigger_type" value="<?php echo esc_attr($np_tk);?>" <?php checked($np_cur_trig,$np_tk);?> class="np-trigger-radio"><span class="np-trigger-icon"><?php echo esc_html( $np_tv[0] ) ?></span><strong><?php echo esc_html($np_tv[1]);?></strong><small><?php echo esc_html($np_tv[2]);?></small></label>
						<?php endforeach; ?>
					</div>
					<div id="np-trigger-delay-row" class="np-conditional-fields" style="<?php echo esc_attr( $np_cur_trig !== 'delay' ? 'display:none' : '' ); ?>"><div class="np-field"><label><?php esc_html_e('Show after','noticepulse');?> <input type="number" name="trigger_delay_seconds" value="<?php echo esc_attr($np_triggers['delay_seconds']??3);?>" min="1" max="120" class="small-text"> <?php esc_html_e('seconds','noticepulse');?></label></div></div>
					<div id="np-trigger-scroll-row" class="np-conditional-fields" style="<?php echo esc_attr( $np_cur_trig !== 'scroll' ? 'display:none' : '' ); ?>"><div class="np-field"><label><?php esc_html_e('Show when visitor scrolls','noticepulse');?> <input type="number" name="trigger_scroll_pct" value="<?php echo esc_attr($np_triggers['scroll_pct']??30);?>" min="5" max="95" class="small-text"> <?php esc_html_e('% of page','noticepulse');?></label></div></div>
				</div>
			</div><!-- /#np-tab-triggers -->

			<!-- ══════════════════════════════════════════════════════════ -->
			<!-- TAB: ADVANCED                                              -->
			<!-- ══════════════════════════════════════════════════════════ -->
			<div class="np-tab-panel" id="np-tab-advanced" data-panel="advanced">

				<div class="np-panel-section">
					<h3 class="np-section-title">🧪 <?php esc_html_e( 'A/B Testing', 'noticepulse' ); ?></h3>
					<div class="np-toggle-row"><label class="np-toggle-switch-label"><input type="checkbox" name="ab_enabled" value="1" id="np-ab-toggle" <?php checked($np_ab['enabled']??0,1);?>><span class="np-toggle-pill"></span><?php esc_html_e('Enable A/B test (50/50 split)','noticepulse');?></label></div>
					<div id="np-ab-fields" class="np-conditional-fields" <?php echo empty($np_ab['enabled'])?'style="display:none"':'';?>>
						<div class="np-info-box">📌 <?php esc_html_e('Variant A = the message above. Enter Variant B here.','noticepulse');?></div>
						<div class="np-field"><label><?php esc_html_e('Variant B Message','noticepulse');?></label><textarea name="ab_message_b" rows="2" class="large-text"><?php echo esc_textarea($np_ab['message_b']??'');?></textarea></div>
						<div class="np-field-row"><div class="np-field"><label><?php esc_html_e('Variant B CTA Label','noticepulse');?></label><input type="text" name="ab_cta_label_b" value="<?php echo esc_attr($np_ab['cta_label_b']??'');?>"></div><div class="np-field"><label><?php esc_html_e('Variant B CTA URL','noticepulse');?></label><input type="url" name="ab_cta_url_b" value="<?php echo esc_url($np_ab['cta_url_b']??'');?>"></div></div>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title">🌍 <?php esc_html_e( 'Geo-Targeting', 'noticepulse' ); ?></h3>
					<div class="np-toggle-row"><label class="np-toggle-switch-label"><input type="checkbox" name="geo_enabled" value="1" id="np-geo-toggle" <?php checked($np_geo['enabled']??0,1);?>><span class="np-toggle-pill"></span><?php esc_html_e('Enable country targeting','noticepulse');?></label></div>
					<div id="np-geo-fields" class="np-conditional-fields" <?php echo empty($np_geo['enabled'])?'style="display:none"':'';?>>
						<div class="np-field"><label><?php esc_html_e('Rule','noticepulse');?></label><div class="np-radio-inline"><label><input type="radio" name="geo_mode" value="include" <?php checked($np_geo['mode']??'include','include');?>><?php esc_html_e('Show ONLY to listed countries','noticepulse');?></label><label><input type="radio" name="geo_mode" value="exclude" <?php checked($np_geo['mode']??'include','exclude');?>><?php esc_html_e('Hide from listed countries','noticepulse');?></label></div></div>
						<div class="np-field"><label><?php esc_html_e('Country Codes','noticepulse');?></label><input type="text" name="geo_countries" value="<?php echo esc_attr($np_geo['countries']??'');?>" class="large-text" placeholder="US, GB, CA, AU"></div>
					</div>
				</div>

				<div class="np-panel-section">
					<h3 class="np-section-title">📈 <?php esc_html_e( 'Google Analytics 4', 'noticepulse' ); ?></h3>
					<div class="np-field-row">
						<div class="np-field"><label><?php esc_html_e('Measurement ID','noticepulse');?></label><input type="text" name="ga4_measurement_id" value="<?php echo esc_attr($np_ga4['measurement_id']??'');?>" placeholder="G-XXXXXXXXXX" class="regular-text"></div>
						<div class="np-field"><label><?php esc_html_e('Event Name','noticepulse');?></label><input type="text" name="ga4_event_name" value="<?php echo esc_attr($np_ga4['event_name']??'noticepulse_cta_click');?>" class="regular-text"><p class="np-field__desc"><?php esc_html_e('Fired in GA4 when visitor clicks CTA.','noticepulse');?></p></div>
					</div>
				</div>

				<?php do_action( 'noticepulse_admin_edit_bar_sections', $bar ); ?>

			</div><!-- /#np-tab-advanced -->

		</div><!-- /.np-edit-main -->

		<!-- ── SIDEBAR ──────────────────────────────────────────────────── -->
		<div class="np-edit-sidebar">
			<div class="np-sidebar-card np-sidebar-card--publish">
				<div class="np-sidebar-card__title"><?php esc_html_e( 'Publish', 'noticepulse' ); ?></div>
				<div class="np-publish-toggle"><label class="np-toggle-switch-label"><input type="checkbox" name="is_active" value="1" id="np-is-active" <?php checked($np_d['is_active'],1);?>><span class="np-toggle-pill"></span><span><?php esc_html_e('Bar is Active','noticepulse');?></span></label></div>
				<button type="submit" class="np-publish-btn"><?php echo $is_new?esc_html__('Publish Bar','noticepulse'):esc_html__('Update Bar','noticepulse');?></button>
				<?php if ( ! $is_new ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=noticepulse_delete_bar&bar_id=' . $np_bar_id ), 'noticepulse_delete_bar_' . $np_bar_id ) ); ?>" class="np-delete-link np-confirm-delete"><?php esc_html_e('Delete this bar','noticepulse');?></a>
				<?php endif; ?>
			</div>

			<div class="np-sidebar-card np-sidebar-card--preview">
				<div class="np-sidebar-card__title"><?php esc_html_e('Live Preview','noticepulse');?> <span class="np-preview-position" id="np-preview-pos"><?php echo 'bottom'===$np_d['position']?'↓ Bottom':'↑ Top';?></span></div>
				<div class="np-preview-shell">
					<div id="np-live-preview" class="np-preview-bar">
						<div class="np-preview-bar__content">
							<span id="np-preview-message"><?php echo esc_html($np_d['message']?wp_strip_all_tags($np_d['message']):__('Your bar message appears here.','noticepulse'));?></span>
							<span id="np-preview-cta" class="np-preview-bar__cta" <?php echo empty($np_d['cta_label'])?'style="display:none"':'';?>><?php echo esc_html($np_d['cta_label']);?></span>
						</div>
						<span class="np-preview-bar__close">×</span>
					</div>
				</div>
				<p class="np-sidebar-card__desc"><?php esc_html_e('Updates as you type.','noticepulse');?></p>
			</div>

			<?php if ( ! $is_new ) :
				$np_s   = NoticePulse_Analytics::get_bar_stats( $np_bar_id );
				$np_si  = (int) $np_s['impressions'];
				$np_sc  = (int) $np_s['clicks'];
				$np_sr  = $np_si > 0 ? round( ( $np_sc / $np_si ) * 100, 1 ) : 0;
			?>
			<div class="np-sidebar-card np-sidebar-card--stats">
				<div class="np-sidebar-card__title"><?php esc_html_e('Performance','noticepulse');?></div>
				<div class="np-sidebar-stats">
					<div class="np-sstat"><span class="np-sstat__val"><?php echo esc_html(number_format_i18n($np_si));?></span><span class="np-sstat__lbl"><?php esc_html_e('Views','noticepulse');?></span></div>
					<div class="np-sstat"><span class="np-sstat__val"><?php echo esc_html(number_format_i18n($np_sc));?></span><span class="np-sstat__lbl"><?php esc_html_e('Clicks','noticepulse');?></span></div>
					<div class="np-sstat"><span class="np-sstat__val <?php echo $np_sr>=5?'np-sstat__val--good':'';?>"><?php echo esc_html($np_sr);?>%</span><span class="np-sstat__lbl">CTR</span></div>
				</div>
				<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=noticepulse_reset_stats&bar_id='.$np_bar_id),'noticepulse_reset_stats_'.$np_bar_id));?>" class="np-reset-stats-link np-confirm-reset"><?php esc_html_e('Reset analytics','noticepulse');?></a>
			</div>
			<?php endif; ?>

		</div><!-- /.np-edit-sidebar -->
	</div><!-- /.np-edit-layout -->
</form>
<?php include NOTICEPULSE_PLUGIN_DIR . 'admin/views/_templates-modal.php'; ?>
</div><!-- /.np-wrap -->