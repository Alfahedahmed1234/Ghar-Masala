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
		'spice'           => (string) get_post_meta( $dish->ID, '_gm_spice', true ),
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
		echo '<style>#postdivrich #content{height:120px}.gm-spice-pick{display:flex;align-items:center;gap:8px;margin:0 0 6px}.gm-allergen-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px 18px}.gm-allergen-grid label{display:flex;justify-content:space-between;align-items:center;gap:8px}</style>';
	}
} );

/** Spice squares in WP Admin, same colours as the site's key. */
add_action( 'admin_head', function () {
	$screen = get_current_screen();
	if ( $screen && 'gm_dish' === $screen->post_type ) {
		echo '<style>.gm-dots{display:inline-flex;gap:3px;vertical-align:middle}.gm-dots i{width:8px;height:8px;background:#e2ded5}.gm-dots i.on{background:#eda01e}.gm-dots--600 i.on{background:#cf8511}.gm-dots--700 i.on{background:#a1660b}</style>';
	}
} );

add_action( 'add_meta_boxes_gm_dish', function () {
	remove_meta_box( 'slugdiv', 'gm_dish', 'normal' );
	add_meta_box( 'gm_dish_details', 'Price, section, spice & badges', 'gm_render_dish_box', 'gm_dish', 'normal', 'high' );
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
		<tr><th scope="row">Standard spice level</th>
			<td>
				<?php $gm_spice_now = (string) get_post_meta( $post->ID, '_gm_spice', true ); ?>
				<label class="gm-spice-pick"><input type="radio" name="gm_spice" value="" <?php checked( $gm_spice_now, '' ); ?>> <span class="gm-dots"><i></i><i></i><i></i><i></i></span> Not spicy / not shown</label>
				<?php foreach ( gm_spice_levels() as $gm_level => $gm_spice ) : ?>
					<label class="gm-spice-pick"><input type="radio" name="gm_spice" value="<?php echo esc_attr( $gm_level ); ?>" <?php checked( $gm_spice_now, $gm_level ); ?>> <?php echo gm_spice_dots( $gm_level ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup. ?> <?php echo esc_html( $gm_spice['label'] ); ?></label>
				<?php endforeach; ?>
				<p class="description">How hot the dish is as standard, using the menu’s spice key. Shown as coloured squares next to the dish.</p>
			</td></tr>
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
	$spice = isset( $_POST['gm_spice'] ) ? sanitize_key( $_POST['gm_spice'] ) : '';
	update_post_meta( $id, '_gm_spice', isset( gm_spice_levels()[ $spice ] ) ? $spice : '' );
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
		'gm_spice'            => 'Spice',
		'gm_badges'           => 'Badges',
		'gm_order'            => 'Order',
	);
} );

add_action( 'manage_gm_dish_posts_custom_column', function ( $col, $id ) {
	if ( 'gm_price' === $col ) {
		echo esc_html( gm_money( (int) round( (float) get_post_meta( $id, '_gm_price', true ) * 100 ) ) );
	} elseif ( 'gm_spice' === $col ) {
		$level = get_post_meta( $id, '_gm_spice', true );
		if ( isset( gm_spice_levels()[ $level ] ) ) {
			echo gm_spice_dots( $level ) . ' ' . esc_html( gm_spice_levels()[ $level ]['short'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup.
		}
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

/* ---------------------------------------------------------------------------
 * Menu → Allergen table: every dish's allergens on one screen
 * ------------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=gm_dish', 'Allergen table', 'Allergen table', 'edit_posts', 'gm-allergens', 'gm_render_allergen_admin' );
} );

add_action( 'admin_post_gm_save_allergens', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'gm_save_allergens' );
	$cols = array_keys( gm_allergen_cols() );
	foreach ( (array) wp_unslash( $_POST['dish'] ?? array() ) as $id => $row ) {
		$id = absint( $id );
		if ( ! $id || 'gm_dish' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			continue;
		}
		$marks = array();
		foreach ( $cols as $key ) {
			if ( isset( $row[ $key ] ) && in_array( $row[ $key ], array( 'Y', 'P' ), true ) ) {
				$marks[ $key ] = $row[ $key ];
			}
		}
		update_post_meta( $id, '_gm_allergens', $marks );
		update_post_meta( $id, '_gm_no_allergen_row', empty( $row['show'] ) ? 1 : 0 );
	}
	gm_menu_changed();
	wp_safe_redirect( admin_url( 'edit.php?post_type=gm_dish&page=gm-allergens&saved=1' ) );
	exit;
} );

function gm_render_allergen_admin() {
	$cols   = gm_allergen_cols();
	$dishes = get_posts(
		array(
			'post_type'      => 'gm_dish',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		)
	);
	// Group by section, in menu order.
	$groups = array();
	foreach ( $dishes as $dish ) {
		$terms = get_the_terms( $dish, 'gm_section' );
		$term  = $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
		$key   = $term ? sprintf( '%05d', (int) get_term_meta( $term->term_id, 'gm_order', true ) ) . $term->name : '99999';
		$groups[ $key ]['title']    = $term ? gm_plain( $term->name ) : 'No section';
		$groups[ $key ]['dishes'][] = $dish;
	}
	ksort( $groups );
	?>
	<div class="wrap">
		<h1>Allergen table</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Saved — the allergen table on the website is updated.</p></div>
		<?php endif; ?>
		<p>This is the table customers see on the Allergens page. Every dish on the menu is listed — new dishes appear here automatically. <strong>Contains</strong> = Y on the site, <strong>May contain</strong> = P. Untick <strong>Show</strong> to leave a dish out (e.g. canned drinks).</p>
		<style>
			.gm-at{border-collapse:collapse;background:#fff}
			.gm-at th,.gm-at td{border:1px solid #dcdcde;padding:4px 6px;font-size:12.5px;text-align:center}
			.gm-at thead th{position:sticky;top:32px;background:#006a4e;color:#fff;z-index:1;writing-mode:vertical-rl;transform:rotate(180deg);height:96px;white-space:nowrap}
			.gm-at thead th.gm-at-dish,.gm-at thead th.gm-at-show{writing-mode:horizontal-tb;transform:none;height:auto;text-align:left}
			.gm-at td.gm-at-dish{text-align:left;white-space:nowrap;font-weight:600}
			.gm-at tr.gm-at-group td{background:#f0f0f1;text-align:left;font-weight:600;text-transform:uppercase;font-size:11px;letter-spacing:.06em}
			.gm-at select{min-width:0;padding:0 18px 0 4px;font-size:12px;min-height:26px}
			.gm-at select.is-y{background:#006a4e;color:#fff}
			.gm-at select.is-p{background:#fdf4e2;color:#74490a}
			.gm-at tr.gm-at-hidden td{opacity:.5}
			.gm-at-wrap{overflow-x:auto}
		</style>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gm_save_allergens">
			<?php wp_nonce_field( 'gm_save_allergens' ); ?>
			<div class="gm-at-wrap">
			<table class="gm-at">
				<thead><tr><th class="gm-at-dish">Dish</th><th class="gm-at-show">Show</th><?php foreach ( $cols as $label ) : ?><th scope="col"><?php echo esc_html( $label ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
				<?php foreach ( $groups as $group ) : ?>
					<tr class="gm-at-group"><td colspan="<?php echo count( $cols ) + 2; ?>"><?php echo esc_html( $group['title'] ); ?></td></tr>
					<?php
					foreach ( $group['dishes'] as $dish ) :
						$marks = get_post_meta( $dish->ID, '_gm_allergens', true );
						$marks = is_array( $marks ) ? $marks : array();
						$show  = ! get_post_meta( $dish->ID, '_gm_no_allergen_row', true );
						?>
						<tr class="<?php echo $show ? '' : 'gm-at-hidden'; ?>">
							<td class="gm-at-dish"><a href="<?php echo esc_url( get_edit_post_link( $dish ) ); ?>"><?php echo esc_html( gm_plain( $dish->post_title ) ); ?></a><?php echo 'draft' === $dish->post_status ? ' <em>(hidden from menu)</em>' : ''; ?></td>
							<td><input type="checkbox" name="dish[<?php echo (int) $dish->ID; ?>][show]" value="1" <?php checked( $show ); ?> aria-label="Show <?php echo esc_attr( $dish->post_title ); ?> in the allergen table"></td>
							<?php foreach ( $cols as $key => $label ) : ?>
								<?php $v = $marks[ $key ] ?? ''; ?>
								<td><select name="dish[<?php echo (int) $dish->ID; ?>][<?php echo esc_attr( $key ); ?>]" class="<?php echo $v ? 'is-' . esc_attr( strtolower( $v ) ) : ''; ?>" aria-label="<?php echo esc_attr( $dish->post_title . ' — ' . $label ); ?>" onchange="this.className=this.value?'is-'+this.value.toLowerCase():''">
									<option value="">—</option>
									<option value="Y" <?php selected( $v, 'Y' ); ?>>Contains</option>
									<option value="P" <?php selected( $v, 'P' ); ?>>May contain</option>
								</select></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php submit_button( 'Save allergen table' ); ?>
		</form>
	</div>
	<?php
}
