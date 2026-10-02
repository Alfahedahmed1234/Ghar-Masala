<?php
/**
 * Customer accounts on the site itself: create an account and sign in
 * without visiting the WordPress login pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route(
		'ghar-masala/v1',
		'/account/register',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'gm_rest_register',
		)
	);
	register_rest_route(
		'ghar-masala/v1',
		'/account/login',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'gm_rest_login',
		)
	);
} );

function gm_rest_register( WP_REST_Request $request ) {
	do_action( 'litespeed_control_set_nocache', 'ghar masala account' );
	$data     = (array) $request->get_json_params();
	$name     = sanitize_text_field( $data['name'] ?? '' );
	$email    = sanitize_email( $data['email'] ?? '' );
	$password = (string) ( $data['password'] ?? '' );

	if ( ! empty( $data['website'] ) ) { // Honeypot.
		return new WP_Error( 'gm_bad_request', 'Something went wrong — please try again.', array( 'status' => 400 ) );
	}
	if ( '' === $name ) {
		return new WP_Error( 'gm_field_name', 'Please enter your name.', array( 'status' => 422 ) );
	}
	if ( ! is_email( $email ) ) {
		return new WP_Error( 'gm_field_email', 'Please enter a valid email address.', array( 'status' => 422 ) );
	}
	if ( strlen( $password ) < 8 ) {
		return new WP_Error( 'gm_field_password', 'Please choose a password of at least 8 characters.', array( 'status' => 422 ) );
	}
	if ( email_exists( $email ) || username_exists( $email ) ) {
		return new WP_Error( 'gm_exists', 'There is already an account for that email — please sign in instead.', array( 'status' => 409 ) );
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
		return new WP_Error( 'gm_register_failed', 'Your account could not be created — please try again.', array( 'status' => 500 ) );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );

	wp_mail(
		$email,
		'Welcome to Ghar Masala',
		'Hi ' . strtok( $name, ' ' ) . ",\n\nYour Ghar Masala account is ready. Sign in with this email address to see your orders, reorder in one tap and have your address filled in at checkout.\n\n"
			. gm_view_url( 'menu' ) . "\n\nGhar Masala — tradition served with comfort\n"
	);

	return array( 'ok' => true );
}

function gm_rest_login( WP_REST_Request $request ) {
	do_action( 'litespeed_control_set_nocache', 'ghar masala account' );
	$data = (array) $request->get_json_params();
	$user = wp_signon(
		array(
			'user_login'    => sanitize_text_field( $data['log'] ?? '' ),
			'user_password' => (string) ( $data['pwd'] ?? '' ),
			'remember'      => ! empty( $data['remember'] ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		return new WP_Error( 'gm_login_failed', 'That email and password don’t match — please try again.', array( 'status' => 401 ) );
	}
	return array( 'ok' => true );
}

/** Emails come from "Ghar Masala", not "WordPress". */
add_filter( 'wp_mail_from_name', function ( $name ) {
	return 'WordPress' === $name ? 'Ghar Masala' : $name;
} );
