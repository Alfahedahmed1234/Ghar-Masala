<?php
/**
 * Ghar Masala theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GM_VERSION', '1.13.0' );

/** Cache-busting version: changes whenever the file does. */
function gm_ver( $file ) {
	$mtime = @filemtime( get_template_directory() . '/' . $file );
	return $mtime ? GM_VERSION . '.' . $mtime : GM_VERSION;
}

/**
 * After a new version of the theme is uploaded, empty the page caches once
 * (desktop and mobile), so every visitor gets the new design straight away.
 */
add_action( 'init', function () {
	if ( get_option( 'gm_theme_version' ) === GM_VERSION ) {
		return;
	}
	update_option( 'gm_theme_version', GM_VERSION );
	flush_rewrite_rules( false );                              // section addresses (/menu/, /reviews/…)
	do_action( 'litespeed_purge_all' );                      // LiteSpeed Cache (incl. its mobile cache)
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}, 99 );

require get_template_directory() . '/inc/data.php';
require get_template_directory() . '/inc/menu-admin.php';
require get_template_directory() . '/inc/settings.php';
require get_template_directory() . '/inc/orders.php';
require get_template_directory() . '/inc/schedule.php';
require get_template_directory() . '/inc/discounts.php';
require get_template_directory() . '/inc/customers.php';
require get_template_directory() . '/inc/banners.php';
require get_template_directory() . '/inc/rewards.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/seo.php';
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

/** Link to one of the site's sections, e.g. gm_view_url( 'menu' ) → /menu/ (or /#account for sections without their own address). */
function gm_view_url( $view = '' ) {
	$url = $view && function_exists( 'gm_seo_url' ) ? gm_seo_url( $view ) : '';
	return $url ? $url : home_url( '/' ) . ( $view ? '#' . $view : '' );
}

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ghar-masala', get_stylesheet_uri(), array(), gm_ver( 'style.css' ) );

	if ( ! gm_is_app() ) {
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
		'loyalty'       => gm_loyalty_config(),
		'countdown'     => 'show' === gm_mod( 'countdown_show' ) ? gm_fill( wp_strip_all_tags( gm_mod( 'countdown_text' ) ) ) : '',
		'welcome'       => 'show' === gm_mod( 'welcome_show' ) ? array(
			'back'     => gm_fill( wp_strip_all_tags( gm_mod( 'welcome_back' ) ) ),
			'upcoming' => gm_fill( wp_strip_all_tags( gm_mod( 'welcome_upcoming' ) ) ),
			'first'    => gm_fill( wp_strip_all_tags( gm_mod( 'welcome_first' ) ) ),
		) : null,
		'reviewReward'  => gm_review_reward()['enabled'] ? (float) gm_review_reward()['percent'] : 0,
		'discounts'     => array(
			'auto'  => array_map( 'gm_discount_public', gm_discount_rules( 'auto' ) ),
			'stack' => gm_discount_stacking(),
		),
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
		'startView' => gm_current_view(),
		'viewPaths' => gm_view_paths(),
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

/** Which theme version is live (view the page source and search for "Ghar Masala theme"). */
add_action( 'wp_head', function () {
	echo '<meta name="generator" content="Ghar Masala theme ' . esc_attr( GM_VERSION ) . '">' . "\n";
}, 1 );

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
	if ( gm_is_app() ) {
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

/** Section addresses for app.js: { "/menu/": "menu", … } (paths only). */
function gm_view_paths() {
	$out = array( wp_make_link_relative( home_url( '/' ) ) => 'home' );
	foreach ( gm_seo_pages() as $slug => $p ) {
		$out[ wp_make_link_relative( home_url( '/' . $slug . '/' ) ) ] = $p[0];
	}
	return $out;
}

