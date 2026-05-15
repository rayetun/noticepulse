<?php
/**
 * NoticePulse — Email Capture Bar.
 *
 * Renders an inline email form in the bar.
 * Stores leads locally in noticepulse_leads table.
 * Optionally pushes to Mailchimp, Klaviyo, ConvertKit,
 * ActiveCampaign, MailerLite, or Brevo.
 *
 * @package NoticePulse
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class NoticePulse_Email_Capture {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'noticepulse_bar_data_attributes',  array( $this, 'add_data_attributes' ), 10, 2 );
		add_filter( 'noticepulse_save_bar_data',         array( $this, 'save_fields' ) );
		add_action( 'wp_ajax_nopriv_noticepulse_email_subscribe', array( $this, 'handle_subscribe' ) );
		add_action( 'wp_ajax_noticepulse_email_subscribe',        array( $this, 'handle_subscribe' ) );
		add_action( 'wp_enqueue_scripts',                array( $this, 'maybe_enqueue' ), 20 );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// PUBLIC OUTPUT
	// ─────────────────────────────────────────────────────────────────────────

	public function add_data_attributes( $attrs, $bar ) {
		if ( ! isset( $bar->bar_type ) || 'email_capture' !== $bar->bar_type ) { return $attrs; }
		$meta = NoticePulse_DB::get_meta( $bar, 'email' );

		$attrs .= sprintf(
			' data-email-capture="1" data-email-placeholder="%s" data-email-btn="%s" data-email-success="%s" data-email-nonce="%s" data-bar-id-email="%d"',
			esc_attr( $meta['placeholder'] ?? __( 'Enter your email…', 'noticepulse' ) ),
			esc_attr( $meta['btn_label']   ?? __( 'Subscribe', 'noticepulse' ) ),
			esc_attr( $meta['success_msg'] ?? __( '🎉 You\'re in! Check your inbox.', 'noticepulse' ) ),
			wp_create_nonce( 'noticepulse_email_subscribe' ),
			absint( $bar->id )
		);
		return $attrs;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// AJAX: Handle email subscription
	// ─────────────────────────────────────────────────────────────────────────

	public function handle_subscribe() {
		check_ajax_referer( 'noticepulse_email_subscribe', 'nonce' );

		$bar_id = isset( $_POST['bar_id'] ) ? absint( $_POST['bar_id'] ) : 0;
		$email  = isset( $_POST['email'] )  ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name   = isset( $_POST['name'] )   ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		if ( ! $bar_id || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'noticepulse' ) ) );
		}

		$bar = NoticePulse_DB::get_bar( $bar_id );
		if ( ! $bar ) {
			wp_send_json_error( array( 'message' => __( 'Bar not found.', 'noticepulse' ) ) );
		}

		// Store lead locally — check for duplicate email per bar first.
		global $wpdb;
		$table = $wpdb->prefix . NoticePulse_DB::LEADS_TABLE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE bar_id = %d AND email = %s',
				$table,
				$bar_id,
				$email
			)
		);

		if ( ! $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table,
				array(
					'bar_id' => $bar_id,
					'email'  => $email,
					'name'   => $name,
				)
			);
		}

		// Push to email provider.
		$meta = NoticePulse_DB::get_meta( $bar, 'email' );
		if ( ! empty( $meta['provider'] ) && 'none' !== $meta['provider'] ) {
			$this->push_to_provider( $meta, $email, $name );
		}

		wp_send_json_success( array( 'message' => $meta['success_msg'] ?? __( '🎉 You\'re in!', 'noticepulse' ) ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// EMAIL PROVIDER PUSH
	// ─────────────────────────────────────────────────────────────────────────

	private function push_to_provider( $meta, $email, $name ) {
		$provider = $meta['provider'] ?? '';
		$api_key  = $meta['api_key']  ?? '';
		$list_id  = $meta['list_id']  ?? '';

		if ( empty( $api_key ) ) { return; }

		switch ( $provider ) {
			case 'mailchimp':
				$dc       = substr( $api_key, strpos( $api_key, '-' ) + 1 );
				$hash     = md5( strtolower( $email ) );
				$endpoint = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$hash}";
				wp_remote_request(
					$endpoint,
					array(
						'method'  => 'PUT',
						'headers' => array(
							'Authorization' => 'Basic ' . base64_encode( 'noticepulse:' . $api_key ),
							'Content-Type'  => 'application/json',
						),
						'body'    => wp_json_encode( array(
							'email_address' => $email,
							'status_if_new' => 'subscribed',
						) ),
					)
				);
				break;

			case 'klaviyo':
				wp_remote_post(
					'https://a.klaviyo.com/api/v2/list/' . $list_id . '/members',
					array(
						'headers' => array( 'Content-Type' => 'application/json' ),
						'body'    => wp_json_encode( array(
							'api_key'  => $api_key,
							'profiles' => array( array( 'email' => $email ) ),
						) ),
					)
				);
				break;

			case 'convertkit':
				wp_remote_post(
					"https://api.convertkit.com/v3/forms/{$list_id}/subscribe",
					array(
						'body' => array(
							'api_key'    => $api_key,
							'email'      => $email,
							'first_name' => $name,
						),
					)
				);
				break;

			case 'mailerlite':
				wp_remote_post(
					'https://connect.mailerlite.com/api/subscribers',
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . $api_key,
							'Content-Type'  => 'application/json',
						),
						'body'    => wp_json_encode( array(
							'email'  => $email,
							'groups' => array( $list_id ),
						) ),
					)
				);
				break;

			case 'brevo':
				wp_remote_post(
					'https://api.brevo.com/v3/contacts',
					array(
						'headers' => array(
							'api-key'      => $api_key,
							'Content-Type' => 'application/json',
						),
						'body'    => wp_json_encode( array(
							'email'         => $email,
							'listIds'       => array( (int) $list_id ),
							'updateEnabled' => true,
						) ),
					)
				);
				break;
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// SAVE
	// ─────────────────────────────────────────────────────────────────────────

	public function save_fields( $data ) {
		// Verify nonce — the same nonce submitted with the bar save form.
		// check_admin_referer() is also called upstream in handle_save_bar()
		// before this filter fires, so this is a belt-and-braces check.
		if (
			! isset( $_POST['noticepulse_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_key( $_POST['noticepulse_nonce'] ),
				'noticepulse_save_bar'
			)
		) {
			return $data;
		}

		// FIX: wp_unslash() + sanitize_key() on bar_type before comparison.
		$np_bar_type = sanitize_key( wp_unslash( $_POST['bar_type'] ?? '' ) );
		if ( 'email_capture' !== $np_bar_type ) { return $data; }

		$values = array(
			'placeholder' => sanitize_text_field( wp_unslash( $_POST['email_placeholder'] ?? __( 'Enter your email…', 'noticepulse' ) ) ),
			'btn_label'   => sanitize_text_field( wp_unslash( $_POST['email_btn_label']   ?? __( 'Subscribe', 'noticepulse' ) ) ),
			'success_msg' => sanitize_text_field( wp_unslash( $_POST['email_success_msg'] ?? __( '🎉 You\'re in! Check your inbox.', 'noticepulse' ) ) ),
			'provider'    => sanitize_key(        wp_unslash( $_POST['email_provider']     ?? 'none' ) ),
			'api_key'     => sanitize_text_field( wp_unslash( $_POST['email_api_key']      ?? '' ) ),
			'list_id'     => sanitize_text_field( wp_unslash( $_POST['email_list_id']      ?? '' ) ),
		);

		$data['bar_meta'] = NoticePulse_DB::set_meta( $data['bar_meta'] ?? '', 'email', $values );
		return $data;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// ENQUEUE
	// ─────────────────────────────────────────────────────────────────────────

	public function maybe_enqueue() {
		$bars = NoticePulse_DB::get_active_bars();
		foreach ( $bars as $bar ) {
			if ( isset( $bar->bar_type ) && 'email_capture' === $bar->bar_type ) {
				wp_enqueue_script(
					'np-email-capture',
					NOTICEPULSE_PLUGIN_URL . 'public/js/np-email-capture.js',
					array( 'noticepulse-public' ),
					NOTICEPULSE_VERSION,
					true
				);
				wp_localize_script(
					'np-email-capture',
					'noticepulseEmailCapture',
					array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ) )
				);
				// FIX: noticepulse-pro-public merged into noticepulse-public — reference updated.
				wp_enqueue_style(
					'noticepulse-public',
					NOTICEPULSE_PLUGIN_URL . 'public/css/noticepulse-public.css',
					array(),
					NOTICEPULSE_VERSION
				);
				return;
			}
		}
	}
}
