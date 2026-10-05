<?php
/**
 * The menu, managed in WP Admin → Menu.
 *
 *   Menu → All dishes   one entry per dish: name, description, price, section,
 *                       badges, allergens. Publish = on the menu, Draft = hidden.
 *   Menu → Sections     Starters, Curries… with the note shown beside each
 *                       heading and their order on the page.
 *
 * The first time the theme runs, the original menu (gm_menu_seed() in
 * inc/data.php) is copied in so there is something to edit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type(
		'gm_dish',
		array(
			'labels'        => array(
				'name'               => 'Menu',
				'singular_name'      => 'Dish',
				'menu_name'          => 'Menu',
				'all_items'          => 'All dishes',
				'add_new'            => 'Add dish',
				'add_new_item'       => 'Add a dish',
				'edit_item'          => 'Edit dish',
				'new_item'           => 'New dish',
				'search_items'       => 'Search dishes',
				'not_found'          => 'No dishes found.',
				'not_found_in_trash' => 'No dishes in the bin.',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 3,
			'menu_icon'     => 'dashicons-carrot',
			'supports'      => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_taxonomy(
		'gm_section',
		'gm_dish',
		array(
			'labels'            => array(
				'name'          => 'Sections',
				'singular_name' => 'Section',
				'menu_name'     => 'Sections',
				'all_items'     => 'All sections',
				'edit_item'     => 'Edit section',
				'add_new_item'  => 'Add a section',
				'new_item_name' => 'New section name',
				'search_items'  => 'Search sections',
				'not_found'     => 'No sections yet.',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'hierarchical'      => false,
			'meta_box_cb'       => false, // Picked from a dropdown in the dish's own box.
			'rewrite'           => false,
		)
	);
} );

/* ---------------------------------------------------------------------------
 * Reading the menu
 * ------------------------------------------------------------------------ */

/**
 * The live menu: sections in order, each with its published dishes.
 *
 * @return array[] [ [ 'title', 'note', 'items' => [ [ 'id', 'name', 'price', 'desc', 'veg', 'rec', 'adjustable', 'allergens', 'no_allergen_row' ] ] ] ]
 */
function gm_menu() {
	static $menu = null;
	if ( null !== $menu ) {
		return $menu;
	}
	if ( ! get_option( 'gm_menu_seeded' ) ) {
		return gm_menu_seed(); // Not copied into WP Admin yet.
	}

	$sections = get_terms(
		array(
			'taxonomy'   => 'gm_section',
			'hide_empty' => false,
		)
	);
	$sections = is_wp_error( $sections ) ? array() : $sections;
	usort(
		$sections,
		function ( $a, $b ) {
			return array( (int) get_term_meta( $a->term_id, 'gm_order', true ), $a->name ) <=> array( (int) get_term_meta( $b->term_id, 'gm_order', true ), $b->name );
		}
	);

	$groups = array();
	foreach ( $sections as $term ) {
		$groups[ $term->term_id ] = array(
			'title' => gm_plain( $term->name ),
			'note'  => gm_plain( $term->description ),
			'items' => array(),
		);
	}
	$loose = array(
		'title' => 'More',
		'note'  => '',
		'items' => array(),
	);

	$dishes = get_posts(
		array(
			'post_type'      => 'gm_dish',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	);
	foreach ( $dishes as $dish ) {
		$item  = gm_dish_item( $dish );
		$terms = get_the_terms( $dish, 'gm_section' );
		if ( $terms && ! is_wp_error( $terms ) && isset( $groups[ $terms[0]->term_id ] ) ) {
			$groups[ $terms[0]->term_id ]['items'][] = $item;
		} else {
			$loose['items'][] = $item;
		}
	}
	if ( $loose['items'] ) {
		$groups[] = $loose;
	}

	$menu = array_values(
		array_filter(
			$groups,
			function ( $g ) {
				return (bool) $g['items'];
			}
		)
	);
	return $menu;
}

/** WordPress stores "&" as "&amp;" in titles and terms; the theme escapes on output itself. */
function gm_plain( $text ) {
	return trim( wp_specialchars_decode( (string) $text, ENT_QUOTES ) );
}

function gm_dish_item( WP_Post $dish ) {
	$key       = get_post_meta( $dish->ID, '_gm_key', true );
	$allergens = get_post_meta( $dish->ID, '_gm_allergens', true );
	return array(
		'id'              => $key ? $key : 'dish-' . $dish->ID,
		'name'            => gm_plain( $dish->post_title ),
		'price'           => (float) get_post_meta( $dish->ID, '_gm_price', true ),
		'desc'            => gm_plain( wp_strip_all_tags( $dish->post_content ) ),
		'veg'             => (bool) get_post_meta( $dish->ID, '_gm_veg', true ),
		'rec'             => (bool) get_post_meta( $dish->ID, '_gm_rec', true ),
		'adjustable'      => (bool) get_post_meta( $dish->ID, '_gm_adjustable', true ),
		'allergens'       => is_array( $allergens ) ? $allergens : array(),
		'no_allergen_row' => (bool) get_post_meta( $dish->ID, '_gm_no_allergen_row', true ),
	);
}

/* ---------------------------------------------------------------------------
 * One-time copy of the original menu into WP Admin
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	if ( get_option( 'gm_menu_seeded' ) ) {
		return;
	}
	// add_option() fails if another request got there first, so this runs once.
	if ( ! add_option( 'gm_menu_seeded', 'running', '', true ) ) {
		return;
	}
	$order = 0;
	foreach ( gm_menu_seed() as $group ) {
		$order += 10;
		$term   = term_exists( $group['title'], 'gm_section' );
		if ( ! $term ) {
			$term = wp_insert_term( $group['title'], 'gm_section', array( 'description' => $group['note'] ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$term_id = (int) $term['term_id'];
		update_term_meta( $term_id, 'gm_order', $order );

		$dish_order = 0;
		foreach ( $group['items'] as $item ) {
			$dish_order += 10;
			$id          = wp_insert_post(
				array(
					'post_type'    => 'gm_dish',
					'post_status'  => 'publish',
					'post_title'   => $item['name'],
					'post_content' => $item['desc'] ?? '',
					'menu_order'   => $dish_order,
				)
			);
			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}
			wp_set_object_terms( $id, $term_id, 'gm_section' );
			update_post_meta( $id, '_gm_key', $item['id'] ); // Keeps past orders and "Order again" working.
			update_post_meta( $id, '_gm_price', number_format( $item['price'], 2, '.', '' ) );
			update_post_meta( $id, '_gm_veg', empty( $item['veg'] ) ? 0 : 1 );
			update_post_meta( $id, '_gm_rec', empty( $item['rec'] ) ? 0 : 1 );
			update_post_meta( $id, '_gm_adjustable', empty( $item['adjustable'] ) ? 0 : 1 );
			update_post_meta( $id, '_gm_allergens', $item['allergens'] ?? array() );
			update_post_meta( $id, '_gm_no_allergen_row', empty( $item['no_allergen_row'] ) ? 0 : 1 );
		}
	}
	update_option( 'gm_menu_seeded', 'yes' );
}, 20 );

/* ---------------------------------------------------------------------------
 * Dish edit screen
 * ------------------------------------------------------------------------ */

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gm_dish' === $post->post_type ? 'Dish name, e.g. Lamb Curry' : $text;
}, 10, 2 );

add_action( 'edit_form_after_title', function ( $post ) {
	if ( 'gm_dish' === $post->post_type ) {
		echo '<p class="description" style="margin:14px 0 6px">Description shown under the dish name (optional):</p>';
	}
} );

// A plain text box for the description.
add_filter( 'user_can_richedit', function ( $can ) {
	return 'gm_dish' === get_post_type() ? false : $can;
} );
add_action( 'admin_head', function () {
	if ( 'gm_dish' === get_post_type() ) {
		remove_action( 'media_buttons', 'media_buttons' );
		echo '<style>#postdivrich #content{height:120px}.gm-allergen-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px 18px}.gm-allergen-grid label{display:flex;justify-content:space-between;align-items:center;gap:8px}</style>';
	}
} );

add_action( 'add_meta_boxes_gm_dish', function () {
	remove_meta_box( 'slugdiv', 'gm_dish', 'normal' );
	add_meta_box( 'gm_dish_details', 'Price, section & badges', 'gm_render_dish_box', 'gm_dish', 'normal', 'high' );
	add_meta_box( 'gm_dish_allergens', 'Allergens', 'gm_render_allergen_box', 'gm_dish', 'normal', 'default' );
} );

function gm_render_dish_box( $post ) {
	wp_nonce_field( 'gm_dish_save', 'gm_dish_nonce' );
	$price    = get_post_meta( $post->ID, '_gm_price', true );
	$current  = wp_get_object_terms( $post->ID, 'gm_section', array( 'fields' => 'ids' ) );
	$current  = $current && ! is_wp_error( $current ) ? (int) $current[0] : 0;
	$sections = get_terms(
		array(
			'taxonomy'   => 'gm_section',
			'hide_empty' => false,
		)
	);
	?>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><label for="gm-price">Price</label></th>
			<td>£ <input type="number" id="gm-price" name="gm_price" min="0" step="0.01" required value="<?php echo esc_attr( '' === $price ? '' : number_format( (float) $price, 2, '.', '' ) ); ?>" style="width:7em"></td></tr>
		<tr><th scope="row"><label for="gm-section">Section</label></th>
			<td><select id="gm-section" name="gm_section">
				<option value="0">— Choose a section —</option>
				<?php foreach ( is_wp_error( $sections ) ? array() : $sections as $term ) : ?>
					<option value="<?php echo (int) $term->term_id; ?>" <?php selected( $current, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=gm_section&post_type=gm_dish' ) ); ?>" style="margin-left:8px">Manage sections</a></td></tr>
		<tr><th scope="row">Badges</th>
			<td>
				<label><input type="checkbox" name="gm_veg" value="1" <?php checked( get_post_meta( $post->ID, '_gm_veg', true ) ); ?>> Vegetarian (V)</label><br>
				<label><input type="checkbox" name="gm_rec" value="1" <?php checked( get_post_meta( $post->ID, '_gm_rec', true ) ); ?>> Recommended</label><br>
				<label><input type="checkbox" name="gm_adjustable" value="1" <?php checked( get_post_meta( $post->ID, '_gm_adjustable', true ) ); ?>> “Spice to order”</label>
			</td></tr>
	</table>
	<p class="description">Position within its section: set <strong>Order</strong> in the “Page Attributes” box (lowest first). To take a dish off the menu without deleting it, switch it to <strong>Draft</strong>.</p>
	<?php
}

function gm_render_allergen_box( $post ) {
	$marks = get_post_meta( $post->ID, '_gm_allergens', true );
	$marks = is_array( $marks ) ? $marks : array();
	?>
	<p class="description" style="margin-bottom:12px">These fill in the allergen table on the site. Leave an allergen on “—” if the dish doesn’t use it.</p>
	<div class="gm-allergen-grid">
		<?php foreach ( gm_allergen_cols() as $key => $label ) : ?>
			<label><span><?php echo esc_html( $label ); ?></span>
				<select name="gm_allergens[<?php echo esc_attr( $key ); ?>]">
					<option value="">—</option>
					<option value="Y" <?php selected( $marks[ $key ] ?? '', 'Y' ); ?>>Contains</option>
					<option value="P" <?php selected( $marks[ $key ] ?? '', 'P' ); ?>>May contain</option>
				</select>
			</label>
		<?php endforeach; ?>
	</div>
	<p style="margin-top:14px"><label><input type="checkbox" name="gm_no_allergen_row" value="1" <?php checked( get_post_meta( $post->ID, '_gm_no_allergen_row', true ) ); ?>> Leave this dish out of the allergen table (e.g. canned drinks)</label></p>
	<?php
}

add_action( 'save_post_gm_dish', function ( $id ) {
	if ( ! isset( $_POST['gm_dish_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gm_dish_nonce'] ), 'gm_dish_save' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$price = isset( $_POST['gm_price'] ) ? max( 0, round( (float) wp_unslash( $_POST['gm_price'] ), 2 ) ) : 0;
	update_post_meta( $id, '_gm_price', number_format( $price, 2, '.', '' ) );
	foreach ( array( 'veg', 'rec', 'adjustable', 'no_allergen_row' ) as $flag ) {
		update_post_meta( $id, '_gm_' . $flag, empty( $_POST[ 'gm_' . $flag ] ) ? 0 : 1 );
	}

	$section = isset( $_POST['gm_section'] ) ? absint( $_POST['gm_section'] ) : 0;
	wp_set_object_terms( $id, $section ? array( $section ) : array(), 'gm_section' );

	$marks = array();
	$input = isset( $_POST['gm_allergens'] ) ? (array) wp_unslash( $_POST['gm_allergens'] ) : array();
	foreach ( array_keys( gm_allergen_cols() ) as $key ) {
		if ( isset( $input[ $key ] ) && in_array( $input[ $key ], array( 'Y', 'P' ), true ) ) {
			$marks[ $key ] = $input[ $key ];
		}
	}
	update_post_meta( $id, '_gm_allergens', $marks );
} );

/* ---------------------------------------------------------------------------
 * Dish list
 * ------------------------------------------------------------------------ */

add_filter( 'manage_gm_dish_posts_columns', function () {
	return array(
		'cb'                  => '<input type="checkbox">',
		'title'               => 'Dish',
		'taxonomy-gm_section' => 'Section',
		'gm_price'            => 'Price',
		'gm_badges'           => 'Badges',
		'gm_order'            => 'Order',
	);
} );

add_action( 'manage_gm_dish_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_price' === $col ) {
		echo esc_html( gm_money( (int) round( (float) get_post_meta( $id, '_gm_price', true ) * 100 ) ) );
	} elseif ( 'gm_badges' === $col ) {
		$badges = array();
		foreach ( array( 'veg' => 'V', 'rec' => 'Recommended', 'adjustable' => 'Spice to order' ) as $flag => $label ) {
			if ( get_post_meta( $id, '_gm_' . $flag, true ) ) {
				$badges[] = $label;
			}
		}
		echo esc_html( implode( ', ', $badges ) );
	} elseif ( 'gm_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

// Dishes listed as they appear: by section order then dish order.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'gm_dish' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );

/* ---------------------------------------------------------------------------
 * Section screen: note + order
 * ------------------------------------------------------------------------ */

add_action( 'gm_section_add_form_fields', function () {
	?>
	<div class="form-field"><label for="gm-section-order">Order</label>
		<input type="number" name="gm_order" id="gm-section-order" value="100">
		<p>Position on the menu page, lowest first.</p></div>
	<?php
} );

add_action( 'gm_section_edit_form_fields', function ( $term ) {
	?>
	<tr class="form-field"><th scope="row"><label for="gm-section-order">Order</label></th>
		<td><input type="number" name="gm_order" id="gm-section-order" value="<?php echo (int) get_term_meta( $term->term_id, 'gm_order', true ); ?>">
		<p class="description">Position on the menu page, lowest first.</p></td></tr>
	<?php
} );

foreach ( array( 'created_gm_section', 'edited_gm_section' ) as $gm_hook ) {
	add_action( $gm_hook, function ( $term_id ) {
		if ( isset( $_POST['gm_order'] ) && current_user_can( 'manage_categories' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- core verifies the term form nonce.
			update_term_meta( $term_id, 'gm_order', (int) $_POST['gm_order'] );
		}
	} );
}

// Explain the Description box: it is the note beside the section heading.
add_filter( 'gm_section_row_actions', function ( $actions ) {
	unset( $actions['view'] );
	return $actions;
} );
add_action( 'admin_head-edit-tags.php', function () {
	if ( isset( $_GET['taxonomy'] ) && 'gm_section' === $_GET['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<style>.term-slug-wrap,.term-parent-wrap{display:none}</style>';
		add_filter( 'gettext', 'gm_section_labels', 10, 2 );
	}
} );
add_action( 'admin_head-term.php', function () {
	if ( isset( $_GET['taxonomy'] ) && 'gm_section' === $_GET['taxonomy'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<style>.term-slug-wrap,.term-parent-wrap{display:none}</style>';
		add_filter( 'gettext', 'gm_section_labels', 10, 2 );
	}
} );
function gm_section_labels( $translated, $text ) {
	if ( 'Description' === $text ) {
		return 'Note beside the heading';
	}
	if ( 'The description is not prominent by default; however, some themes may show it.' === $text ) {
		return 'Optional, e.g. “Served with salad & mint sauce”.';
	}
	return $translated;
}

/* ---------------------------------------------------------------------------
 * Page caching: refresh the site after menu changes
 * ------------------------------------------------------------------------ */

function gm_menu_changed() {
	do_action( 'litespeed_purge_all' );
}
add_action( 'save_post_gm_dish', 'gm_menu_changed', 99 );
add_action( 'trashed_post', function ( $id ) {
	if ( 'gm_dish' === get_post_type( $id ) ) {
		gm_menu_changed();
	}
} );
add_action( 'created_gm_section', 'gm_menu_changed', 99 );
add_action( 'edited_gm_section', 'gm_menu_changed', 99 );
add_action( 'delete_gm_section', 'gm_menu_changed', 99 );
