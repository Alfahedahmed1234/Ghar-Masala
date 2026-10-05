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
