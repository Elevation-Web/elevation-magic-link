<?php
/**
 * Plugin Name: Elevation Magic Link Login
 * Description: Adds a "Magic Link" option to the default WordPress login form.
 * Version: 1.2.2
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
 * Output the Magic Link button.
 */
function emll_add_magic_link_button() {
	// Add a security nonce field for the request form.
	wp_nonce_field( 'emll_request_magic_nonce', 'emll_nonce' );
	?>
	<div class="emll-container" style="margin: 20px 0; padding-top: 10px; border-top: 1px solid #ddd; text-align: center;">

		<!-- View 1: Initial Toggle Button -->
		<div id="emll-view-toggle">
			<p style="font-size: 12px; color: #666; margin-bottom: 10px;">
				<?php echo esc_html__( "Don't want to use a password?", 'elevation-magic-link' ); ?>
			</p>
			<button type="button" id="emll-toggle-btn" class="button button-secondary" style="width: 100%; height: 40px;">
				<?php echo esc_html__( 'Send Me a Magic Link', 'elevation-magic-link' ); ?>
			</button>
		</div>

		<!-- View 2: Magic Link Submission (Hidden by default) -->
		<div id="emll-view-submit" style="display:none;">
			<p style="font-size: 12px; color: #666; margin-bottom: 10px;">
				<?php echo esc_html__( 'Enter your username or email above.', 'elevation-magic-link' ); ?>
			</p>
			<button type="button" id="emll-submit-btn" class="button button-primary" style="width: 100%; height: 40px; margin-bottom: 10px;">
				<?php echo esc_html__( 'Send Login Link', 'elevation-magic-link' ); ?>
			</button>
			<a href="#" id="emll-back-btn" style="font-size: 12px; text-decoration: none;">
				<?php echo esc_html__( 'Back to Password Login', 'elevation-magic-link' ); ?>
			</a>
		</div>

		<!-- Hidden input to act as the actual trigger -->
		<input type="hidden" name="emll_request_magic" id="emll_request_magic_input" value="" disabled>
	</div>
	<?php
}

/**
 * Enqueue scripts correctly using wp_add_inline_script.
 */
add_action( 'login_enqueue_scripts', 'emll_login_scripts' );

/**
 * Register and enqueue the JS for the login button interaction.
 */
function emll_login_scripts() {
	// Register a dummy handle to attach the inline script to.
	// We depend on 'login' or 'jquery' if available, but 'login' is safe on this page.
	wp_register_script( 'emll-login-script', '', array(), '1.2.2', true );
	wp_enqueue_script( 'emll-login-script' );

	$script = "
    document.addEventListener('DOMContentLoaded', function() {
        // Elements from our plugin
        var toggleBtn = document.getElementById('emll-toggle-btn');
        var submitBtn = document.getElementById('emll-submit-btn');
        var backBtn   = document.getElementById('emll-back-btn');
        var magicInput = document.getElementById('emll_request_magic_input');
        var viewToggle = document.getElementById('emll-view-toggle');
        var viewSubmit = document.getElementById('emll-view-submit');

        // Standard WordPress Login Elements
        var loginForm = document.getElementById('loginform');
        var passwordField = document.getElementById('user_pass');
        var passwordWrap = document.querySelector('.user-pass-wrap'); // Wrapper for password field
        var standardSubmit = document.querySelector('.submit'); // Wrapper for standard submit button
        var rememberMe = document.querySelector('.forgetmenot'); // 'Remember Me' checkbox

        // Helper to toggle between Password Mode and Magic Link Mode
        function toggleMagicMode(isMagic) {
            var displayStyle = isMagic ? 'none' : 'block';
            var magicStyle = isMagic ? 'block' : 'none';

            // Toggle standard WP elements
            if(passwordWrap) passwordWrap.style.display = displayStyle;
            if(standardSubmit) standardSubmit.style.display = displayStyle;
            if(rememberMe) rememberMe.style.display = displayStyle;

            // Toggle our elements
            if(viewToggle) viewToggle.style.display = displayStyle;
            if(viewSubmit) viewSubmit.style.display = magicStyle;

            // Handle Logic
            if (isMagic) {
                // Enable magic link mode
                if(magicInput) { 
                    magicInput.removeAttribute('disabled'); 
                    magicInput.value = '1'; 
                }
                if(passwordField) passwordField.removeAttribute('required');
            } else {
                // Revert to standard mode
                if(magicInput) { 
                    magicInput.setAttribute('disabled', 'disabled'); 
                    magicInput.value = ''; 
                }
                if(passwordField) passwordField.setAttribute('required', 'required');
            }
        }

        // Event Listeners
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleMagicMode(true);
            });
        }

        if (backBtn) {
            backBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleMagicMode(false);
            });
        }

        if (submitBtn && loginForm) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Ensure the input is active before submitting
                if(magicInput) {
                    magicInput.removeAttribute('disabled');
                    magicInput.value = '1';
                }
                if(passwordField) {
                    passwordField.removeAttribute('required');
                }
                loginForm.submit();
            });
        }
    });
    ";

	wp_add_inline_script( 'emll-login-script', $script );
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
	// Optimization: check if this is the correct request method early.
	if ( ! isset( $_POST['emll_request_magic'] ) ) {
		return;
	}

	// 1. Verify Nonce for security.
	if ( ! isset( $_POST['emll_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['emll_nonce'] ) ), 'emll_request_magic_nonce' ) ) {
		wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'elevation-magic-link' ) );
	}

	// Check input log.
	if ( empty( $_POST['log'] ) ) {
		return;
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

		// Generate a stateless signature to validate the origin of the link without relying on session cookies.
		// This acts as a nonce that works across devices (e.g., Request on Desktop -> Click on Mobile).
		$signature = hash_hmac( 'sha256', $user->ID . $token, wp_salt() );

		// Construct the login URL.
		$magic_url = add_query_arg(
			array(
				'emll_token' => $token,
				'emll_uid'   => $user->ID,
				'emll_sig'   => $signature,
			),
			wp_login_url()
		);

		// Email details.
		$site_name = get_bloginfo( 'name' );

		/* translators: %s: Site Name */
		$subject = sprintf( esc_html__( '[%s] Your Magic Login Link', 'elevation-magic-link' ), $site_name );

		$message = sprintf(
			/* translators: 1: Site Name, 2: Magic Link URL */
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
	// Check if the necessary parameters are present.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification uses stateless HMAC signature below.
	if ( ! isset( $_GET['emll_token'], $_GET['emll_uid'], $_GET['emll_sig'] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$user_id = absint( $_GET['emll_uid'] );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$token = sanitize_text_field( wp_unslash( $_GET['emll_token'] ) );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$signature = sanitize_text_field( wp_unslash( $_GET['emll_sig'] ) );

	// 1. Verify Origin (Nonce Replacement).
	// We reconstruct the hash using the ID and Token from the URL and the site salt.
	// If this does not match the signature provided, the URL was tampered with or did not originate from this site.
	$expected_signature = hash_hmac( 'sha256', $user_id . $token, wp_salt() );

	if ( ! hash_equals( $expected_signature, $signature ) ) {
		wp_die( esc_html__( 'Invalid link signature. Request denied.', 'elevation-magic-link' ), esc_html__( 'Login Failed', 'elevation-magic-link' ), array( 'response' => 403 ) );
	}

	// 2. Validate User.
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		wp_die( esc_html__( 'Invalid user identifier.', 'elevation-magic-link' ), esc_html__( 'Login Failed', 'elevation-magic-link' ), array( 'response' => 403 ) );
	}

	// 3. Validate Token logic.
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
	// Safe to ignore nonce here as it only controls a visual message, not data processing.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['magic_sent'] ) && '1' === $_GET['magic_sent'] ) {
		$notice = esc_html__( 'Check your email! If an account exists, we\'ve sent you a magic login link.', 'elevation-magic-link' );
		return $message . '<p class="message" style="border-left-color: #662d91;">' . $notice . '</p>';
	}
	return $message;
}
