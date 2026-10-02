<?php
/**
 * REST endpoints used by assets/js/app.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', function () {
	register_rest_route(
		'ghar-masala/v1',
		'/slots',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function () {
				do_action( 'litespeed_control_set_nocache', 'ghar masala live slots' );
				$response = new WP_REST_Response( array( 'days' => gm_calendar() ) );
				$response->header( 'Cache-Control', 'no-store, max-age=0' );
				return $response;
			},
		)
	);

	register_rest_route(
		'ghar-masala/v1',
		'/orders',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'gm_rest_place_order',
		)
	);

	register_rest_route(
		'ghar-masala/v1',
		'/stripe-webhook',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'gm_stripe_webhook',
		)
	);
} );

function gm_rest_place_order( WP_REST_Request $request ) {
	do_action( 'litespeed_control_set_nocache', 'ghar masala order' );
	$data = $request->get_json_params();
	if ( ! is_array( $data ) ) {
		return new WP_Error( 'gm_bad_request', 'Something went wrong — please try again.', array( 'status' => 400 ) );
	}
	// Honeypot: real visitors never see or fill this field.
	if ( ! empty( $data['website'] ) ) {
		return new WP_Error( 'gm_bad_request', 'Something went wrong — please try again.', array( 'status' => 400 ) );
	}

	$id = gm_create_order( $data );
	if ( is_wp_error( $id ) ) {
		$id->add_data( array( 'status' => 422 ) );
		return $id;
	}

	if ( 'card' === get_post_meta( $id, '_gm_payment', true ) ) {
		$url = gm_stripe_checkout_url( $id );
		if ( is_wp_error( $url ) ) {
			update_post_meta( $id, '_gm_status', 'cancelled' );
			return new WP_Error( 'gm_stripe', 'Card payment could not be started: ' . $url->get_error_message(), array( 'status' => 502 ) );
		}
		return array( 'redirect' => $url );
	}

	gm_set_status( $id, 'confirmed' );
	return array( 'order' => gm_order_summary( $id ) );
}
