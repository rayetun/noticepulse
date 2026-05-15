<?php
/**
 * NoticePulse — Analytics Admin Page.
 *
 * All PHP variables prefixed with np_ to satisfy PrefixAllGlobals sniff.
 * CSS updated to use --np-* design tokens for consistency with all other pages.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$np_total_leads = class_exists( 'NoticePulse_Analytics_Pro' )
	? NoticePulse_Analytics_Pro::get_leads_count()
	: 0;

$np_export_base = admin_url( 'admin-ajax.php' );
$np_nonce       = wp_create_nonce( 'noticepulse_analytics' );
?>

<div class="wrap np-wrap np-wrap--v2">

	<!-- ── PAGE HEADER (matches dashboard + settings pages) ─────────────── -->
	<div class="np2-hero">
		<div class="np2-hero__left">
			<div class="np2-hero__logo">
				<span style="font-size:22px; line-height:1;">📊</span>
			</div>
			<div>
				<div class="np2-hero__title">
					<h1><?php esc_html_e( 'Analytics', 'noticepulse' ); ?></h1>
					<span class="np2-version-badge">NoticePulse</span>
				</div>
				<p style="margin:4px 0 0; font-size:12px; color:var(--np-muted);">
					<?php esc_html_e( 'Impressions, clicks, and lead capture performance across all bars.', 'noticepulse' ); ?>
				</p>
			</div>
		</div>
		<div class="np2-hero__right">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse' ) ); ?>"
			   class="np2-nav-btn">
				← <?php esc_html_e( 'All Bars', 'noticepulse' ); ?>
			</a>
			<a href="<?php echo esc_url( $np_export_base . '?action=noticepulse_export_csv&nonce=' . $np_nonce ); ?>"
			   class="np2-nav-btn">
				⬇ <?php esc_html_e( 'Export Analytics', 'noticepulse' ); ?>
			</a>
			<a href="<?php echo esc_url( $np_export_base . '?action=noticepulse_export_leads&nonce=' . $np_nonce ); ?>"
			   class="np2-nav-btn">
				⬇ <?php esc_html_e( 'Export Leads', 'noticepulse' ); ?>
			</a>
		</div>
	</div>

	<!-- ── SUMMARY CARDS (reusing np2-stats-strip pattern) ──────────────── -->
	<div class="np2-stats-strip" style="margin-top:14px;">
		<div class="np2-stat">
			<div class="np2-stat__icon np2-stat__icon--impressions">👁</div>
			<div class="np2-stat__body">
				<div class="np2-stat__value" id="np-stat-impressions">—</div>
				<div class="np2-stat__label"><?php esc_html_e( 'Impressions', 'noticepulse' ); ?></div>
			</div>
		</div>
		<div class="np2-stat-divider"></div>
		<div class="np2-stat">
			<div class="np2-stat__icon np2-stat__icon--clicks">🖱</div>
			<div class="np2-stat__body">
				<div class="np2-stat__value" id="np-stat-clicks">—</div>
				<div class="np2-stat__label"><?php esc_html_e( 'Clicks', 'noticepulse' ); ?></div>
			</div>
		</div>
		<div class="np2-stat-divider"></div>
		<div class="np2-stat">
			<div class="np2-stat__icon np2-stat__icon--ctr">📈</div>
			<div class="np2-stat__body">
				<div class="np2-stat__value" id="np-stat-ctr">—</div>
				<div class="np2-stat__label"><?php esc_html_e( 'Avg. CTR', 'noticepulse' ); ?></div>
			</div>
		</div>
		<div class="np2-stat-divider"></div>
		<div class="np2-stat">
			<div class="np2-stat__icon np2-stat__icon--bars">📧</div>
			<div class="np2-stat__body">
				<div class="np2-stat__value"><?php echo esc_html( number_format( $np_total_leads ) ); ?></div>
				<div class="np2-stat__label"><?php esc_html_e( 'Total Leads', 'noticepulse' ); ?></div>
			</div>
		</div>
	</div>

	<!-- ── FILTERS ──────────────────────────────────────────────────────── -->
	<div class="np-an-filters">
		<div class="np-an-range-tabs" id="np-range-tabs">
			<button class="np-an-tab" data-range="7d"><?php esc_html_e( '7 Days', 'noticepulse' ); ?></button>
			<button class="np-an-tab np-an-tab--active" data-range="30d"><?php esc_html_e( '30 Days', 'noticepulse' ); ?></button>
			<button class="np-an-tab" data-range="90d"><?php esc_html_e( '90 Days', 'noticepulse' ); ?></button>
			<button class="np-an-tab" data-range="all"><?php esc_html_e( 'All Time', 'noticepulse' ); ?></button>
		</div>
		<div class="np-an-bar-filter">
			<label for="np-bar-select" class="screen-reader-text"><?php esc_html_e( 'Filter by Bar', 'noticepulse' ); ?></label>
			<select id="np-bar-select" class="np-an-select">
				<option value="0"><?php esc_html_e( '— All Bars —', 'noticepulse' ); ?></option>
				<?php
				$np_bars = NoticePulse_DB::get_all_bars();
				foreach ( $np_bars as $np_bar ) :
				?>
				<option value="<?php echo absint( $np_bar->id ); ?>">
					<?php echo esc_html( $np_bar->name ); ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<!-- ── CHART ────────────────────────────────────────────────────────── -->
	<div class="np-an-card" style="margin-bottom:16px;">
		<div class="np-an-card__header">
			<span class="np-an-card__title"><?php esc_html_e( 'Impressions vs Clicks', 'noticepulse' ); ?></span>
			<div class="np-an-legend">
				<span class="np-an-legend__dot np-an-legend__dot--impressions"></span>
				<?php esc_html_e( 'Impressions', 'noticepulse' ); ?>
				<span class="np-an-legend__dot np-an-legend__dot--clicks"></span>
				<?php esc_html_e( 'Clicks', 'noticepulse' ); ?>
			</div>
		</div>
		<div class="np-an-card__body np-an-card__body--chart">
			<div id="np-chart-loading" class="np-an-loading">
				<div class="np-an-spinner"></div>
				<span><?php esc_html_e( 'Loading chart…', 'noticepulse' ); ?></span>
			</div>
			<canvas id="np-chart" style="display:none;"></canvas>
		</div>
	</div>

	<!-- ── PER-BAR TABLE ─────────────────────────────────────────────────── -->
	<div class="np-an-card" style="margin-bottom:40px;">
		<div class="np-an-card__header">
			<span class="np-an-card__title"><?php esc_html_e( 'Bar Performance', 'noticepulse' ); ?></span>
		</div>

		<div id="np-table-loading" class="np-an-loading">
			<div class="np-an-spinner"></div>
			<span><?php esc_html_e( 'Loading data…', 'noticepulse' ); ?></span>
		</div>

		<table class="np-an-table" id="np-perf-table" style="display:none;">
			<thead>
				<tr>
					<th class="np-col-name"><?php esc_html_e( 'Bar Name', 'noticepulse' ); ?></th>
					<th class="np-col-num"><?php esc_html_e( 'Impressions', 'noticepulse' ); ?></th>
					<th class="np-col-num"><?php esc_html_e( 'Clicks', 'noticepulse' ); ?></th>
					<th class="np-col-num"><?php esc_html_e( 'CTR', 'noticepulse' ); ?></th>
					<th class="np-col-num"><?php esc_html_e( 'Leads', 'noticepulse' ); ?></th>
					<th class="np-col-export"><?php esc_html_e( 'Export', 'noticepulse' ); ?></th>
				</tr>
			</thead>
			<tbody id="np-perf-tbody">
				<!-- JS populated -->
			</tbody>
		</table>

		<div id="np-table-empty" class="np-an-empty" style="display:none;">
			<div class="np-an-empty__icon">📭</div>
			<p><?php esc_html_e( 'No analytics data yet for this period.', 'noticepulse' ); ?></p>
			<p class="np-an-empty__sub"><?php esc_html_e( 'Activate a bar on the frontend to start collecting data.', 'noticepulse' ); ?></p>
		</div>
	</div>

</div><!-- .np-wrap -->

