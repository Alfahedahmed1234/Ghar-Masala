<?php
/**
 * Ghar Masala theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GM_VERSION', '1.8.0' );

/** Cache-busting version: changes whenever the file does. */
function gm_ver( $file ) {
	$mtime = @filemtime( get_template_directory() . '/' . $file );
	return $mtime ? GM_VERSION . '.' . $mtime : GM_VERSION;
}

require get_template_directory() . '/inc/data.php';
require get_template_directory() . '/inc/menu-admin.php';
require get_template_directory() . '/inc/settings.php';
require get_template_directory() . '/inc/orders.php';
require get_template_directory() . '/inc/schedule.php';
require get_template_directory() . '/inc/delivery.php';
require get_template_directory() . '/inc/accounts.php';
require get_template_directory() . '/inc/testimonials.php';
require get_template_directory() . '/inc/news.php';
require get_template_directory() . '/inc/contact.php';
require get_template_directory() . '/inc/stripe.php';
require get_template_directory() . '/inc/rest.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
} );

function gm_asset( $path ) {
	return get_template_directory_uri() . '/assets/' . $path;
}

/** Logo: Appearance → Customize → Site Identity → Logo, or the bundled one. */
function gm_logo_url() {
	$id = get_theme_mod( 'custom_logo' );
	$src = $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
	return $src ? $src : gm_asset( 'images/logo.png' );
}

/** Link to one of the front page's views, e.g. gm_view_url( 'menu' ). */
function gm_view_url( $view = '' ) {
	return home_url( '/' ) . ( $view ? '#' . $view : '' );
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ghar-masala', get_stylesheet_uri(), array(), gm_ver( 'style.css' ) );

	if ( ! is_front_page() ) {
		return;
	}

	wp_enqueue_script( 'ghar-masala', gm_asset( 'js/app.js' ), array(), gm_ver( 'assets/js/app.js' ), array( 'in_footer' => true ) );

	$menu = array();
	foreach ( gm_menu() as $group ) {
		$items = array();
		foreach ( $group['items'] as $item ) {
			$items[] = array(
				'id'         => $item['id'],
				'name'       => $item['name'],
				'price'      => (int) round( $item['price'] * 100 ),
				'desc'       => $item['desc'] ?? '',
				'veg'        => ! empty( $item['veg'] ),
				'rec'        => ! empty( $item['rec'] ),
				'adjustable' => ! empty( $item['adjustable'] ),
				'spice'      => (string) ( $item['spice'] ?? '' ),
			);
		}
		$menu[] = array(
			'title' => $group['title'],
			'note'  => $group['note'],
			'items' => $items,
		);
	}

	$user   = wp_get_current_user();
	$config = array(
		// Paths only (no https://domain), so requests always go to the address the
		// visitor is on — a redirect (http→https, www) would turn a POST into a GET.
		'rest'      => wp_make_link_relative( esc_url_raw( rest_url( 'ghar-masala/v1/' ) ) ),
		'ajax'      => wp_make_link_relative( admin_url( 'admin-ajax.php' ) ),
		'loggedIn'  => is_user_logged_in(),
		'minOrder'  => (int) round( gm_rules()['min_order'] * 100 ),
		'deliveryRules' => gm_delivery_summary(),
		'cutoffText'    => gm_cutoff_text(),
		'cashLimit'     => gm_cash_limit(),
		'phone'     => array(
			'href'  => 'tel:+' . gm_phone_intl(),
			'label' => gm_setting( 'phone' ),
		),
		'menu'      => $menu,
		'spiceLevels' => gm_spice_levels(),
		'payments'  => array(
			'card' => gm_stripe_enabled(),
			'cod'  => gm_cod_enabled(),
		),
		'returned'  => gm_return_state(),
	);
	if ( is_user_logged_in() ) {
		// Only ever printed for signed-in visitors, whose pages are not cached.
		$orders           = gm_user_orders( $user->ID );
		$config['nonce']  = wp_create_nonce( 'wp_rest' );
		$config['user']   = array(
			'name'   => $user->display_name,
			'email'  => $user->user_email,
			'orders' => $orders,
			'saved'  => gm_saved_details( $user->ID ),
		);
	}

	wp_add_inline_script( 'ghar-masala', 'window.GM_CONFIG = ' . wp_json_encode( $config ) . ';', 'before' );
} );

/** Address details from a customer's latest order, to pre-fill checkout. */
function gm_saved_details( $user_id ) {
	$ids = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => '_gm_user', 'value' => (int) $user_id ) ),
		)
	);
	if ( ! $ids ) {
		return null;
	}
	$out = array();
	foreach ( array( 'name', 'phone', 'address', 'postcode', 'notes' ) as $key ) {
		$out[ $key ] = get_post_meta( $ids[0], '_gm_' . $key, true );
	}
	return $out;
}

/** Fonts are bundled with the theme, so start loading the main one early. */
add_action( 'wp_head', function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( gm_asset( 'fonts/archivo-latin.woff2' ) ) );
}, 1 );

/** After signing in or out, land on the account view rather than wp-admin. */
add_filter( 'login_redirect', function ( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User && ! $user->has_cap( 'edit_posts' ) && ( ! $requested || admin_url() === $requested ) ) {
		return gm_view_url( 'account' );
	}
	return $redirect_to;
}, 10, 3 );

/** Customers do not need the admin toolbar. */
add_filter( 'show_admin_bar', function ( $show ) {
	return current_user_can( 'edit_posts' ) ? $show : false;
} );

/** The allergen disclaimer used under the menu and in the footer (HTML). */
function gm_allergen_note() {
	return sprintf(
		'Due to the nature of our operations, we cannot guarantee that our dishes are completely free from traces of allergens. Please visit our <a href="%1$s">Allergens page</a> for further information, or contact us on <a href="%2$s">%3$s</a> if you have any questions or specific allergen requirements.',
		esc_url( gm_view_url( 'allergens' ) ),
		esc_attr( 'tel:+' . gm_phone_intl() ),
		esc_html( gm_setting( 'phone' ) )
	);
}

/**
 * Other pages (privacy policy, blog posts…) share the header but not app.js,
 * so give them just enough script for the phone menu and dropdown toggles.
 * The basket link there simply goes to the order section on the home page.
 */
add_action( 'wp_footer', function () {
	if ( is_front_page() ) {
		return;
	}
	?>
	<script>
	document.addEventListener('click', function (e) {
		var t = e.target.closest('[data-gm-navtoggle],[data-gm-subtoggle]');
		if (!t) return;
		var box = t.closest(t.hasAttribute('data-gm-navtoggle') ? '[data-gm-nav]' : '.gm-nav__item--sub');
		var open = !box.classList.contains('is-open');
		box.classList.toggle('is-open', open);
		t.setAttribute('aria-expanded', open ? 'true' : 'false');
	});
	</script>
	<?php
} );
