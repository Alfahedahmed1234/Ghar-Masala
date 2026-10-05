<?php
/**
 * News timeline, managed in WP Admin → News.
 *
 * Each entry is a milestone: a title, a date, some text and an optional photo
 * (Featured image). The News page lists them newest first as a timeline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type(
		'gm_news',
		array(
			'labels'        => array(
				'name'               => 'News',
				'singular_name'      => 'News entry',
				'menu_name'          => 'News timeline',
				'all_items'          => 'All entries',
				'add_new'            => 'Add entry',
				'add_new_item'       => 'Add a news entry',
				'edit_item'          => 'Edit news entry',
				'search_items'       => 'Search news',
				'not_found'          => 'No news entries yet.',
				'not_found_in_trash' => 'No news entries in the bin.',
				'featured_image'     => 'Photo (optional)',
				'set_featured_image' => 'Add a photo',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-calendar-alt',
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
		)
	);
} );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'post-thumbnails', array( 'gm_news' ) );
} );

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gm_news' === $post->post_type ? 'What happened, e.g. “We delivered our 100th order”' : $text;
}, 10, 2 );

add_action( 'add_meta_boxes_gm_news', function () {
	remove_meta_box( 'slugdiv', 'gm_news', 'normal' );
	add_meta_box( 'gm_news_date', 'Date on the timeline', function ( $post ) {
		wp_nonce_field( 'gm_news_save', 'gm_news_nonce' );
		$date = get_post_meta( $post->ID, '_gm_news_date', true );
		if ( ! $date ) {
			$date = wp_date( 'Y-m-d' );
		}
		echo '<input type="date" name="gm_news_date" value="' . esc_attr( $date ) . '" style="width:100%">';
		echo '<p class="description">When it happened — can be in the past. Entries are listed newest first.</p>';
	}, 'gm_news', 'side', 'high' );
} );

add_action( 'save_post_gm_news', function ( $id ) {
	if ( isset( $_POST['gm_news_nonce'], $_POST['gm_news_date'] ) && wp_verify_nonce( sanitize_key( $_POST['gm_news_nonce'] ), 'gm_news_save' ) && current_user_can( 'edit_post', $id ) ) {
		$date = sanitize_text_field( wp_unslash( $_POST['gm_news_date'] ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			update_post_meta( $id, '_gm_news_date', $date );
		}
	}
	do_action( 'litespeed_purge_all' );
} );

add_filter( 'manage_gm_news_posts_columns', function () {
	return array(
		'cb'           => '<input type="checkbox">',
		'title'        => 'Entry',
		'gm_news_date' => 'Timeline date',
		'date'         => 'Published',
	);
} );
add_action( 'manage_gm_news_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_news_date' === $col ) {
		$date = get_post_meta( $id, '_gm_news_date', true );
		echo esc_html( $date ? date_i18n( 'j F Y', strtotime( $date ) ) : '—' );
	}
}, 10, 2 );

/** Published entries, newest first, ready for the page. */
function gm_news_items() {
	$posts = get_posts(
		array(
			'post_type'      => 'gm_news',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);
	$items = array();
	foreach ( $posts as $post ) {
		$date    = get_post_meta( $post->ID, '_gm_news_date', true );
		$date    = $date ? $date : get_the_date( 'Y-m-d', $post );
		$items[] = array(
			'sort'       => $date . sprintf( '%010d', $post->ID ),
			'date_label' => date_i18n( 'j F Y', strtotime( $date ) ),
			'title'      => wp_specialchars_decode( $post->post_title, ENT_QUOTES ),
			'text'       => $post->post_content,
			'image'      => get_the_post_thumbnail_url( $post, 'large' ),
		);
	}
	usort(
		$items,
		function ( $a, $b ) {
			return strcmp( $b['sort'], $a['sort'] );
		}
	);
	return $items;
}

/** First run: start the timeline with the launch of online ordering. */
add_action( 'init', function () {
	if ( get_option( 'gm_news_seeded' ) || ! add_option( 'gm_news_seeded', 'yes', '', true ) ) {
		return;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'gm_news',
			'post_status'  => 'publish',
			'post_title'   => 'Online pre-ordering is here',
			'post_content' => 'You can now build your order and book a delivery slot up to seven days ahead directly on this site — choose your dishes, pick a slot, and pay at checkout. Ringing the kitchen still works too.',
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_gm_news_date', '2026-08-20' );
	}
}, 20 );
