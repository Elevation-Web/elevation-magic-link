<?php
/**
 * Plugin Name: Elevation Magic Link Login
 * Description: Adds a "Magic Link" option to the default WordPress login form.
 * Version: 1.2
 * Author: Elevation
 * Author URI: https://elevationweb.org/
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: elevation-magic-link
 *
 * @package Elevation_Magic_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add the "Send Magic Link" button to the default login form.
 */
add_action( 'login_form', 'emll_add_magic_link_button' );

/**
 * Output the Magic Link button and associated script.
 */
function emll_add_magic_link_button() {
	// Add a security nonce field.
	wp_nonce_field( 'emll_request_magic_nonce', 'emll_nonce' );
	?>
	<div class="emll-container" style="margin: 20px 0; padding-top: 10px; border-top: 1px solid #ddd; text-align: center;">
		<p style="font-size: 12px; color: #666; margin-bottom: 10px;">
			<?php echo esc_html__( "Don't want to use a password?", 'elevation-magic-link' ); ?>
		</p>
		<button type="submit" id="emll-magic-btn" name="emll_request_magic" value="1" class="button button-secondary" style="width: 100%; height: 40px;">
			<?php echo esc_html__( 'Send Me a Magic Link', 'elevation-magic-link' ); ?>
		</button>
	</div>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			var magicBtn = document.getElementById('emll-magic-btn');
			var passwordField = document.getElementById('user_pass');

			if (magicBtn && passwordField) {
				magicBtn.addEventListener('click', function() {
					// Temporarily remove required attribute to allow form submission without password
					passwordField.removeAttribute('required');
				});
			}
		});
	</script>
	<?php
}

/**
 * Handle the request to generate and send a magic link.
 */
add_action( 'init', 'emll_handle_request' );

/**
 * Handle the logic for generating a token and sending the email.
 *
 * @return void
 */
function emll_handle_request() {
	// Only proceed if our specific button was clicked.
	if ( isset( $_POST['emll_request_magic'] ) && ! empty( $_POST['log'] ) ) {

		// 1. Verify Nonce for security.
		if ( ! isset( $_POST['emll_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['emll_nonce'] ) ), 'emll_request_magic_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'elevation-magic-link' ) );
		}

		$user_input = sanitize_text_field( wp_unslash( $_POST['log'] ) );

		// Try to find user by username or email.
		$user = get_user_by( 'login', $user_input );
		if ( ! $user ) {
			$user = get_user_by( 'email', $user_input );
		}

		if ( $user ) {
			// Generate a secure, high-entropy token.
			$token      = wp_generate_password( 32, false );
			$expires_in = apply_filters( 'emll_token_expiration', 15 * MINUTE_IN_SECONDS );
			$expiration = time() + $expires_in;

			// Store the token and expiration in user meta.
			update_user_meta(
				$user->ID,
				'_emll_magic_token',
				array(
					'token'   => wp_hash_password( $token ), // Store hashed for security.
					'expires' => $expiration,
				)
			);

			// Construct the login URL.
			$magic_url = add_query_arg(
				array(
					'emll_token' => $token,
					'emll_uid'   => $user->ID,
				),
				wp_login_url()
			);

			// Email details.
			$site_name = get_bloginfo( 'name' );

			/* translators: %s: Site Name */
			$subject = sprintf( esc_html__( '[%s] Your Magic Login Link', 'elevation-magic-link' ), $site_name );

			/* translators: 1: Site Name, 2: Magic Link URL */
			$message = sprintf(
				__( "Hello,\n\nYou requested a magic link to log into %1\$s. This link will expire shortly.\n\nClick the link below to sign in automatically:\n%2\$s\n\nIf you did not request this, please ignore this email.", 'elevation-magic-link' ),
				$site_name,
				$magic_url
			);

			wp_mail( $user->user_email, $subject, $message );
		}

		// Redirect back with a generic success flag for privacy.
		wp_safe_redirect( add_query_arg( 'magic_sent', '1', wp_login_url() ) );
		exit;
	}
}

/**
 * Validate the magic link token and log the user in if valid.
 */
add_action( 'init', 'emll_process_magic_login' );

/**
 * Process the magic link login attempt.
 *
 * @return void
 */
function emll_process_magic_login() {
	// The emll_token serves as the one-time security nonce for this request.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['emll_token'] ) && isset( $_GET['emll_uid'] ) ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = absint( $_GET['emll_uid'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['emll_token'] ) );

		// Additional Security: Check if user actually exists.
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user identifier.', 'elevation-magic-link' ), esc_html__( 'Login Failed', 'elevation-magic-link' ), array( 'response' => 403 ) );
		}

		$data = get_user_meta( $user_id, '_emll_magic_token', true );

		if ( $data && isset( $data['token'], $data['expires'] ) && time() < $data['expires'] ) {
			// Verify hashed token.
			if ( wp_check_password( $token, $data['token'] ) ) {

				// Success! Clear the token immediately (one-time use).
				delete_user_meta( $user_id, '_emll_magic_token' );

				// Log the user in.
				wp_clear_auth_cookie();
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id, true );

				/**
				 * Filter the redirect URL after a successful magic link login.
				 *
				 * @param string  $redirect_to The default redirect URL (admin_url).
				 * @param WP_User $user        The logged-in user object.
				 */
				$redirect_to = apply_filters( 'emll_login_redirect', admin_url(), $user );

				// Redirect to dashboard or filtered URL.
				wp_safe_redirect( $redirect_to );
				exit;
			}
		}

		// If validation fails.
		wp_die( esc_html__( 'This magic link is invalid or has expired. Please request a new one.', 'elevation-magic-link' ), esc_html__( 'Login Failed', 'elevation-magic-link' ), array( 'response' => 403 ) );
	}
}

/**
 * Display a message after requesting a magic link.
 */
add_filter( 'login_message', 'emll_custom_login_message' );

/**
 * Add a custom message to the login form when a magic link is sent.
 *
 * @param string $message Existing login message.
 * @return string Modified login message.
 */
function emll_custom_login_message( $message ) {
	// This only checks for a flag to display a notification.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['magic_sent'] ) && '1' === $_GET['magic_sent'] ) {
		$notice = esc_html__( 'Check your email! If an account exists, we\'ve sent you a magic login link.', 'elevation-magic-link' );
		return $message . '<p class="message" style="border-left-color: #662d91;">' . $notice . '</p>';
	}
	return $message;
}
