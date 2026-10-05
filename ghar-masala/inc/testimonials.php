<?php
/**
 * Testimonials, managed in WP Admin → Testimonials.
 *
 * Each testimonial is a private post: the title is the customer's name, the
 * text box is their quote, and "Order" sets the position on the page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type(
		'gm_testimonial',
		array(
			'labels'        => array(
				'name'               => 'Testimonials',
				'singular_name'      => 'Testimonial',
				'menu_name'          => 'Testimonials',
				'all_items'          => 'All testimonials',
				'add_new'            => 'Add testimonial',
				'add_new_item'       => 'Add a testimonial',
				'edit_item'          => 'Edit testimonial',
				'new_item'           => 'New testimonial',
				'search_items'       => 'Search testimonials',
				'not_found'          => 'No testimonials yet.',
				'not_found_in_trash' => 'No testimonials in the bin.',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false, // Simple text editor rather than the block editor.
			'menu_position' => 4,
			'menu_icon'     => 'dashicons-format-quote',
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
		)
	);
} );

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gm_testimonial' === $post->post_type ? 'Customer name, e.g. Sarah, Tividale' : $text;
}, 10, 2 );

add_action( 'edit_form_after_title', function ( $post ) {
	if ( 'gm_testimonial' === $post->post_type ) {
		echo '<p class="description" style="margin:14px 0 6px">Type the customer’s quote below — no quotation marks needed. Set “Order” on the right to choose its position (lowest first). Click <strong>Publish</strong> to show it on the site; save as a draft to hide it.</p>';
	}
} );

// Plain text box only — a quote needs no formatting toolbar or media button.
add_filter( 'user_can_richedit', function ( $can ) {
	return 'gm_testimonial' === get_post_type() ? false : $can;
} );
add_action( 'admin_head', function () {
	if ( 'gm_testimonial' === get_post_type() ) {
		remove_action( 'media_buttons', 'media_buttons' );
	}
} );

add_filter( 'manage_gm_testimonial_posts_columns', function () {
	return array(
		'cb'       => '<input type="checkbox">',
		'title'    => 'Customer',
		'gm_quote' => 'Quote',
		'gm_order' => 'Order',
		'date'     => 'Date',
	);
} );

add_action( 'manage_gm_testimonial_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_quote' === $col ) {
		echo esc_html( wp_trim_words( get_post_field( 'post_content', $id ), 25 ) );
	} elseif ( 'gm_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

/** Published testimonials, in the chosen order. */
function gm_testimonials() {
	return get_posts(
		array(
			'post_type'      => 'gm_testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);
}

add_action( 'add_meta_boxes_gm_testimonial', function () {
	remove_meta_box( 'slugdiv', 'gm_testimonial', 'normal' );
} );

/* ---------------------------------------------------------------------------
 * Customer-submitted reviews: saved as "Pending" until approved (Publish)
 * ------------------------------------------------------------------------ */

add_action( 'wp_ajax_nopriv_gm_review', 'gm_ajax_review' );
add_action( 'wp_ajax_gm_review', 'gm_ajax_review' );

function gm_ajax_review() {
	nocache_headers();
	$name   = sanitize_text_field( gm_post( 'name' ) );
	$area   = sanitize_text_field( gm_post( 'area' ) );
	$review = sanitize_textarea_field( mb_substr( (string) gm_post( 'review' ), 0, 1000 ) );
	$email  = sanitize_email( gm_post( 'email' ) );
	$rating = max( 1, min( 5, (int) gm_post( 'rating' ) ) );

	if ( '' !== gm_post( 'website' ) ) { // Honeypot.
		gm_account_fail( 'Something went wrong — please try again.' );
	}
	if ( '' === $name ) {
		gm_account_fail( 'Please enter your name.', 422 );
	}
	if ( mb_strlen( trim( $review ) ) < 5 ) {
		gm_account_fail( 'Please write a few words about your order.', 422 );
	}
	// Simple flood guard: one review per visitor every two minutes.
	$ip_key = 'gm_review_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	if ( get_transient( $ip_key ) ) {
		gm_account_fail( 'Thank you — we have already received your review.', 429 );
	}
	set_transient( $ip_key, 1, 2 * MINUTE_IN_SECONDS );

	$id = wp_insert_post(
		array(
			'post_type'    => 'gm_testimonial',
			'post_status'  => 'pending',
			'post_title'   => $area ? $name . ', ' . $area : $name,
			'post_content' => $review,
			'menu_order'   => 100,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		gm_account_fail( 'Your review could not be sent — please try again.', 500 );
	}
	update_post_meta( $id, '_gm_rating', $rating );
	if ( $email ) {
		update_post_meta( $id, '_gm_email', $email );
	}

	wp_mail(
		gm_setting( 'order_email' ),
		'New review waiting for approval — ' . $name,
		"A customer has left a review on the website. It will not appear until you approve it.\n\n"
			. 'From: ' . ( $area ? "$name, $area" : $name ) . ( $email ? " ($email)" : '' ) . "\nRating: $rating/5\n\n$review\n\n"
			. 'Approve or delete it here: ' . admin_url( 'post.php?post=' . $id . '&action=edit' ) . "\n"
	);

	wp_send_json_success( array( 'message' => 'Thank you! Your review has been sent and will appear once we have checked it.' ) );
}

/** Star rating box + approval hint on the edit screen. */
add_action( 'add_meta_boxes_gm_testimonial', function ( $post ) {
	add_meta_box( 'gm_review_rating', 'Rating', function ( $post ) {
		wp_nonce_field( 'gm_review_save', 'gm_review_nonce' );
		$rating = (int) get_post_meta( $post->ID, '_gm_rating', true );
		echo '<select name="gm_rating"><option value="0">No stars</option>';
		for ( $i = 5; $i >= 1; $i-- ) {
			printf( '<option value="%1$d" %2$s>%3$s (%1$d)</option>', (int) $i, selected( $rating, $i, false ), esc_html( str_repeat( '★', $i ) ) );
		}
		echo '</select>';
		$email = get_post_meta( $post->ID, '_gm_email', true );
		if ( $email ) {
			echo '<p>Sent by <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a> (not shown on the site)</p>';
		}
	}, 'gm_testimonial', 'side' );
} );

add_action( 'edit_form_top', function ( $post ) {
	if ( 'gm_testimonial' === $post->post_type && 'pending' === $post->post_status ) {
		echo '<div class="notice notice-info inline"><p><strong>Sent in by a customer.</strong> Check it, then click <strong>Publish</strong> to show it on the Reviews page — or move it to the Bin.</p></div>';
	}
} );

add_action( 'save_post_gm_testimonial', function ( $id ) {
	if ( isset( $_POST['gm_review_nonce'], $_POST['gm_rating'] ) && wp_verify_nonce( sanitize_key( $_POST['gm_review_nonce'] ), 'gm_review_save' ) && current_user_can( 'edit_post', $id ) ) {
		update_post_meta( $id, '_gm_rating', max( 0, min( 5, (int) $_POST['gm_rating'] ) ) );
	}
	do_action( 'litespeed_purge_all' );
} );

add_filter( 'manage_gm_testimonial_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['gm_rating'] = 'Rating';
			$out['gm_status'] = 'Status';
		}
	}
	return $out;
}, 20 );

add_action( 'manage_gm_testimonial_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_rating' === $col ) {
		$r = (int) get_post_meta( $id, '_gm_rating', true );
		echo $r ? esc_html( str_repeat( '★', $r ) ) : '—';
	} elseif ( 'gm_status' === $col ) {
		$status = get_post_status( $id );
		echo 'pending' === $status ? '<strong style="color:#b26200">Waiting for approval</strong>' : ( 'publish' === $status ? 'Live' : esc_html( ucfirst( $status ) ) );
	}
}, 10, 2 );

/** "Testimonials 2" bubble in the admin menu when reviews are waiting. */
add_action( 'admin_menu', function () {
	global $menu;
	$waiting = (int) wp_count_posts( 'gm_testimonial' )->pending;
	if ( ! $waiting ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( 'edit.php?post_type=gm_testimonial' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $waiting . '</span></span>';
		}
	}
}, 99 );
