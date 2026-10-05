<?php
/**
 * Contact form: messages are emailed to the kitchen and kept in
 * WP Admin → Enquiries so none get lost.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type(
		'gm_enquiry',
		array(
			'labels'        => array(
				'name'          => 'Enquiries',
				'singular_name' => 'Enquiry',
				'all_items'     => 'All enquiries',
				'edit_item'     => 'Enquiry',
				'search_items'  => 'Search enquiries',
				'not_found'     => 'No enquiries yet.',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 4,
			'menu_icon'     => 'dashicons-email-alt',
			'supports'      => array( 'title' ),
			'capabilities'  => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'  => true,
		)
	);
} );

add_action( 'wp_ajax_nopriv_gm_contact', 'gm_ajax_contact' );
add_action( 'wp_ajax_gm_contact', 'gm_ajax_contact' );

function gm_ajax_contact() {
	nocache_headers();
	$name    = sanitize_text_field( gm_post( 'name' ) );
	$phone   = sanitize_text_field( gm_post( 'phone' ) );
	$email   = sanitize_email( gm_post( 'email' ) );
	$message = sanitize_textarea_field( mb_substr( (string) gm_post( 'message' ), 0, 2000 ) );

	if ( '' !== gm_post( 'website' ) ) { // Honeypot.
		gm_account_fail( 'Something went wrong — please try again.' );
	}
	if ( '' === $name ) {
		gm_account_fail( 'Please enter your name.', 422 );
	}
	if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 10 ) {
		gm_account_fail( 'Please enter a contact number we can call you back on.', 422 );
	}
	$ip_key = 'gm_contact_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	if ( (int) get_transient( $ip_key ) >= 5 ) {
		gm_account_fail( 'Thank you — we have your messages. Please ring us if it is urgent.', 429 );
	}
	set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );

	$id = wp_insert_post(
		array(
			'post_type'   => 'gm_enquiry',
			'post_status' => 'private',
			'post_title'  => $name . ' — ' . $phone,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		gm_account_fail( 'Your message could not be sent — please ring us instead.', 500 );
	}
	update_post_meta( $id, '_gm_name', $name );
	update_post_meta( $id, '_gm_phone', $phone );
	update_post_meta( $id, '_gm_email', $email );
	update_post_meta( $id, '_gm_message', $message );

	wp_mail(
		gm_setting( 'order_email' ),
		'New enquiry from ' . $name,
		"Name: $name\nContact number: $phone\n" . ( $email ? "Email: $email\n" : '' ) . "\n" . ( $message ? $message : '(no message — please call back)' ) . "\n\nAll enquiries: " . admin_url( 'edit.php?post_type=gm_enquiry' ) . "\n",
		$email ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array()
	);

	wp_send_json_success( array( 'message' => 'Thank you, ' . strtok( $name, ' ' ) . ' — we have your message and will be in touch soon.' ) );
}

add_filter( 'manage_gm_enquiry_posts_columns', function () {
	return array(
		'cb'         => '<input type="checkbox">',
		'title'      => 'From',
		'gm_message' => 'Message',
		'date'       => 'Received',
	);
} );
add_action( 'manage_gm_enquiry_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_message' === $col ) {
		echo esc_html( wp_trim_words( get_post_meta( $id, '_gm_message', true ), 20 ) );
	}
}, 10, 2 );

add_action( 'add_meta_boxes_gm_enquiry', function () {
	remove_meta_box( 'slugdiv', 'gm_enquiry', 'normal' );
	add_meta_box( 'gm_enquiry_details', 'Message', function ( $post ) {
		$m = function ( $k ) use ( $post ) {
			return get_post_meta( $post->ID, '_gm_' . $k, true );
		};
		echo '<p><strong>Name:</strong> ' . esc_html( $m( 'name' ) ) . '</p>';
		echo '<p><strong>Contact number:</strong> <a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $m( 'phone' ) ) ) . '">' . esc_html( $m( 'phone' ) ) . '</a></p>';
		if ( $m( 'email' ) ) {
			echo '<p><strong>Email:</strong> <a href="mailto:' . esc_attr( $m( 'email' ) ) . '">' . esc_html( $m( 'email' ) ) . '</a></p>';
		}
		echo '<p><strong>Message:</strong><br>' . nl2br( esc_html( $m( 'message' ) ? $m( 'message' ) : '(none — please call back)' ) ) . '</p>';
	}, 'gm_enquiry', 'normal', 'high' );
} );
