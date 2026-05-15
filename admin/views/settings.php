<?php
/**
 * Admin view: Settings & Tools (Export / Import / Danger Zone).
 *
 * Dark redesign — uses the same design tokens, layout patterns
 * and component classes as the edit-bar and dashboard pages.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$np_notice = isset( $_GET['np_notice'] ) ? sanitize_key( $_GET['np_notice'] ) : '';
?>

<div class="wrap np-wrap np-wrap--v2">

	<!-- ── PAGE HEADER ───────────────────────────────────────────────────── -->
	<div class="np2-hero">
		<div class="np2-hero__left">
			<div class="np2-hero__logo">
				<span style="font-size:22px; line-height:1;">⚙️</span>
			</div>
			<div>
				<div class="np2-hero__title">
					<h1><?php esc_html_e( 'Settings & Tools', 'noticepulse' ); ?></h1>
					<span class="np2-version-badge">NoticePulse</span>
				</div>
				<p style="margin:4px 0 0; font-size:12px; color:var(--np-muted);">
					<?php esc_html_e( 'Export bars, import backups, and manage your data.', 'noticepulse' ); ?>
				</p>
			</div>
		</div>
		<div class="np2-hero__right">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse' ) ); ?>"
			   class="np2-nav-btn">
				← <?php esc_html_e( 'All Bars', 'noticepulse' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse-analytics' ) ); ?>"
			   class="np2-nav-btn">
				📊 <?php esc_html_e( 'Analytics', 'noticepulse' ); ?>
			</a>
		</div>
	</div>

	<!-- ── NOTICE ────────────────────────────────────────────────────────── -->
	<?php if ( $np_notice ) : ?>
		<?php $this->render_notice( $np_notice ); ?>
	<?php endif; ?>

	<!-- ── SETTINGS GRID ─────────────────────────────────────────────────── -->
	<div class="np-settings-grid">

		<!-- LEFT COLUMN -->
		<div class="np-settings-col">

			<!-- Export -->
			<div class="np-settings-card">
				<div class="np-settings-card__header">
					<div class="np-settings-card__icon np-settings-card__icon--export">📤</div>
					<div>
						<h2 class="np-settings-card__title">
							<?php esc_html_e( 'Export Bars', 'noticepulse' ); ?>
						</h2>
						<p class="np-settings-card__desc">
							<?php esc_html_e( 'Download all your notification bars as a JSON file. Perfect for backups or migrating to another site.', 'noticepulse' ); ?>
						</p>
					</div>
				</div>
				<div class="np-settings-card__body">
					<div class="np-settings-info-row">
						<span class="np-settings-info-item">
							<span class="np-settings-info-dot np-settings-info-dot--green"></span>
							<?php esc_html_e( 'Includes all bar settings', 'noticepulse' ); ?>
						</span>
						<span class="np-settings-info-item">
							<span class="np-settings-info-dot np-settings-info-dot--green"></span>
							<?php esc_html_e( 'Safe to re-import', 'noticepulse' ); ?>
						</span>
						<span class="np-settings-info-item">
							<span class="np-settings-info-dot np-settings-info-dot--blue"></span>
							<?php esc_html_e( 'Does not include analytics', 'noticepulse' ); ?>
						</span>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="noticepulse_export">
						<?php wp_nonce_field( 'noticepulse_export', 'noticepulse_export_nonce' ); ?>
						<button type="submit" class="np-settings-btn np-settings-btn--primary">
							<span>📥</span>
							<?php esc_html_e( 'Download Export JSON', 'noticepulse' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- Import -->
			<div class="np-settings-card">
				<div class="np-settings-card__header">
					<div class="np-settings-card__icon np-settings-card__icon--import">📂</div>
					<div>
						<h2 class="np-settings-card__title">
							<?php esc_html_e( 'Import Bars', 'noticepulse' ); ?>
						</h2>
						<p class="np-settings-card__desc">
							<?php esc_html_e( 'Restore bars from a previously exported NoticePulse JSON file. Your existing bars will not be affected.', 'noticepulse' ); ?>
						</p>
					</div>
				</div>
				<div class="np-settings-card__body">
					<div class="np-settings-info-row">
						<span class="np-settings-info-item">
							<span class="np-settings-info-dot np-settings-info-dot--green"></span>
							<?php esc_html_e( 'Existing bars are kept', 'noticepulse' ); ?>
						</span>
						<span class="np-settings-info-item">
							<span class="np-settings-info-dot np-settings-info-dot--blue"></span>
							<?php esc_html_e( 'Only .json files accepted', 'noticepulse' ); ?>
						</span>
					</div>
					<form method="post"
					      action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					      enctype="multipart/form-data"
					      class="np-import-form"
					      id="np-import-form">
						<input type="hidden" name="action" value="noticepulse_import">
						<?php wp_nonce_field( 'noticepulse_import', 'noticepulse_import_nonce' ); ?>

						<!-- File drop zone -->
						<label for="np-import-file" class="np-dropzone" id="np-dropzone">
							<span class="np-dropzone__icon">📄</span>
							<span class="np-dropzone__label" id="np-dropzone-label">
								<?php esc_html_e( 'Drop your JSON file here or click to browse', 'noticepulse' ); ?>
							</span>
							<span class="np-dropzone__hint">
								<?php esc_html_e( 'noticepulse-export-*.json', 'noticepulse' ); ?>
							</span>
							<input type="file"
							       id="np-import-file"
							       name="np_import_file"
							       accept=".json"
							       required
							       class="np-dropzone__input">
						</label>

						<button type="submit" class="np-settings-btn np-settings-btn--primary" id="np-import-btn" disabled>
							<span>📤</span>
							<?php esc_html_e( 'Import Bars', 'noticepulse' ); ?>
						</button>
					</form>
				</div>
			</div>
			<!-- Support card -->
			<div class="np-settings-card np-settings-card--support">
				<div class="np-settings-card__body np-support-body">
					<div class="np-support-heart">❤️</div>
					<div class="np-support-text">
						<strong><?php esc_html_e( 'Enjoying NoticePulse?', 'noticepulse' ); ?></strong>
						<span><?php esc_html_e( "It's free &mdash; and always will be. If it helps your site, a small donation keeps development going.", 'noticepulse' ); ?></span>
					</div>
					<div class="np-support-actions">
						<a href="https://wise.com/pay/me/mdrayhanu2"
						   target="_blank"
						   rel="noopener noreferrer"
						   class="np-support-btn">
							<?php esc_html_e( 'Support this plugin', 'noticepulse' ); ?> ❤️
						</a>
						<a href="https://wordpress.org/support/plugin/noticepulse/reviews/#new-post"
						   target="_blank"
						   rel="noopener noreferrer"
						   class="np-support-review">
							★ <?php esc_html_e( 'Leave a review', 'noticepulse' ); ?>
						</a>
					</div>
				</div>
			</div>

		</div><!-- /left column -->

		<!-- RIGHT COLUMN -->
		<div class="np-settings-col">

			<!-- Plugin info card -->
			<div class="np-settings-card np-settings-card--info">
				<div class="np-settings-card__header">
					<div class="np-settings-card__icon np-settings-card__icon--info">🔔</div>
					<div>
						<h2 class="np-settings-card__title">
							<?php esc_html_e( 'Plugin Info', 'noticepulse' ); ?>
						</h2>
					</div>
				</div>
				<div class="np-settings-card__body">
					<dl class="np-info-list">
						<div class="np-info-row">
							<dt><?php esc_html_e( 'Plugin Version', 'noticepulse' ); ?></dt>
							<dd><span class="np-mono-badge"><?php echo esc_html( NOTICEPULSE_VERSION ); ?></span></dd>
						</div>
						<div class="np-info-row">
							<dt><?php esc_html_e( 'WordPress', 'noticepulse' ); ?></dt>
							<dd><span class="np-mono-badge"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span></dd>
						</div>
						<div class="np-info-row">
							<dt><?php esc_html_e( 'PHP Version', 'noticepulse' ); ?></dt>
							<dd><span class="np-mono-badge"><?php echo esc_html( PHP_VERSION ); ?></span></dd>
						</div>
						<div class="np-info-row">
							<dt><?php esc_html_e( 'Total Bars', 'noticepulse' ); ?></dt>
							<dd>
								<span class="np-mono-badge">
									<?php
									$np_bars_count = class_exists( 'NoticePulse_DB' )
										? count( NoticePulse_DB::get_all_bars() )
										: 0;
									echo esc_html( $np_bars_count );
									?>
								</span>
							</dd>
						</div>
						<div class="np-info-row">
							<dt><?php esc_html_e( 'Database Tables', 'noticepulse' ); ?></dt>
							<dd><span class="np-status-dot np-status-dot--ok"></span> <?php esc_html_e( 'Installed', 'noticepulse' ); ?></dd>
						</div>
					</dl>
				</div>
			</div>

			<!-- Quick links -->
			<div class="np-settings-card">
				<div class="np-settings-card__header">
					<div class="np-settings-card__icon np-settings-card__icon--links">🔗</div>
					<div>
						<h2 class="np-settings-card__title">
							<?php esc_html_e( 'Quick Actions', 'noticepulse' ); ?>
						</h2>
					</div>
				</div>
				<div class="np-settings-card__body">
					<div class="np-quick-links">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse&action=new' ) ); ?>"
						   class="np-quick-link">
							<span class="np-quick-link__icon">➕</span>
							<div>
								<strong><?php esc_html_e( 'Create New Bar', 'noticepulse' ); ?></strong>
								<span><?php esc_html_e( 'Add a notification bar', 'noticepulse' ); ?></span>
							</div>
							<span class="np-quick-link__arrow">→</span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse-analytics' ) ); ?>"
						   class="np-quick-link">
							<span class="np-quick-link__icon">📊</span>
							<div>
								<strong><?php esc_html_e( 'View Analytics', 'noticepulse' ); ?></strong>
								<span><?php esc_html_e( 'Impressions, clicks, CTR', 'noticepulse' ); ?></span>
							</div>
							<span class="np-quick-link__arrow">→</span>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=noticepulse' ) ); ?>"
						   class="np-quick-link">
							<span class="np-quick-link__icon">📋</span>
							<div>
								<strong><?php esc_html_e( 'Manage Bars', 'noticepulse' ); ?></strong>
								<span><?php esc_html_e( 'Edit, enable, or delete bars', 'noticepulse' ); ?></span>
							</div>
							<span class="np-quick-link__arrow">→</span>
						</a>
					</div>
				</div>
			</div>

			<!-- Danger Zone -->
			<div class="np-settings-card np-settings-card--danger">
				<div class="np-settings-card__header">
					<div class="np-settings-card__icon np-settings-card__icon--danger">⚠️</div>
					<div>
						<h2 class="np-settings-card__title np-settings-card__title--danger">
							<?php esc_html_e( 'Danger Zone', 'noticepulse' ); ?>
						</h2>
						<p class="np-settings-card__desc">
							<?php esc_html_e( 'Permanently delete all notification bars and analytics data. This cannot be undone.', 'noticepulse' ); ?>
						</p>
					</div>
				</div>
				<div class="np-settings-card__body">
					<div class="np-danger-warning">
						<span>🔴</span>
						<?php esc_html_e( 'This will permanently erase ALL bars, leads, and analytics. Make sure to export a backup first.', 'noticepulse' ); ?>
					</div>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=noticepulse_reset_all' ), 'noticepulse_reset_all' ) ); ?>"
					   class="np-settings-btn np-settings-btn--danger np-confirm-reset-all">
						<span>🗑</span>
						<?php esc_html_e( 'Delete All Data', 'noticepulse' ); ?>
					</a>
				</div>
			</div>
		</div><!-- /right column -->

	</div><!-- /.np-settings-grid -->

</div><!-- /.np-wrap -->