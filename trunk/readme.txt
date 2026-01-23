=== Elevation Magic Link Login ===
Contributors: elevation1support, msuarez25
Tags: login, magic link, passwordless, security, authentication
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Add a secure, passwordless login option to the default WordPress login form.

== Description ==

Elevation Magic Link Login allows your users to sign in without remembering a password. By simply entering their username or email address, they receive a secure, time-sensitive link via email that logs them in instantly.

This plugin is built with security as a priority, utilizing WordPress best practices such as nonces, input sanitization, output escaping, hashed tokens, and HMAC signatures to ensure your site and users remain protected.

= Features =

Adds a "Send Me a Magic Link" button to the default WP login form.

New: Toggle-based UI that hides the password field when requesting a link for a cleaner experience.

Secure, high-entropy token generation.

Tokens are hashed before storage for maximum security.

Cross-device support: Uses stateless HMAC signatures to validate links even if opened on a different device than requested.

One-time use links that expire after 15 minutes (filterable).

No-password fallback for users who forget their credentials.

Lightweight and developer-friendly.

Filterable redirect URL after successful login.

== Installation ==

Upload the elevation-magic-link-login folder to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

Your login page will now display the Magic Link button.

== Frequently Asked Questions ==

= How long do links stay valid? =
By default, links expire after 15 minutes. Developers can change this using the emll_token_expiration filter.

= Does this replace the standard password login? =
No, it adds a secondary option. Users can still log in using their standard username and password.

= How can I change the redirect URL? =
You can use the emll_login_redirect filter in your theme's functions.php. Example:
add_filter('emll_login_redirect', function($url, $user) { return home_url('/welcome'); }, 10, 2);

== Screenshots ==

The login form with the Magic Link toggle button.

The Magic Link input view.

The custom notification after a link has been sent.

== Changelog ==

= 1.2.2 =

Improved UI: The "Send Magic Link" button now toggles the form view, hiding the password field and showing a specific email submission button.

Added "Back to Password Login" link for better usability.

= 1.2.1 =

Security Update: Implemented Stateless HMAC Signature verification. This validates the link origin while allowing users to request on one device and login on another.

Fix: Replaced raw script tags with wp_add_inline_script for better WordPress standard compliance.

= 1.2 =

Added emll_login_redirect filter for custom redirect URLs.

Documentation updates.

= 1.1 =

Added hashed token storage.

Improved security checks for user identification.

Added CSRF protection via nonces.

= 1.0 =

Initial release.