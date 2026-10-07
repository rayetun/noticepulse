<?php
/**
 * NoticePulse — Template Library Modal.
 *
 * SINGLE SOURCE OF TRUTH (since 2.2.0): the cards are generated directly from
 * NoticePulse_Templates::get_all_templates(). Each card's preview colours,
 * message and CTA come from get_card_preview() — derived from the same fields
 * that applying the template injects — so the card always matches the result.
 * (Previously a second hand-maintained registry lived here and drifted.)
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$np_templates = class_exists( 'NoticePulse_Templates' ) ? NoticePulse_Templates::get_all_templates() : array();
$np_tpl_count = count( $np_templates );

// Curated category labels (display only — not template data, so no drift risk).
$np_categories = array(
	'all'           => __( 'All', 'noticepulse' ),
	'announcement'  => '📢 ' . __( 'Announcement', 'noticepulse' ),
	'gdpr'          => '🍪 ' . __( 'GDPR', 'noticepulse' ),
	'countdown'     => '⏳ ' . __( 'Countdown', 'noticepulse' ),
	'email_capture' => '📧 ' . __( 'Email', 'noticepulse' ),
	'coupon_copy'   => '🎟 ' . __( 'Coupon', 'noticepulse' ),
	'click_to_call' => '📞 ' . __( 'Call', 'noticepulse' ),
	'ticker'        => '🎠 ' . __( 'Carousel', 'noticepulse' ),
);
?>

<!-- ── TEMPLATE LIBRARY MODAL ──────────────────────────────────────────── -->
<div id="np-template-overlay" class="np-tmpl-overlay" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Template Library', 'noticepulse' ); ?>" style="display:none;">
	<div class="np-tmpl-modal">

		<div class="np-tmpl-header">
			<div class="np-tmpl-header__left">
				<span class="np-tmpl-header__icon">🎨</span>
				<div>
					<h2 class="np-tmpl-header__title"><?php esc_html_e( 'Template Library', 'noticepulse' ); ?></h2>
					<p class="np-tmpl-header__sub">
						<?php
						/* translators: %d: number of ready-made templates. */
						echo esc_html( sprintf( _n( '%d ready-made template — pick one to pre-fill your bar instantly.', '%d ready-made templates — pick one to pre-fill your bar instantly.', $np_tpl_count, 'noticepulse' ), $np_tpl_count ) );
						?>
					</p>
				</div>
			</div>
			<button type="button" class="np-tmpl-close" id="np-tmpl-close" aria-label="<?php esc_attr_e( 'Close', 'noticepulse' ); ?>">✕</button>
		</div>

		<!-- Category tabs -->
		<div class="np-tmpl-cats" id="np-tmpl-cats">
			<?php foreach ( $np_categories as $np_cat_key => $np_cat_label ) : ?>
			<button type="button"
			        class="np-tmpl-cat<?php echo 'all' === $np_cat_key ? ' np-tmpl-cat--active' : ''; ?>"
			        data-cat="<?php echo esc_attr( $np_cat_key ); ?>">
				<?php echo esc_html( $np_cat_label ); ?>
			</button>
			<?php endforeach; ?>
		</div>

		<!-- Template grid -->
		<div class="np-tmpl-grid" id="np-tmpl-grid">
			<?php
			foreach ( $np_templates as $np_tpl ) :
				$np_p    = NoticePulse_Templates::get_card_preview( $np_tpl );
				$np_type = $np_p['type'];
			?>
			<div class="np-tmpl-card"
			     data-id="<?php echo esc_attr( $np_tpl['id'] ); ?>"
			     data-cat="<?php echo esc_attr( isset( $np_tpl['category'] ) ? $np_tpl['category'] : $np_type ); ?>"
			     data-type="<?php echo esc_attr( $np_type ); ?>"
			     data-label="<?php echo esc_attr( $np_tpl['name'] ); ?>">

				<!-- Mini bar preview (derived from the template's real fields) -->
				<div class="np-tmpl-preview" style="background:<?php echo esc_attr( $np_p['bg'] ); ?>;">
					<span class="np-tmpl-preview__msg" style="color:<?php echo esc_attr( $np_p['text'] ); ?>;">
						<?php echo esc_html( $np_p['message'] ); ?>
					</span>
					<?php if ( '' !== $np_p['cta'] ) : ?>
					<span class="np-tmpl-preview__btn" style="background:<?php echo esc_attr( $np_p['btn'] ); ?>; color:<?php echo esc_attr( $np_p['btn_text'] ); ?>;">
						<?php echo esc_html( $np_p['cta'] ); ?>
					</span>
					<?php endif; ?>
				</div>

				<!-- Footer -->
				<div class="np-tmpl-card__footer">
					<div class="np-tmpl-card__meta">
						<span class="np-tmpl-card__emoji"><?php echo esc_html( isset( $np_tpl['emoji'] ) ? $np_tpl['emoji'] : '🎨' ); ?></span>
						<div>
							<div class="np-tmpl-card__name"><?php echo esc_html( $np_tpl['name'] ); ?></div>
							<div class="np-tmpl-card__type"><?php echo esc_html( ucwords( str_replace( '_', ' ', $np_type ) ) ); ?></div>
						</div>
					</div>
					<button type="button"
					        class="np-tmpl-apply-btn"
					        data-id="<?php echo esc_attr( $np_tpl['id'] ); ?>"
					        data-label="<?php echo esc_attr( $np_tpl['name'] ); ?>">
						<?php esc_html_e( 'Use Template', 'noticepulse' ); ?>
					</button>
				</div>

			</div>
			<?php endforeach; ?>
		</div>

		<div class="np-tmpl-empty" id="np-tmpl-empty" style="display:none;">
			<span>🔍</span>
			<p><?php esc_html_e( 'No templates in this category.', 'noticepulse' ); ?></p>
		</div>

	</div>
</div>

<!-- ── CONFIRM DIALOG ────────────────────────────────────────────────────── -->
<div id="np-tmpl-confirm" class="np-tmpl-confirm-wrap" style="display:none;" role="alertdialog" aria-modal="true">
	<div class="np-tmpl-confirm-box">
		<div class="np-tmpl-confirm-icon">⚠️</div>
		<h3 class="np-tmpl-confirm-title"><?php esc_html_e( 'Replace current settings?', 'noticepulse' ); ?></h3>
		<p class="np-tmpl-confirm-msg">
			<?php esc_html_e( 'Applying', 'noticepulse' ); ?>
			<strong id="np-tmpl-confirm-name"></strong>
			<?php esc_html_e( 'will overwrite your current bar settings. This cannot be undone until you save.', 'noticepulse' ); ?>
		</p>
		<div class="np-tmpl-confirm-actions">
			<button type="button" class="np-btn-confirm-cancel" id="np-tmpl-confirm-cancel">
				<?php esc_html_e( 'Keep Current', 'noticepulse' ); ?>
			</button>
			<button type="button" class="np-btn-confirm-ok" id="np-tmpl-confirm-ok">
				<?php esc_html_e( 'Yes, Apply Template', 'noticepulse' ); ?>
			</button>
		</div>
	</div>
</div>
