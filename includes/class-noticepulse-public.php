<?php
/**
 * Public-facing functionality for NoticePulse.
 *
 * v2.0.0 adds the hook API that Pro (and free bar types) plug into:
 *
 *   noticepulse_active_bars          — filter eligible bars (Pro: geo/role/AB)
 *   noticepulse_bar_data_attributes  — filter data-* attrs  (Pro: countdown, triggers)
 *   noticepulse_bar_inline_styles    — filter inline style   (Pro: gradients)
 *
 * @package NoticePulse
 * @since   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NoticePulse_Public
 */
class NoticePulse_Public {

	/**
	 * Single instance.
	 *
	 * @var NoticePulse_Public
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return NoticePulse_Public
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts',              array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer',                       array( $this, 'render_bars' ) );
		add_action( 'wp_ajax_nopriv_noticepulse_track', array( $this, 'handle_tracking' ) );
		add_action( 'wp_ajax_noticepulse_track',        array( $this, 'handle_tracking' ) );
	}

	/**
	 * Enqueue public assets — only when bars are actually visible.
	 */
	public function enqueue_assets() {
		$bars = $this->get_eligible_bars();
		if ( empty( $bars ) ) {
			return;
		}

		wp_enqueue_style(
			'noticepulse-public',
			NOTICEPULSE_PLUGIN_URL . 'public/css/noticepulse-public.css',
			array(),
			NOTICEPULSE_VERSION
		);

		wp_enqueue_script(
			'noticepulse-public',
			NOTICEPULSE_PLUGIN_URL . 'public/js/noticepulse-public.js',
			array(),
			NOTICEPULSE_VERSION,
			true
		);

		wp_localize_script(
			'noticepulse-public',
			'noticepulseData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'noticepulse_track' ),
			)
		);
	}

	/**
	 * Get bars eligible for the current page and visitor.
	 *
	 * The noticepulse_active_bars filter lets Pro features (geo-targeting,
	 * role targeting, A/B testing) remove bars that should not show.
	 *
	 * @return array
	 */
	private function get_eligible_bars() {
		$bars = NoticePulse_DB::get_active_bars();
		if ( empty( $bars ) ) {
			return array();
		}

		$eligible = array();
		foreach ( $bars as $bar ) {
			if ( ! $this->bar_passes_visibility( $bar ) ) {
				continue;
			}
			if ( ! $this->bar_passes_user_status( $bar ) ) {
				continue;
			}
			if ( ! $this->bar_passes_device( $bar ) ) {
				continue;
			}
			$eligible[] = $bar;
		}

		/**
		 * Filter the eligible bars for the current page/visitor.
		 *
		 * Pro uses this to remove bars that fail geo/role/AB targeting.
		 * Free bar-type classes can also use it to add bar-type-specific
		 * display rules (e.g. GDPR bar hides after consent is given).
		 *
		 * @since 2.0.0
		 * @param array $eligible Array of bar objects.
		 */
		return apply_filters( 'noticepulse_active_bars', $eligible );
	}

	/**
	 * Check page visibility rules for a bar.
	 *
	 * @param object $bar Bar object.
	 * @return bool
	 */
	private function bar_passes_visibility( $bar ) {
		if ( 'all' === $bar->visibility || '' === $bar->visibility ) {
			return true;
		}
		if ( 'specific' === $bar->visibility && ! empty( $bar->page_ids ) ) {
			$page_ids = array_map( 'absint', explode( ',', $bar->page_ids ) );
			$queried  = get_queried_object_id();
			return in_array( $queried, $page_ids, true );
		}
		return true;
	}

	/**
	 * Check user login status for a bar.
	 *
	 * @param object $bar Bar object.
	 * @return bool
	 */
	private function bar_passes_user_status( $bar ) {
		if ( 'all' === $bar->user_status ) {
			return true;
		}
		if ( 'logged_in' === $bar->user_status ) {
			return is_user_logged_in();
		}
		if ( 'logged_out' === $bar->user_status ) {
			return ! is_user_logged_in();
		}
		return true;
	}

	/**
	 * Check that at least one device type is enabled for a bar.
	 *
	 * @param object $bar Bar object.
	 * @return bool
	 */
	private function bar_passes_device( $bar ) {
		return ( $bar->show_desktop || $bar->show_tablet || $bar->show_mobile );
	}

	/**
	 * Render eligible bars in wp_footer.
	 */
	public function render_bars() {
		$bars = $this->get_eligible_bars();
		if ( empty( $bars ) ) {
			return;
		}
		foreach ( $bars as $bar ) {
			$this->render_bar( $bar );
		}
	}

	/**
	 * Render a single bar's HTML.
	 *
	 * Three hook points let Pro and free bar-type classes customise output:
	 *   noticepulse_bar_inline_styles    — override background (e.g. gradient)
	 *   noticepulse_bar_data_attributes  — add data-* attrs (countdown, triggers, GDPR, etc.)
	 *
	 * @param object $bar Bar object.
	 */
	public function render_bar( $bar ) {
		$bar_id = absint( $bar->id );

		// Build device CSS classes.
		$device_classes = array();
		if ( ! $bar->show_desktop ) {
			$device_classes[] = 'np-hide-desktop';
		}
		if ( ! $bar->show_tablet ) {
			$device_classes[] = 'np-hide-tablet';
		}
		if ( ! $bar->show_mobile ) {
			$device_classes[] = 'np-hide-mobile';
		}

		// Build inline styles.
		// FIX: Include --np-btn-bg and --np-btn-txt as CSS custom properties so
		// JS button-building scripts can read user's saved colors via getComputedStyle.
		$bar_style = sprintf(
			'background-color:%s;color:%s;--np-btn-bg:%s;--np-btn-txt:%s;',
			esc_attr( sanitize_hex_color( $bar->bg_color ) ),
			esc_attr( sanitize_hex_color( $bar->text_color ) ),
			esc_attr( sanitize_hex_color( $bar->btn_bg_color ) ),
			esc_attr( sanitize_hex_color( $bar->btn_txt_color ) )
		);

		/**
		 * Filter the bar's inline style string.
		 *
		 * Pro uses this to replace the solid background with a gradient.
		 * Google Fonts uses it to inject font-family.
		 *
		 * @since 2.0.0
		 * @param string $bar_style CSS style string.
		 * @param object $bar       Bar object.
		 */
		$bar_style = apply_filters( 'noticepulse_bar_inline_styles', $bar_style, $bar );

		$btn_style = sprintf(
			'background-color:%s;color:%s;',
			esc_attr( sanitize_hex_color( $bar->btn_bg_color ) ),
			esc_attr( sanitize_hex_color( $bar->btn_txt_color ) )
		);

		$close_style = sprintf(
			'color:%s;',
			esc_attr( sanitize_hex_color( $bar->close_color ) )
		);

		// Build data attributes.
		$data_attrs = sprintf(
			'data-bar-id="%d" data-position="%s" data-sticky="%s" data-cookie-days="%d" data-bar-type="%s"',
			$bar_id,
			esc_attr( $bar->position ),
			$bar->is_sticky ? '1' : '0',
			absint( $bar->cookie_days ),
			esc_attr( isset( $bar->bar_type ) ? $bar->bar_type : 'standard' )
		);

		/**
		 * Filter the bar element's data-* attributes string.
		 *
		 * Pro uses this to add: data-countdown-end, data-trigger-type,
		 * data-geo-match, data-ab-variant, data-ga4-id etc.
		 * Free bar types use it to add: data-gdpr, data-ticker, data-phone.
		 *
		 * @since 2.0.0
		 * @param string $data_attrs Space-separated data-* attribute string.
		 * @param object $bar        Bar object.
		 */
		$data_attrs = apply_filters( 'noticepulse_bar_data_attributes', $data_attrs, $bar );

		$position_class = ( 'bottom' === $bar->position ) ? 'np-bar--bottom' : 'np-bar--top';
		$sticky_class   = $bar->is_sticky ? 'np-bar--sticky' : '';
		$size_class     = 'np-bar--size-' . sanitize_html_class( $bar->font_size );
		$padding_class  = 'np-bar--padding-' . sanitize_html_class( $bar->bar_padding );
		$align_class    = 'np-bar--align-' . sanitize_html_class( $bar->text_align );
		$type_class     = 'np-bar--type-' . sanitize_html_class( isset( $bar->bar_type ) ? $bar->bar_type : 'standard' );
		$device_class   = implode( ' ', $device_classes );

		$all_classes = trim( implode( ' ', array_filter( array(
			'np-bar',
			$position_class,
			$sticky_class,
			$size_class,
			$padding_class,
			$align_class,
			$type_class,
			$device_class,
		) ) ) );

		?>
		<div class="<?php echo esc_attr( $all_classes ); ?>" style="<?php echo esc_attr( $bar_style ); ?>" <?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> role="banner" aria-label="<?php esc_attr_e( 'Site notification', 'noticepulse' ); ?>">
			<div class="np-bar__inner">
				<div class="np-bar__content">
					<span class="np-bar__message"><?php echo wp_kses( $bar->message, $this->allowed_message_tags() ); ?></span>
					<?php if ( ! empty( $bar->cta_label ) ) : // FIX: label alone is enough; URL is optional ?>
						<a class="np-bar__cta"
							href="<?php echo ! empty( $bar->cta_url ) ? esc_url( $bar->cta_url ) : '#'; ?>"
							target="<?php echo esc_attr( $bar->cta_target ); ?>"
							rel="<?php echo ( '_blank' === $bar->cta_target ) ? 'noopener noreferrer' : ''; ?>"
							style="<?php echo esc_attr( $btn_style ); ?> border-radius: <?php echo esc_attr( $this->get_border_radius( $bar->btn_radius ) ); ?>;"
							data-bar-id="<?php echo esc_attr( $bar_id ); ?>"
							aria-label="<?php echo esc_attr( $bar->cta_label ); ?>"
						><?php echo esc_html( $bar->cta_label ); ?></a>
					<?php endif; ?>
				</div>

				<?php if ( $bar->show_close ) : ?>
					<button class="np-bar__close"
						style="<?php echo esc_attr( $close_style ); ?>"
						data-bar-id="<?php echo esc_attr( $bar_id ); ?>"
						aria-label="<?php esc_attr_e( 'Close notification', 'noticepulse' ); ?>"
						type="button"
					>&times;</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get CSS border-radius value from slug.
	 *
	 * @param string $radius_slug sharp|rounded|pill.
	 * @return string CSS value.
	 */
	private function get_border_radius( $radius_slug ) {
		$map = array(
			'sharp'   => '0px',
			'rounded' => '4px',
			'pill'    => '50px',
		);
		return isset( $map[ $radius_slug ] ) ? $map[ $radius_slug ] : '4px';
	}

	/**
	 * Allowed HTML tags for bar message content.
	 *
	 * @return array
	 */
	private function allowed_message_tags() {
		return array(
			'strong' => array(),
			'em'     => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
			'span'   => array(
				'class' => array(),
				'style' => array(),
			),
			'br'     => array(),
		);
	}

	/**
	 * Handle AJAX analytics tracking.
	 */
	public function handle_tracking() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'noticepulse_track' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
		}

		$bar_id     = isset( $_POST['bar_id'] )     ? absint( $_POST['bar_id'] )             : 0;
		$event_type = isset( $_POST['event_type'] ) ? sanitize_key( $_POST['event_type'] )   : '';

		if ( ! $bar_id || ! in_array( $event_type, array( 'impression', 'click' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ), 400 );
		}

		$bar = NoticePulse_DB::get_bar( $bar_id );
		if ( ! $bar ) {
			wp_send_json_error( array( 'message' => 'Bar not found.' ), 404 );
		}

		$recorded = NoticePulse_Analytics::record_event( $bar_id, $event_type );

		if ( $recorded ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( array( 'message' => 'Could not record event.' ), 500 );
		}
	}
}
