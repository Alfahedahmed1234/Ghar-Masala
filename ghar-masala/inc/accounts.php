<?php
/**
 * Customer accounts, entirely on the Ghar Masala site: create an account,
 * sign in, sign out, and reset a forgotten password. Customers never see a
 * WordPress login or profile page.
 *
 * The forms post to admin-ajax.php (see app.js), which works whatever the
 * permalink settings and is not affected by plugins that restrict the REST API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Read a POSTed field. */
function gm_post( $key ) {
	return isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

function gm_account_fail( $message, $status = 400 ) {
	wp_send_json_error( array( 'message' => $message ), $status );
}

function gm_account_nocache() {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'ghar masala account' );
}

/* ---------------------------------------------------------------------------
 * Create an account
 * ------------------------------------------------------------------------ */

add_action( 'wp_ajax_nopriv_gm_register', 'gm_ajax_register' );
add_action( 'wp_ajax_gm_register', 'gm_ajax_register' );

function gm_ajax_register() {
	gm_account_nocache();
	$name     = sanitize_text_field( gm_post( 'name' ) );
	$email    = sanitize_email( gm_post( 'email' ) );
	$password = (string) gm_post( 'password' );

	if ( '' !== gm_post( 'website' ) ) { // Honeypot.
		gm_account_fail( 'Something went wrong — please try again.' );
	}
	if ( '' === $name ) {
		gm_account_fail( 'Please enter your name.', 422 );
	}
	if ( ! is_email( $email ) ) {
		gm_account_fail( 'Please enter a valid email address.', 422 );
	}
	if ( strlen( $password ) < 8 ) {
		gm_account_fail( 'Please choose a password of at least 8 characters.', 422 );
	}
	if ( email_exists( $email ) || username_exists( $email ) ) {
		gm_account_fail( 'There is already an account for that email — please sign in, or use “Forgotten your password?”.', 409 );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $email,
			'user_email'   => $email,
			'user_pass'    => $password,
			'display_name' => $name,
			'first_name'   => strtok( $name, ' ' ),
			'role'         => 'subscriber', // Always a customer, whatever the site default is.
		)
	);
	if ( is_wp_error( $user_id ) ) {
		gm_account_fail( 'Your account could not be created — please try again.', 500 );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );

	wp_mail(
		$email,
		'Welcome to Ghar Masala',
		'Hi ' . strtok( $name, ' ' ) . ",\n\nYour Ghar Masala account is ready. Sign in with this email address to see your orders, reorder in one tap and have your address filled in at checkout.\n\n"
			. gm_view_url( 'menu' ) . "\n\nGhar Masala — tradition served with comfort\n"
	);

	wp_send_json_success();
}

/* ---------------------------------------------------------------------------
 * Sign in / sign out
 * ------------------------------------------------------------------------ */

add_action( 'wp_ajax_nopriv_gm_login', 'gm_ajax_login' );
add_action( 'wp_ajax_gm_login', 'gm_ajax_login' );

function gm_ajax_login() {
	gm_account_nocache();
	$user = wp_signon(
		array(
			'user_login'    => sanitize_text_field( gm_post( 'log' ) ),
			'user_password' => (string) gm_post( 'pwd' ),
			'remember'      => '' !== gm_post( 'rememberme' ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		gm_account_fail( 'That email and password don’t match — please try again.', 401 );
	}
	wp_send_json_success();
}

add_action( 'wp_ajax_gm_logout', function () {
	gm_account_nocache();
	wp_logout();
	wp_send_json_success();
} );
add_action( 'wp_ajax_nopriv_gm_logout', function () {
	wp_send_json_success(); // Already signed out.
} );

/* ---------------------------------------------------------------------------
 * Forgotten password: email a link back to the site, then set a new one there
 * ------------------------------------------------------------------------ */

add_action( 'wp_ajax_nopriv_gm_lost_password', 'gm_ajax_lost_password' );
add_action( 'wp_ajax_gm_lost_password', 'gm_ajax_lost_password' );

function gm_ajax_lost_password() {
	gm_account_nocache();
	$login = trim( sanitize_text_field( gm_post( 'email' ) ) );
	if ( '' === $login ) {
		gm_account_fail( 'Please enter your email address.', 422 );
	}
	$user = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );

	// Same answer whether or not the account exists, so the form can't be used
	// to find out who has an account.
	$sent = array( 'message' => 'If there is an account for that email, a link to set a new password is on its way. Check your inbox (and spam folder).' );

	// Only customers reset here; staff use the normal WordPress login.
	if ( ! $user || $user->has_cap( 'edit_posts' ) ) {
		wp_send_json_success( $sent );
	}
	// One email a minute per account is plenty.
	if ( get_transient( 'gm_reset_sent_' . $user->ID ) ) {
		wp_send_json_success( $sent );
	}

	$key = get_password_reset_key( $user );
	if ( is_wp_error( $key ) ) {
		gm_account_fail( 'We could not send a reset link just now — please try again later.', 500 );
	}
	set_transient( 'gm_reset_sent_' . $user->ID, 1, MINUTE_IN_SECONDS );

	$link = add_query_arg(
		array(
			'gm_reset' => $key,
			'login'    => rawurlencode( $user->user_login ),
		),
		home_url( '/' )
	) . '#reset';

	wp_mail(
		$user->user_email,
		'Reset your Ghar Masala password',
		'Hi ' . ( $user->first_name ? $user->first_name : $user->display_name ) . ",\n\n"
			. "Someone (hopefully you) asked to reset the password for your Ghar Masala account.\n\n"
			. "Choose a new password here:\n" . $link . "\n\n"
			. "The link works for 24 hours. If you didn't ask for this, you can ignore this email — your password won't change.\n\n"
			. "Ghar Masala — tradition served with comfort\n"
	);

	wp_send_json_success( $sent );
}

add_action( 'wp_ajax_nopriv_gm_reset_password', 'gm_ajax_reset_password' );
add_action( 'wp_ajax_gm_reset_password', 'gm_ajax_reset_password' );

function gm_ajax_reset_password() {
	gm_account_nocache();
	$password = (string) gm_post( 'password' );
	$user     = check_password_reset_key( sanitize_text_field( gm_post( 'key' ) ), sanitize_text_field( gm_post( 'login' ) ) );
	if ( is_wp_error( $user ) ) {
		gm_account_fail( 'This reset link has expired or has already been used. Please ask for a new one.', 410 );
	}
	if ( strlen( $password ) < 8 ) {
		gm_account_fail( 'Please choose a password of at least 8 characters.', 422 );
	}
	reset_password( $user, $password );
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true, is_ssl() );
	wp_send_json_success();
}

// The kitchen does not need an email each time a customer changes their password.
remove_action( 'after_password_reset', 'wp_password_change_notification' );

/* ---------------------------------------------------------------------------
 * Keep customers off WordPress's own screens
 * ------------------------------------------------------------------------ */

/** WordPress's lost-password / register / reset pages → the site's own forms. */
add_action( 'login_init', function () {
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		wp_safe_redirect( gm_view_url( 'forgot' ) );
		exit;
	}
	if ( 'register' === $action ) {
		wp_safe_redirect( gm_view_url( 'register' ) );
		exit;
	}
	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) && isset( $_GET['key'], $_GET['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$user = get_user_by( 'login', sanitize_text_field( wp_unslash( $_GET['login'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( $user && ! $user->has_cap( 'edit_posts' ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'gm_reset' => sanitize_text_field( wp_unslash( $_GET['key'] ) ), // phpcs:ignore WordPress.Security.NonceVerification
						'login'    => rawurlencode( $user->user_login ),
					),
					home_url( '/' )
				) . '#reset'
			);
			exit;
		}
	}
} );

/** Customers who wander into /wp-admin go to their account page instead. */
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	wp_safe_redirect( gm_view_url( 'account' ) );
	exit;
} );

/** Emails come from "Ghar Masala", not "WordPress". */
add_filter( 'wp_mail_from_name', function ( $name ) {
	return 'WordPress' === $name ? 'Ghar Masala' : $name;
} );
