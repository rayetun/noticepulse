<?php
/**
 * Admin view: Free plan bar limit reached.
 *
 * @package NoticePulse
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap np-wrap">
	<div class="np-header">
		<div class="np-header__brand">
			<span class="dashicons dashicons-megaphone"></span>
			<h1><?php esc_html_e( 'NoticePulse', 'noticepulse' ); ?></h1>
		</div>
	</div>
	<div class="np-limit-page">
		<span class="dashicons dashicons-lock np-limit-icon"></span>
		<h2><?php esc_html_e( 'Free Plan Limit Reached', 'noticepulse' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %d: max bars limit */
				esc_html__( 'You\'ve reached the maximum of %d notification bars on the free plan.', 'noticepulse' ),
				(int) NoticePulse_Features::max_bars()
			);
			?>
		</p>
		<p><?php esc_html_e( 'Upgrade to NoticePulse Pro for unlimited bars, countdown timers, exit-intent triggers, geo-targeting, A/B testing, and much more.', 'noticepulse' ); ?></p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse' ) ); ?>" class="button button-secondary">
			<?php esc_html_e( '&larr; Manage Existing Bars', 'noticepulse' ); ?>
		</a>
	</div>
</div>
