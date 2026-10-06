<?php
/**
 * Card payments through Stripe Checkout (hosted payment page).
 *
 * Flow: checkout creates a pending order → customer pays on stripe.com →
 * Stripe sends them back to /?gm_stripe=success… and (optionally) calls the
 * webhook. Either path marks the order confirmed once Stripe says it is paid.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_stripe_request( $method, $path, $body = array() ) {
	$response = wp_remote_request(
		'https://api.stripe.com/v1/' . $path,
		array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array( 'Authorization' => 'Bearer ' . trim( gm_setting( 'stripe_secret' ) ) ),
			'body'    => $body ? $body : null,
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$code = wp_remote_retrieve_response_code( $response );
	if ( $code >= 300 || ! is_array( $data ) ) {
		$message = $data['error']['message'] ?? 'Card payments are unavailable right now.';
		return new WP_Error( 'gm_stripe', $message );
	}
	return $data;
}

/**
 * Start a Stripe Checkout session for a pending order.
 *
 * @return string|WP_Error URL to send the customer to.
 */
function gm_stripe_checkout_url( $id ) {
	$items      = array_filter( (array) get_post_meta( $id, '_gm_items', true ), 'is_array' );
	$line_items = array();
	foreach ( $items as $line ) {
		$line_items[] = array(
			'quantity'   => (int) $line['qty'],
			'price_data' => array(
				'currency'     => 'gbp',
				'unit_amount'  => (int) $line['pence'],
				'product_data' => array( 'name' => $line['name'] . ( ! empty( $line['spice'] ) ? ' — ' . gm_spice_levels()[ $line['spice'] ]['short'] : '' ) ),
			),
		);
	}

	$fee = (int) get_post_meta( $id, '_gm_delivery', true );
	if ( $fee > 0 ) {
		$line_items[] = array(
			'quantity'   => 1,
			'price_data' => array(
				'currency'     => 'gbp',
				'unit_amount'  => $fee,
				'product_data' => array( 'name' => 'Delivery — ' . gm_delivery_label( $id ) ),
			),
		);
	}

	// Discounts become a one-off Stripe coupon (Checkout can't take negative lines).
	$discounts = array();
	$off       = (int) get_post_meta( $id, '_gm_discount_pence', true ) + (int) get_post_meta( $id, '_gm_loyalty_pence', true );
	if ( $off > 0 ) {
		$labels = wp_list_pluck( gm_order_discount_lines( $id ), 'label' );
		$coupon = gm_stripe_request(
			'POST',
			'coupons',
			array(
				'amount_off'      => $off,
				'currency'        => 'gbp',
				'duration'        => 'once',
				'max_redemptions' => 1,
				'name'            => mb_substr( $labels ? implode( ' + ', $labels ) : 'Discount', 0, 40 ),
			)
		);
		if ( is_wp_error( $coupon ) ) {
			return $coupon;
		}
		$discounts = array( array( 'coupon' => $coupon['id'] ) );
	}

	$base    = home_url( '/' );
	$params  = array(
		'mode'                => 'payment',
		'line_items'          => $line_items,
		'customer_email'      => get_post_meta( $id, '_gm_email', true ),
		'client_reference_id' => (string) $id,
		'metadata'            => array(
			'order_id' => (string) $id,
			'ref'      => gm_order_ref( $id ),
		),
		'payment_intent_data' => array(
			'description' => 'Ghar Masala ' . gm_order_ref( $id ) . ' — ' . gm_slot_label( get_post_meta( $id, '_gm_slot_date', true ), get_post_meta( $id, '_gm_slot_time', true ) ),
		),
		// Stripe's minimum; the slot hold (hold_minutes) is a little longer.
		'expires_at'          => time() + 31 * MINUTE_IN_SECONDS,
		// Stripe fills in {CHECKOUT_SESSION_ID} itself, so it must not be URL-encoded.
		'success_url'         => add_query_arg( array( 'gm_stripe' => 'success', 'gm_order' => $id ), $base ) . '&session_id={CHECKOUT_SESSION_ID}',
		'cancel_url'          => add_query_arg( array( 'gm_stripe' => 'cancel', 'gm_order' => $id, 'gm_key' => gm_order_key( $id ) ), $base ),
	);
	if ( $discounts ) {
		$params['discounts'] = $discounts;
	}
	$session = gm_stripe_request( 'POST', 'checkout/sessions', $params );
	if ( is_wp_error( $session ) ) {
		return $session;
	}
	update_post_meta( $id, '_gm_stripe_session', $session['id'] );
	return $session['url'];
}

/** A per-order secret so only the customer's own cancel link can cancel it. */
function gm_order_key( $id ) {
	return substr( wp_hash( 'gm_order_' . $id . '_' . get_post_meta( $id, '_gm_created', true ) ), 0, 20 );
}

/** Ask Stripe whether the order's session has been paid, and confirm it if so. */
function gm_stripe_sync( $id, $session_id ) {
	if ( ! $session_id || get_post_meta( $id, '_gm_stripe_session', true ) !== $session_id ) {
		return false;
	}
	if ( 'confirmed' === get_post_meta( $id, '_gm_status', true ) ) {
		return true;
	}
	$session = gm_stripe_request( 'GET', 'checkout/sessions/' . rawurlencode( $session_id ) );
	if ( is_wp_error( $session ) || 'paid' !== ( $session['payment_status'] ?? '' ) ) {
		return false;
	}
	gm_set_status( $id, 'confirmed' );
	return true;
}

/**
 * Handle the customer coming back from Stripe. The result is handed to the
 * page's JavaScript through gm_return_state().
 */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['gm_stripe'] ) || empty( $_GET['gm_order'] ) ) {
		return;
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'ghar masala checkout return' );

	$id = absint( $_GET['gm_order'] );
	if ( 'gm_order' !== get_post_type( $id ) ) {
		return;
	}

	if ( 'success' === $_GET['gm_stripe'] ) {
		$session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ?? '' ) );
		if ( gm_stripe_sync( $id, $session_id ) ) {
			$GLOBALS['gm_return'] = array( 'status' => 'paid', 'order' => gm_order_summary( $id ) );
		} elseif ( get_post_meta( $id, '_gm_stripe_session', true ) === $session_id ) {
			$GLOBALS['gm_return'] = array( 'status' => 'processing', 'order' => gm_order_summary( $id ) );
		}
	} elseif ( 'cancel' === $_GET['gm_stripe'] ) {
		$key = sanitize_text_field( wp_unslash( $_GET['gm_key'] ?? '' ) );
		if ( hash_equals( gm_order_key( $id ), $key ) && 'pending' === get_post_meta( $id, '_gm_status', true ) ) {
			update_post_meta( $id, '_gm_status', 'cancelled' );
			$GLOBALS['gm_return'] = array( 'status' => 'cancelled' );
		}
	}
} );

function gm_return_state() {
	return $GLOBALS['gm_return'] ?? null;
}

/**
 * Webhook: checkout.session.completed.
 */
function gm_stripe_webhook( WP_REST_Request $request ) {
	$secret = trim( (string) gm_setting( 'stripe_webhook' ) );
	$body   = $request->get_body();
	$header = (string) $request->get_header( 'stripe_signature' );
	if ( ! $secret || ! gm_stripe_signature_ok( $body, $header, $secret ) ) {
		return new WP_REST_Response( array( 'error' => 'bad signature' ), 400 );
	}
	$event = json_decode( $body, true );
	if ( 'checkout.session.completed' === ( $event['type'] ?? '' ) ) {
		$session = $event['data']['object'];
		$id      = absint( $session['metadata']['order_id'] ?? 0 );
		if ( $id && 'gm_order' === get_post_type( $id ) && 'paid' === ( $session['payment_status'] ?? '' )
			&& get_post_meta( $id, '_gm_stripe_session', true ) === $session['id'] ) {
			if ( 'confirmed' !== get_post_meta( $id, '_gm_status', true ) ) {
				gm_set_status( $id, 'confirmed' );
			}
		}
	}
	return new WP_REST_Response( array( 'received' => true ), 200 );
}

function gm_stripe_signature_ok( $payload, $header, $secret ) {
	$timestamp  = null;
	$signatures = array();
	foreach ( explode( ',', $header ) as $part ) {
		$kv = explode( '=', trim( $part ), 2 );
		if ( 2 !== count( $kv ) ) {
			continue;
		}
		if ( 't' === $kv[0] ) {
			$timestamp = (int) $kv[1];
		} elseif ( 'v1' === $kv[0] ) {
			$signatures[] = $kv[1];
		}
	}
	if ( ! $timestamp || abs( time() - $timestamp ) > 300 ) {
		return false;
	}
	$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
	foreach ( $signatures as $sig ) {
		if ( hash_equals( $expected, $sig ) ) {
			return true;
		}
	}
	return false;
}
