<?php
/**
 * Admin view: All Bars — Premium Dashboard.
 *
 * No Pro promotions. No upgrade banners. No bar limit.
 *
 * @package NoticePulse
 * @since   2.1.0
 *
 * @var array  $bars   All bar objects.
 * @var array  $stats  Analytics keyed by bar_id.
 * @var int    $count  Total bars count.
 * @var string $notice Admin notice key.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$np_total_impressions = 0;
$np_total_clicks      = 0;
foreach ( $stats as $s ) {
	$np_total_impressions += (int) $s['impressions'];
	$np_total_clicks      += (int) $s['clicks'];
}
$np_total_ctr = $np_total_impressions > 0
	? round( ( $np_total_clicks / $np_total_impressions ) * 100, 1 )
	: 0;
?>

<div class="wrap np-wrap np-wrap--v2">

<?php if ( $notice ) : $this->render_notice( $notice ); endif; ?>

<!-- ── HERO HEADER ────────────────────────────────────────────────────── -->
<div class="np2-hero">
	<div class="np2-hero__left">
		<div class="np2-hero__logo">
			<svg width="34" height="34" viewBox="0 0 34 34" fill="none">
				<rect width="34" height="34" rx="9" fill="url(#npg)"/>
				<path d="M9 12h16M9 17h12M9 22h14" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
				<circle cx="26" cy="12" r="3.5" fill="#fbbf24"/>
				<defs>
					<linearGradient id="npg" x1="0" y1="0" x2="34" y2="34">
						<stop stop-color="#6d28d9"/>
						<stop offset="1" stop-color="#4f46e5"/>
					</linearGradient>
				</defs>
			</svg>
		</div>
		<div class="np2-hero__title">
			<h1>NoticePulse</h1>
			<span class="np2-version-badge">v<?php echo esc_html( NOTICEPULSE_VERSION ); ?></span>
		</div>
	</div>
	<div class="np2-hero__right">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse-analytics' ) ); ?>" class="np2-nav-btn">
			📊 <?php esc_html_e( 'Analytics', 'noticepulse' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse&action=new' ) ); ?>" class="np2-add-btn">
			+ <?php esc_html_e( 'New Bar', 'noticepulse' ); ?>
		</a>
	</div>
</div>

<!-- ── STAT STRIP ─────────────────────────────────────────────────────── -->
<div class="np2-stats-strip">
	<div class="np2-stat">
		<div class="np2-stat__icon np2-stat__icon--bars">&#9776;</div>
		<div class="np2-stat__body">
			<span class="np2-stat__value"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
			<span class="np2-stat__label"><?php esc_html_e( 'Total Bars', 'noticepulse' ); ?></span>
		</div>
	</div>
	<div class="np2-stat-divider"></div>
	<div class="np2-stat">
		<div class="np2-stat__icon np2-stat__icon--impressions">&#128065;</div>
		<div class="np2-stat__body">
			<span class="np2-stat__value"><?php echo esc_html( number_format_i18n( $np_total_impressions ) ); ?></span>
			<span class="np2-stat__label"><?php esc_html_e( 'Impressions', 'noticepulse' ); ?></span>
		</div>
	</div>
	<div class="np2-stat-divider"></div>
	<div class="np2-stat">
		<div class="np2-stat__icon np2-stat__icon--clicks">&#128070;</div>
		<div class="np2-stat__body">
			<span class="np2-stat__value"><?php echo esc_html( number_format_i18n( $np_total_clicks ) ); ?></span>
			<span class="np2-stat__label"><?php esc_html_e( 'CTA Clicks', 'noticepulse' ); ?></span>
		</div>
	</div>
	<div class="np2-stat-divider"></div>
	<div class="np2-stat">
		<div class="np2-stat__icon np2-stat__icon--ctr">&#128200;</div>
		<div class="np2-stat__body">
			<span class="np2-stat__value"><?php echo esc_html( $np_total_ctr ); ?>%</span>
			<span class="np2-stat__label"><?php esc_html_e( 'Avg. CTR', 'noticepulse' ); ?></span>
		</div>
	</div>
</div>

<!-- ── EMPTY STATE ────────────────────────────────────────────────────── -->
<?php if ( empty( $bars ) ) : ?>
<div class="np2-empty">
	<div class="np2-empty__illustration">
		<svg width="80" height="80" viewBox="0 0 80 80" fill="none">
			<rect width="80" height="80" rx="20" fill="#f5f3ff"/>
			<rect x="14" y="28" width="52" height="10" rx="5" fill="#ddd6fe"/>
			<rect x="14" y="44" width="38" height="10" rx="5" fill="#ede9fe"/>
			<circle cx="60" cy="28" r="8" fill="#6d28d9"/>
			<path d="M57 28h6M60 25v6" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
		</svg>
	</div>
	<h2><?php esc_html_e( 'Create your first notification bar', 'noticepulse' ); ?></h2>
	<p><?php esc_html_e( 'Countdown timers, GDPR notices, exit-intent bars, email capture — all free, all powerful.', 'noticepulse' ); ?></p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse&action=new' ) ); ?>" class="np2-add-btn np2-add-btn--large">
		+ <?php esc_html_e( 'Create Your First Bar', 'noticepulse' ); ?>
	</a>
</div>

<?php else : ?>

<!-- ── BAR CARDS ──────────────────────────────────────────────────────── -->
<div class="np2-bars-grid">

	<?php foreach ( $bars as $bar ) :
		$np_bid         = absint( $bar->id );
		$np_bar_stats   = isset( $stats[ $np_bid ] ) ? $stats[ $np_bid ] : array( 'impressions' => 0, 'clicks' => 0 );
		$np_impressions = (int) $np_bar_stats['impressions'];
		$np_clicks      = (int) $np_bar_stats['clicks'];
		$np_ctr         = $np_impressions > 0 ? round( ( $np_clicks / $np_impressions ) * 100, 1 ) : 0;
		$np_edit_url    = admin_url( 'admin.php?page=noticepulse&action=edit&bar_id=' . $np_bid );
		$np_toggle_url  = wp_nonce_url( admin_url( 'admin-post.php?action=noticepulse_toggle_bar&bar_id=' . $np_bid ), 'noticepulse_toggle_bar_' . $np_bid );
		$np_delete_url  = wp_nonce_url( admin_url( 'admin-post.php?action=noticepulse_delete_bar&bar_id=' . $np_bid ), 'noticepulse_delete_bar_' . $np_bid );
		$np_reset_url   = wp_nonce_url( admin_url( 'admin-post.php?action=noticepulse_reset_stats&bar_id=' . $np_bid ), 'noticepulse_reset_stats_' . $np_bid );
		$np_bg          = sanitize_hex_color( $bar->bg_color ) ?: '#1a73e8';
		$np_fg          = sanitize_hex_color( $bar->text_color ) ?: '#ffffff';
		$np_bbg         = sanitize_hex_color( $bar->btn_bg_color ) ?: '#ffffff';
		$np_bfg         = sanitize_hex_color( $bar->btn_txt_color ) ?: '#1a73e8';

		// Bar type label.
		$np_type_labels = array(
			'standard'      => '📢',
			'gdpr'          => '🍪',
			'ticker'        => '🔄',
			'click_to_call' => '📞',
			'countdown'     => '⏱',
			'email_capture' => '📧',
			'coupon_copy'   => '🎟',
		);
		$np_type_icon = isset( $np_type_labels[ $bar->bar_type ] ) ? $np_type_labels[ $bar->bar_type ] : '📢';

		// Check if gradient is active.
		$np_meta = $bar->bar_meta ? json_decode( $bar->bar_meta, true ) : array();
		$np_grad = isset( $np_meta['gradient']['enabled'] ) && $np_meta['gradient']['enabled'];
		$np_grad_c1 = isset( $np_meta['gradient']['color1'] ) ? $np_meta['gradient']['color1'] : '#6d28d9';
		$np_grad_c2 = isset( $np_meta['gradient']['color2'] ) ? $np_meta['gradient']['color2'] : '#4f46e5';
		$np_preview_bg = $np_grad
			? 'linear-gradient(135deg,' . $np_grad_c1 . ',' . $np_grad_c2 . ')'
			: $np_bg;
	?>
	<div class="np2-bar-card <?php echo esc_attr( $bar->is_active ? 'np2-bar-card--active' : 'np2-bar-card--inactive' ); ?>">

		<!-- Preview strip -->
		<div class="np2-bar-card__preview" style="background:<?php echo esc_attr( $np_preview_bg ); ?>">
			<span class="np2-bar-card__type-icon"><?php echo esc_html( $np_type_icon ); ?></span>
			<span class="np2-bar-card__preview-msg" style="color:<?php echo esc_attr( $np_fg ); ?>">
				<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $bar->message ), 10 ) ); ?>
			</span>
			<?php if ( ! empty( $bar->cta_label ) ) : ?>
				<span class="np2-bar-card__preview-cta"
					style="background:<?php echo esc_attr( $np_bbg ); ?>;color:<?php echo esc_attr( $np_bfg ); ?>">
					<?php echo esc_html( $bar->cta_label ); ?>
				</span>
			<?php endif; ?>
		</div>

		<!-- Card body -->
		<div class="np2-bar-card__body">
			<div class="np2-bar-card__title-row">
				<a class="np2-bar-card__name" href="<?php echo esc_url( $np_edit_url ); ?>">
					<?php echo esc_html( $bar->name ); ?>
				</a>
				<a href="<?php echo esc_url( $np_toggle_url ); ?>"
					class="np2-toggle-switch <?php echo esc_attr( $bar->is_active ? 'np2-toggle-switch--on' : '' ); ?>"
					title="<?php echo $bar->is_active ? esc_attr__( 'Deactivate', 'noticepulse' ) : esc_attr__( 'Activate', 'noticepulse' ); ?>">
					<span class="np2-toggle-switch__knob"></span>
				</a>
			</div>

			<div class="np2-bar-card__meta">
				<span class="np2-pill"><?php echo esc_html( 'bottom' === $bar->position ? '↓' : '↑' ); ?> <?php echo esc_html( ucfirst( $bar->position ) ); ?></span>
				<span class="np2-pill"><?php echo $bar->is_sticky ? esc_html__( 'Sticky', 'noticepulse' ) : esc_html__( 'Static', 'noticepulse' ); ?></span>
				<?php if ( ! empty( $bar->date_end ) ) : ?>
					<span class="np2-pill np2-pill--scheduled">⏰ <?php echo esc_html( gmdate( 'M j', strtotime( $bar->date_end ) ) ); ?></span>
				<?php endif; ?>
			</div>

			<div class="np2-bar-card__analytics">
				<div class="np2-bar-card__stat">
					<span class="np2-bar-card__stat-val"><?php echo esc_html( number_format_i18n( $np_impressions ) ); ?></span>
					<span class="np2-bar-card__stat-lbl"><?php esc_html_e( 'Views', 'noticepulse' ); ?></span>
				</div>
				<div class="np2-bar-card__stat">
					<span class="np2-bar-card__stat-val"><?php echo esc_html( number_format_i18n( $np_clicks ) ); ?></span>
					<span class="np2-bar-card__stat-lbl"><?php esc_html_e( 'Clicks', 'noticepulse' ); ?></span>
				</div>
				<div class="np2-bar-card__stat">
					<span class="np2-bar-card__stat-val <?php echo esc_attr( $np_ctr >= 5 ? 'np2-stat-good' : '' ); ?>">
						<?php echo esc_html( $np_ctr ); ?>%
					</span>
					<span class="np2-bar-card__stat-lbl"><?php esc_html_e( 'CTR', 'noticepulse' ); ?></span>
				</div>
				<div class="np2-bar-card__ctr-bar">
					<div class="np2-bar-card__ctr-fill" style="width:<?php echo esc_attr( min( 100, $np_ctr * 5 ) ); ?>%"></div>
				</div>
			</div>

			<div class="np2-bar-card__actions">
				<a href="<?php echo esc_url( $np_edit_url ); ?>" class="np2-card-action np2-card-action--edit">
					✏️ <?php esc_html_e( 'Edit', 'noticepulse' ); ?>
				</a>
				<a href="<?php echo esc_url( $np_reset_url ); ?>"
					class="np2-card-action np2-card-action--reset np-confirm-reset"
					title="<?php esc_attr_e( 'Reset analytics', 'noticepulse' ); ?>">↺</a>
				<a href="<?php echo esc_url( $np_delete_url ); ?>"
					class="np2-card-action np2-card-action--delete np-confirm-delete"
					title="<?php esc_attr_e( 'Delete', 'noticepulse' ); ?>">🗑</a>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<!-- Ghost "add new" card — always visible, no bar limit -->
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse&action=new' ) ); ?>" class="np2-bar-card np2-bar-card--ghost">
		<div class="np2-bar-card__ghost-inner">
			<span class="np2-ghost-icon">+</span>
			<span><?php esc_html_e( 'Add New Bar', 'noticepulse' ); ?></span>
		</div>
	</a>

</div><!-- /.np2-bars-grid -->
<?php endif; ?>

</div><!-- /.np-wrap -->
