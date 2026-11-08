<?php
/**
 * Plugin Name: Fix password reset behind page cache
 * Description: Lets password reset links work when a host cache strips the wp-resetpass cookie
 *              (e.g. a custom login URL from WPS Hide Login that the cache doesn't exclude).
 *
 * WordPress normally moves the reset key from the URL into a cookie and redirects.
 * If the cache drops that cookie, every link shows "invalid key". This skips the
 * cookie: the key is read from the link (and from the form on submit) instead.
 * The key is still fully checked by check_password_reset_key().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'login_init', function () {
	$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
	if ( 'rp' !== $action && 'resetpass' !== $action ) {
		return;
	}

	$cookie = 'wp-resetpass-' . COOKIEHASH;
	$login  = '';
	$key    = '';

	if ( isset( $_GET['login'], $_GET['key'] ) ) {
		// Link from the email: use the values directly and stop core from redirecting.
		$login = wp_unslash( $_GET['login'] );
		$key   = wp_unslash( $_GET['key'] );
		unset( $_GET['login'], $_GET['key'] );
	} elseif ( isset( $_POST['rp_login'], $_POST['rp_key'] ) ) {
		// Form submit: values come from the hidden fields.
		$login = wp_unslash( $_POST['rp_login'] );
		$key   = wp_unslash( $_POST['rp_key'] );
	}

	if ( '' !== $login && '' !== $key ) {
		$_COOKIE[ $cookie ] = $login . ':' . $key;
	}

	nocache_headers();
}, 1 );

// Add the username to the reset form so the submit doesn't need the cookie.
add_action( 'resetpass_form', function ( $user ) {
	if ( $user instanceof WP_User ) {
		echo '<input type="hidden" name="rp_login" value="' . esc_attr( $user->user_login ) . '" />';
	}
} );

// Don't keep the key in the browser's referrer after the page loads.
add_action( 'login_head', function () {
	echo '<meta name="referrer" content="no-referrer" />' . "\n";
} );
