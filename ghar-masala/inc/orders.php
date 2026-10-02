<?php
/**
 * Orders: storage, delivery slots, emails and the admin screen.
 *
 * Each order is a private `gm_order` post. Its status lives in `_gm_status`:
 *   pending   – waiting for card payment (holds its slot for a short time)
 *   confirmed – paid by card, or booked as pay-on-delivery
 *   cancelled – frees the slot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_tz() {
	return new DateTimeZone( gm_rules()['timezone'] );
}

function gm_now() {
	return new DateTimeImmutable( 'now', gm_tz() );
}

add_action( 'init', function () {
	register_post_type(
		'gm_order',
		array(
			'labels'          => array(
				'name'          => 'Orders',
				'singular_name' => 'Order',
				'menu_name'     => 'Orders',
				'all_items'     => 'All orders',
				'edit_item'     => 'Order',
				'search_items'  => 'Search orders',
				'not_found'     => 'No orders yet.',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 3,
			'menu_icon'       => 'dashicons-food',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
} );

/* ---------------------------------------------------------------------------
 * Slots
 * ------------------------------------------------------------------------ */

/** "18:30" → "6:30" */
function gm_clock( $hhmm ) {
	list( $h, $m ) = array_map( 'intval', explode( ':', $hhmm ) );
	$h12           = $h > 12 ? $h - 12 : $h;
	return $h12 . ':' . str_pad( (string) $m, 2, '0', STR_PAD_LEFT );
}

function gm_slot_window( $hhmm ) {
	$start = DateTimeImmutable::createFromFormat( 'H:i', $hhmm, gm_tz() );
	$end   = $start->modify( '+' . gm_rules()['window_mins'] . ' minutes' );
	return gm_clock( $hhmm ) . '–' . gm_clock( $end->format( 'H:i' ) ) . 'pm';
}

/** "Thursday 8 October, 6:30–6:45pm" */
function gm_slot_label( $date, $hhmm ) {
	$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, gm_tz() );
	return $day->format( 'l j F' ) . ', ' . gm_slot_window( $hhmm );
}

/** Whether orders for $date are still being taken right now. */
function gm_day_open( DateTimeImmutable $day ) {
	$rules = gm_rules();
	if ( in_array( (int) $day->format( 'w' ), $rules['closed_days'], true ) ) {
		return false;
	}
	$cutoff = $day->modify( '-1 day' )->setTime( $rules['cutoff_hour'], 0 );
	return gm_now() < $cutoff;
}

/**
 * Taken slot times keyed by date: [ '2026-10-08' => [ '18:30', … ] ].
 */
function gm_taken_slots( $from, $to, $exclude_id = 0 ) {
	$ids = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_gm_slot_date',
					'value'   => array( $from, $to ),
					'compare' => 'BETWEEN', // Y-m-d strings sort correctly as text.
				),
			),
		)
	);
	$hold_since = time() - gm_rules()['hold_minutes'] * MINUTE_IN_SECONDS;
	$taken      = array();
	foreach ( $ids as $id ) {
		if ( (int) $id === (int) $exclude_id ) {
			continue;
		}
		$status = get_post_meta( $id, '_gm_status', true );
		$live   = 'confirmed' === $status
			|| ( 'pending' === $status && (int) get_post_meta( $id, '_gm_created', true ) > $hold_since );
		if ( $live ) {
			$taken[ get_post_meta( $id, '_gm_slot_date', true ) ][] = get_post_meta( $id, '_gm_slot_time', true );
		}
	}
	return $taken;
}

/**
 * The bookable calendar, starting tomorrow.
 */
function gm_calendar() {
	$days_ahead = (int) gm_setting( 'days_ahead' );
	$today      = gm_now()->setTime( 0, 0 );
	$first      = $today->modify( '+1 day' );
	$last       = $today->modify( '+' . $days_ahead . ' days' );
	$taken      = gm_taken_slots( $first->format( 'Y-m-d' ), $last->format( 'Y-m-d' ) );
	$rules      = gm_rules();
	$out        = array();

	for ( $i = 1; $i <= $days_ahead; $i++ ) {
		$day    = $today->modify( '+' . $i . ' days' );
		$date   = $day->format( 'Y-m-d' );
		$closed = in_array( (int) $day->format( 'w' ), $rules['closed_days'], true );
		$open   = gm_day_open( $day );
		$slots  = array();
		foreach ( $rules['slot_starts'] as $t ) {
			$slots[] = array(
				'time'   => $t,
				'label'  => gm_clock( $t ) . 'pm',
				'window' => gm_slot_window( $t ),
				'taken'  => in_array( $t, $taken[ $date ] ?? array(), true ),
			);
		}
		$out[] = array(
			'date'      => $date,
			'weekday'   => $day->format( 'D' ),
			'dateLabel' => $day->format( 'j M' ),
			'long'      => $day->format( 'l j F' ),
			'open'      => $open,
			'status'    => $open ? 'open' : ( $closed ? 'Closed' : 'Orders closed' ),
			'slots'     => $slots,
		);
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Orders
 * ------------------------------------------------------------------------ */

function gm_order_ref( $id ) {
	return 'GM-' . ( 1000 + (int) $id );
}

function gm_set_status( $id, $status ) {
	update_post_meta( $id, '_gm_status', $status );
	if ( 'confirmed' === $status ) {
		gm_send_order_emails( $id );
	}
}

/** Public summary of an order, as the site's front end shows it. */
function gm_order_summary( $id ) {
	$items = (array) get_post_meta( $id, '_gm_items', true );
	$lines = array();
	foreach ( $items as $line ) {
		$lines[] = array(
			'id'    => $line['id'],
			'qty'   => (int) $line['qty'],
			'name'  => $line['name'],
			'note'  => $line['note'],
			'total' => gm_money( $line['qty'] * $line['pence'] ),
		);
	}
	$date = get_post_meta( $id, '_gm_slot_date', true );
	$time = get_post_meta( $id, '_gm_slot_time', true );
	$day  = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, gm_tz() );
	$total    = (int) get_post_meta( $id, '_gm_total', true );
	$subtotal = get_post_meta( $id, '_gm_subtotal', true );
	$subtotal = '' === $subtotal ? $total : (int) $subtotal;
	return array(
		'ref'      => gm_order_ref( $id ),
		'status'   => get_post_meta( $id, '_gm_status', true ),
		'payment'  => get_post_meta( $id, '_gm_payment', true ),
		'slot'     => gm_slot_label( $date, $time ),
		'date'     => $day ? $day->format( 'D j F' ) : $date,
		'window'   => gm_slot_window( $time ),
		'lines'    => $lines,
		'subtotal'    => gm_money( $subtotal ),
		'delivery'    => gm_delivery_label( $id ),
		'total'       => gm_money( $total ),
		'total_pence' => $total,
		'discount'    => get_post_meta( $id, '_gm_discount', true ),
	);
}

/** "£1.00 (2.4 miles)", "Free (1.2 miles)", or a warning when unchecked. */
function gm_delivery_label( $id ) {
	$fee   = (int) get_post_meta( $id, '_gm_delivery', true );
	$miles = get_post_meta( $id, '_gm_miles', true );
	if ( '' === $miles ) {
		return metadata_exists( 'post', $id, '_gm_delivery' ) ? 'Not checked — postcode lookup was down' : 'Free';
	}
	return ( $fee ? gm_money( $fee ) : 'Free' ) . ' (' . number_format( (float) $miles, 1 ) . ' miles)';
}

/**
 * Validate a checkout payload and store it as a pending order.
 *
 * @return int|WP_Error Order ID.
 */
function gm_create_order( array $data ) {
	$index = gm_menu_index();
	$rules = gm_rules();

	// Basket.
	$items = array();
	$total = 0;
	foreach ( (array) ( $data['items'] ?? array() ) as $id => $qty ) {
		$qty = (int) $qty;
		if ( ! isset( $index[ $id ] ) || $qty < 1 ) {
			continue;
		}
		$qty     = min( $qty, 50 );
		$items[] = array(
			'id'    => $id,
			'name'  => $index[ $id ]['name'],
			'qty'   => $qty,
			'pence' => $index[ $id ]['pence'],
			'note'  => sanitize_text_field( mb_substr( (string) ( $data['notes'][ $id ] ?? '' ), 0, 200 ) ),
		);
		$total  += $qty * $index[ $id ]['pence'];
	}
	if ( ! $items ) {
		return new WP_Error( 'gm_empty', 'Your basket is empty.' );
	}
	if ( $total < (int) round( $rules['min_order'] * 100 ) ) {
		return new WP_Error( 'gm_minimum', 'The minimum order is ' . gm_money( (int) round( $rules['min_order'] * 100 ) ) . '.' );
	}

	// Slot.
	$date = (string) ( $data['date'] ?? '' );
	$time = (string) ( $data['time'] ?? '' );
	$day  = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, gm_tz() );
	if ( ! $day || $day->format( 'Y-m-d' ) !== $date || ! in_array( $time, $rules['slot_starts'], true ) ) {
		return new WP_Error( 'gm_slot', 'Please choose a delivery slot.' );
	}
	$last = gm_now()->setTime( 0, 0 )->modify( '+' . (int) gm_setting( 'days_ahead' ) . ' days' );
	if ( ! gm_day_open( $day ) || $day > $last ) {
		return new WP_Error( 'gm_slot_closed', 'Orders for that day have closed — please pick another slot.' );
	}
	if ( in_array( $time, gm_taken_slots( $date, $date )[ $date ] ?? array(), true ) ) {
		return new WP_Error( 'gm_slot_taken', 'Sorry, that slot has just been taken — please pick another.' );
	}

	// Customer.
	$customer = array(
		'name'     => sanitize_text_field( $data['name'] ?? '' ),
		'email'    => sanitize_email( $data['email'] ?? '' ),
		'phone'    => sanitize_text_field( $data['phone'] ?? '' ),
		'address'  => sanitize_text_field( $data['address'] ?? '' ),
		'postcode' => strtoupper( sanitize_text_field( $data['postcode'] ?? '' ) ),
		'notes'    => sanitize_textarea_field( mb_substr( (string) ( $data['instructions'] ?? '' ), 0, 500 ) ),
		'discount' => sanitize_text_field( mb_substr( (string) ( $data['discount'] ?? '' ), 0, 40 ) ),
	);
	foreach ( array( 'name' => 'your name', 'address' => 'your address', 'postcode' => 'your postcode', 'phone' => 'a mobile number' ) as $key => $label ) {
		if ( '' === $customer[ $key ] ) {
			return new WP_Error( 'gm_field_' . $key, 'Please enter ' . $label . '.' );
		}
	}
	if ( ! is_email( $customer['email'] ) ) {
		return new WP_Error( 'gm_field_email', 'Please enter a valid email address.' );
	}

	// Delivery charge. If postcodes.io is down the order still goes through,
	// flagged so the kitchen checks the distance itself.
	$delivery = gm_delivery_quote( $customer['postcode'] );
	if ( is_wp_error( $delivery ) && 'gm_postcode_unknown' === $delivery->get_error_code() ) {
		return new WP_Error( 'gm_field_postcode', $delivery->get_error_message() );
	}
	if ( ! is_wp_error( $delivery ) && ! $delivery['ok'] ) {
		return new WP_Error( 'gm_out_of_area', $delivery['message'] );
	}
	$checked = ! is_wp_error( $delivery );
	$fee     = $checked ? $delivery['fee'] : 0;
	if ( $checked ) {
		$customer['postcode'] = $delivery['postcode'];
	}

	$payment = ( 'card' === ( $data['payment'] ?? '' ) && gm_stripe_enabled() ) ? 'card' : 'cod';
	if ( 'cod' === $payment && ! gm_cod_enabled() ) {
		return new WP_Error( 'gm_payment', 'Please pay by card.' );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'gm_order',
			'post_status' => 'publish',
			'post_title'  => 'New order',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return $id;
	}

	$meta = array(
		'_gm_status'    => 'pending',
		'_gm_payment'   => $payment,
		'_gm_slot_date' => $date,
		'_gm_slot_time' => $time,
		'_gm_items'     => $items,
		'_gm_subtotal'  => $total,
		'_gm_delivery'  => $fee,
		'_gm_miles'     => $checked ? $delivery['miles'] : '',
		'_gm_total'     => $total + $fee,
		'_gm_user'      => get_current_user_id(),
		'_gm_created'   => time(),
	);
	foreach ( $customer as $key => $value ) {
		$meta[ '_gm_' . $key ] = $value;
	}
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	wp_update_post(
		array(
			'ID'         => $id,
			'post_title' => gm_order_ref( $id ) . ' — ' . $customer['name'],
		)
	);

	// Two checkouts can race for the same slot; the earlier order keeps it.
	foreach ( gm_order_ids_for_slot( $date, $time ) as $other ) {
		if ( $other < $id ) {
			update_post_meta( $id, '_gm_status', 'cancelled' );
			return new WP_Error( 'gm_slot_taken', 'Sorry, that slot has just been taken — please pick another.' );
		}
	}

	return $id;
}

function gm_order_ids_for_slot( $date, $time ) {
	$taken = array();
	$ids   = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_gm_slot_date', 'value' => $date ),
				array( 'key' => '_gm_slot_time', 'value' => $time ),
			),
		)
	);
	$hold_since = time() - gm_rules()['hold_minutes'] * MINUTE_IN_SECONDS;
	foreach ( $ids as $id ) {
		$status = get_post_meta( $id, '_gm_status', true );
		if ( 'confirmed' === $status || ( 'pending' === $status && (int) get_post_meta( $id, '_gm_created', true ) > $hold_since ) ) {
			$taken[] = (int) $id;
		}
	}
	return $taken;
}

/** Orders placed by a signed-in customer, newest first. */
function gm_user_orders( $user_id, $limit = 20 ) {
	$ids = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_gm_user', 'value' => (int) $user_id ),
				array( 'key' => '_gm_status', 'value' => 'confirmed' ),
			),
		)
	);
	return array_map( 'gm_order_summary', $ids );
}

/* ---------------------------------------------------------------------------
 * Emails
 * ------------------------------------------------------------------------ */

function gm_send_order_emails( $id ) {
	if ( get_post_meta( $id, '_gm_emailed', true ) ) {
		return;
	}
	update_post_meta( $id, '_gm_emailed', 1 );

	$o    = gm_order_summary( $id );
	$m    = function ( $key ) use ( $id ) {
		return get_post_meta( $id, '_gm_' . $key, true );
	};
	$paid = 'card' === $o['payment'] ? 'Paid by card' : 'Pay on delivery';

	$lines = '';
	foreach ( $o['lines'] as $line ) {
		$lines .= sprintf( "%d × %s — %s\n", $line['qty'], $line['name'], $line['total'] );
		if ( $line['note'] ) {
			$lines .= '    Note: ' . $line['note'] . "\n";
		}
	}

	$details = "Order {$o['ref']}\nDelivery: {$o['slot']}\n\n{$lines}\nSubtotal: {$o['subtotal']}\nDelivery charge: {$o['delivery']}\nTotal: {$o['total']} ({$paid})\n";
	if ( $o['discount'] ) {
		$details .= "Discount code entered: {$o['discount']}\n";
	}

	$kitchen = "New order!\n\n{$details}\nCustomer\n"
		. $m( 'name' ) . "\n" . $m( 'address' ) . ', ' . $m( 'postcode' ) . "\n" . $m( 'phone' ) . "\n" . $m( 'email' ) . "\n";
	if ( $m( 'notes' ) ) {
		$kitchen .= "\nSpice level & allergy notes:\n" . $m( 'notes' ) . "\n";
	}
	$kitchen .= "\nManage orders: " . admin_url( 'edit.php?post_type=gm_order' ) . "\n";

	wp_mail(
		gm_setting( 'order_email' ),
		"New order {$o['ref']} — {$o['slot']}",
		$kitchen,
		array( 'Reply-To: ' . $m( 'name' ) . ' <' . $m( 'email' ) . '>' )
	);

	$customer = 'Hi ' . $m( 'name' ) . ",\n\nThank you — your Ghar Masala order is booked in.\n\n{$details}\n"
		. 'Delivering to: ' . $m( 'address' ) . ', ' . $m( 'postcode' ) . "\n\n"
		. 'We will text you when the food leaves the kitchen. Any changes, ring ' . gm_setting( 'phone' ) . ".\n\nGhar Masala — tradition served with comfort\n" . home_url( '/' ) . "\n";

	wp_mail( $m( 'email' ), "Your Ghar Masala order {$o['ref']}", $customer );
}

/* ---------------------------------------------------------------------------
 * Admin screen
 * ------------------------------------------------------------------------ */

add_filter( 'manage_gm_order_posts_columns', function () {
	return array(
		'cb'         => '<input type="checkbox">',
		'title'      => 'Order',
		'gm_slot'    => 'Delivery slot',
		'gm_total'   => 'Total',
		'gm_payment' => 'Payment',
		'gm_status'  => 'Status',
		'date'       => 'Placed',
	);
} );

add_action( 'manage_gm_order_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'gm_slot':
			echo esc_html( gm_slot_label( get_post_meta( $id, '_gm_slot_date', true ), get_post_meta( $id, '_gm_slot_time', true ) ) );
			break;
		case 'gm_total':
			echo esc_html( gm_money( (int) get_post_meta( $id, '_gm_total', true ) ) );
			break;
		case 'gm_payment':
			echo 'card' === get_post_meta( $id, '_gm_payment', true ) ? 'Card' : 'On delivery';
			break;
		case 'gm_status':
			echo esc_html( gm_status_label( $id ) );
			break;
	}
}, 10, 2 );

function gm_status_label( $id ) {
	$status = get_post_meta( $id, '_gm_status', true );
	if ( 'pending' === $status ) {
		$expired = (int) get_post_meta( $id, '_gm_created', true ) < time() - gm_rules()['hold_minutes'] * MINUTE_IN_SECONDS;
		return $expired ? 'Unpaid (abandoned)' : 'Awaiting payment';
	}
	return 'confirmed' === $status ? 'Confirmed' : 'Cancelled';
}

add_action( 'add_meta_boxes_gm_order', function () {
	add_meta_box( 'gm_order_details', 'Order details', 'gm_render_order_box', 'gm_order', 'normal', 'high' );
} );

function gm_render_order_box( $post ) {
	$id = $post->ID;
	$o  = gm_order_summary( $id );
	$m  = function ( $key ) use ( $id ) {
		return get_post_meta( $id, '_gm_' . $key, true );
	};
	wp_nonce_field( 'gm_order_status', 'gm_order_status_nonce' );
	?>
	<table class="widefat striped" style="margin-bottom:16px">
		<thead><tr><th>Dish</th><th>Qty</th><th>Note</th><th style="text-align:right">Total</th></tr></thead>
		<tbody>
		<?php foreach ( $o['lines'] as $line ) : ?>
			<tr><td><?php echo esc_html( $line['name'] ); ?></td><td><?php echo (int) $line['qty']; ?></td><td><?php echo esc_html( $line['note'] ); ?></td><td style="text-align:right"><?php echo esc_html( $line['total'] ); ?></td></tr>
		<?php endforeach; ?>
		<tr><td colspan="3">Delivery</td><td style="text-align:right"><?php echo esc_html( $o['delivery'] ); ?></td></tr>
		<tr><th colspan="3">Total</th><th style="text-align:right"><?php echo esc_html( $o['total'] ); ?></th></tr>
		</tbody>
	</table>
	<p><strong>Delivery slot:</strong> <?php echo esc_html( $o['slot'] ); ?></p>
	<p><strong>Customer:</strong> <?php echo esc_html( $m( 'name' ) ); ?><br>
		<?php echo esc_html( $m( 'address' ) . ', ' . $m( 'postcode' ) ); ?><br>
		<a href="tel:<?php echo esc_attr( $m( 'phone' ) ); ?>"><?php echo esc_html( $m( 'phone' ) ); ?></a> ·
		<a href="mailto:<?php echo esc_attr( $m( 'email' ) ); ?>"><?php echo esc_html( $m( 'email' ) ); ?></a></p>
	<?php if ( $m( 'notes' ) ) : ?>
		<p><strong>Spice level &amp; allergy notes:</strong><br><?php echo nl2br( esc_html( $m( 'notes' ) ) ); ?></p>
	<?php endif; ?>
	<?php if ( $o['discount'] ) : ?>
		<p><strong>Discount code entered:</strong> <?php echo esc_html( $o['discount'] ); ?> <em>(not applied automatically — adjust the bill if valid)</em></p>
	<?php endif; ?>
	<p><strong>Payment:</strong> <?php echo 'card' === $o['payment'] ? 'Card (Stripe)' : 'Pay on delivery'; ?>
		<?php if ( $m( 'stripe_session' ) ) : ?> · Stripe session <code><?php echo esc_html( $m( 'stripe_session' ) ); ?></code><?php endif; ?></p>
	<p><label for="gm-status"><strong>Status:</strong></label>
		<select name="gm_status" id="gm-status">
			<?php foreach ( array( 'pending' => 'Awaiting payment', 'confirmed' => 'Confirmed', 'cancelled' => 'Cancelled (frees the slot)' ) as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $o['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description">Click “Update” to save.</span></p>
	<?php
}

add_action( 'save_post_gm_order', function ( $id ) {
	if ( ! isset( $_POST['gm_order_status_nonce'], $_POST['gm_status'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['gm_order_status_nonce'] ), 'gm_order_status' )
		|| ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$status = sanitize_key( $_POST['gm_status'] );
	if ( in_array( $status, array( 'pending', 'confirmed', 'cancelled' ), true ) && get_post_meta( $id, '_gm_status', true ) !== $status ) {
		gm_set_status( $id, $status );
	}
} );

// Newest orders first.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'gm_order' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'date' );
		$q->set( 'order', 'DESC' );
	}
} );

// Orders are edited through the details box only.
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'gm_order' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}
	return $actions;
}, 10, 2 );
