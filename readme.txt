=== Elevation Magic Link Login ===
Contributors: elevation1support, msuarez25
Tags: login, magic link, passwordless, security, authentication
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Add a secure, passwordless login option to the default WordPress login form.

== Description ==

Elevation Magic Link Login allows your users to sign in without remembering a password. By simply entering their username or email address, they receive a secure, time-sensitive link via email that logs them in instantly.

This plugin is built with security as a priority, utilizing WordPress best practices such as nonces, input sanitization, output escaping, and hashed tokens to ensure your site and users remain protected.

= Features =

Adds a "Send Me a Magic Link" button to the default WP login form.

Secure, high-entropy token generation.

Tokens are hashed before storage for maximum security.

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

The login form with the Magic Link button.

The custom notification after a link has been sent.

== Changelog ==

= 1.2 =

Added emll_login_redirect filter for custom redirect URLs.

Documentation updates.

= 1.1 =

Added hashed token storage.

Improved security checks for user identification.

Added CSRF protection via nonces.

= 1.0 =

Initial release.