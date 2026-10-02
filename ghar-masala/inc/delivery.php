<?php
/**
 * Delivery charge from the customer's postcode.
 *
 * Postcodes are turned into map coordinates with postcodes.io (free, UK
 * Ordnance Survey data, no API key) and the straight-line distance from the
 * kitchen's postcode decides the charge:
 *   up to `free_miles`  → free
 *   beyond that         → `per_mile` for every started mile
 *   beyond `max_miles`  → outside the delivery area
 * The rates live in Settings → Ghar Masala.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_delivery_rules() {
	return array(
		'from'       => gm_setting( 'delivery_from' ),
		'area'       => gm_setting( 'delivery_area' ) ? gm_setting( 'delivery_area' ) : gm_setting( 'delivery_from' ),
		'free_miles' => (float) gm_setting( 'free_miles' ),
		'per_mile'   => (int) round( (float) gm_setting( 'per_mile' ) * 100 ),
		'max_miles'  => (float) gm_setting( 'max_miles' ),
	);
}

/** "b691ny" → "B691NY", or '' when it can't be a UK postcode. */
function gm_postcode_key( $postcode ) {
	$key = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $postcode ) );
	return preg_match( '/^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/', $key ) ? $key : '';
}

/**
 * Look a postcode up on postcodes.io.
 *
 * @return array|WP_Error [ 'postcode' => 'B69 1NY', 'lat' => …, 'lng' => … ]
 */
function gm_postcode_lookup( $postcode ) {
	$key = gm_postcode_key( $postcode );
	if ( ! $key ) {
		return new WP_Error( 'gm_postcode_unknown', 'Please enter a full UK postcode, like B69 1NY.' );
	}

	$cache = get_transient( 'gm_pc_' . $key );
	if ( is_array( $cache ) ) {
		return $cache['found'] ? $cache : new WP_Error( 'gm_postcode_unknown', 'We could not find that postcode — please check it.' );
	}

	$response = wp_remote_get( 'https://api.postcodes.io/postcodes/' . rawurlencode( $key ), array( 'timeout' => 8 ) );
	$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

	if ( 404 === $code ) {
		set_transient( 'gm_pc_' . $key, array( 'found' => false ), DAY_IN_SECONDS );
		return new WP_Error( 'gm_postcode_unknown', 'We could not find that postcode — please check it.' );
	}
	$data = 200 === $code ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;
	if ( ! isset( $data['result']['latitude'], $data['result']['longitude'] ) ) {
		return new WP_Error( 'gm_postcode_service', 'The postcode checker is not responding.' );
	}

	$found = array(
		'found'    => true,
		'postcode' => $data['result']['postcode'],
		'lat'      => (float) $data['result']['latitude'],
		'lng'      => (float) $data['result']['longitude'],
	);
	set_transient( 'gm_pc_' . $key, $found, 30 * DAY_IN_SECONDS );
	return $found;
}

/** Straight-line ("as the crow flies") distance in miles. */
function gm_distance_miles( array $a, array $b ) {
	$r    = 3958.8;
	$dlat = deg2rad( $b['lat'] - $a['lat'] );
	$dlng = deg2rad( $b['lng'] - $a['lng'] );
	$h    = sin( $dlat / 2 ) ** 2 + cos( deg2rad( $a['lat'] ) ) * cos( deg2rad( $b['lat'] ) ) * sin( $dlng / 2 ) ** 2;
	return 2 * $r * asin( min( 1, sqrt( $h ) ) );
}

/**
 * Delivery charge for a postcode.
 *
 * @return array|WP_Error {
 *     postcode string  Tidied postcode.
 *     miles    float   Distance, 1 decimal place.
 *     fee      int     Charge in pence.
 *     ok       bool    False when outside the delivery area.
 *     message  string  Line to show the customer.
 * }
 */
function gm_delivery_quote( $postcode ) {
	$rules = gm_delivery_rules();
	$to    = gm_postcode_lookup( $postcode );
	if ( is_wp_error( $to ) ) {
		return $to;
	}
	$from = gm_postcode_lookup( $rules['from'] );
	if ( is_wp_error( $from ) ) {
		return new WP_Error( 'gm_postcode_service', 'The postcode checker is not responding.' );
	}

	$miles = round( gm_distance_miles( $from, $to ), 2 );
	$shown = number_format( $miles, 1 );
	$quote = array(
		'postcode' => $to['postcode'],
		'miles'    => (float) $shown,
		'fee'      => 0,
		'ok'       => true,
		'message'  => '',
	);

	if ( $miles > $rules['max_miles'] ) {
		if ( (float) $shown <= $rules['max_miles'] ) {
			$shown = number_format( $miles, 2 ); // "3.04", not a confusing "3.0".
		}
		$quote['ok']      = false;
		$quote['message'] = sprintf(
			'Sorry — %s is %s miles away. We deliver up to %s miles from %s.',
			$to['postcode'],
			$shown,
			gm_number( $rules['max_miles'] ),
			$rules['area']
		);
		return $quote;
	}

	if ( $miles > $rules['free_miles'] ) {
		$quote['fee'] = (int) ceil( round( $miles - $rules['free_miles'], 2 ) ) * $rules['per_mile'];
	}
	$quote['message'] = sprintf(
		'%s is %s miles away — %s.',
		$to['postcode'],
		$shown,
		$quote['fee'] ? gm_money( $quote['fee'] ) . ' delivery' : 'free delivery'
	);
	return $quote;
}

/** 2.0 → "2", 2.5 → "2.5" */
function gm_number( $n ) {
	return rtrim( rtrim( number_format( (float) $n, 1 ), '0' ), '.' );
}

/** One-line summary of the delivery rules, for page copy. */
function gm_delivery_summary() {
	$r = gm_delivery_rules();
	return sprintf(
		'Free within %s miles of %s, then %s for each extra mile, up to %s miles.',
		gm_number( $r['free_miles'] ),
		$r['area'],
		gm_money( $r['per_mile'] ),
		gm_number( $r['max_miles'] )
	);
}

add_action( 'rest_api_init', function () {
	register_rest_route(
		'ghar-masala/v1',
		'/delivery',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array( 'postcode' => array( 'type' => 'string', 'required' => true ) ),
			'callback'            => function ( WP_REST_Request $request ) {
				do_action( 'litespeed_control_set_nocache', 'ghar masala delivery quote' );
				$quote = gm_delivery_quote( $request['postcode'] );
				if ( is_wp_error( $quote ) ) {
					$quote->add_data( array( 'status' => 'gm_postcode_unknown' === $quote->get_error_code() ? 422 : 503 ) );
				}
				return $quote;
			},
		)
	);
} );
