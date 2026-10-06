<?php
/**
 * Search engines & local SEO.
 *
 * - Real addresses for each section (/menu/, /reviews/, /faqs/…) so Google can
 *   index them separately. They load the same one-page site, opened at that
 *   section, so nothing changes for customers.
 * - Page titles, meta descriptions, canonical links and social-share tags.
 * - Structured data (schema.org) describing Ghar Masala as a local Indian &
 *   Bangladeshi restaurant/takeaway: phone, area served, delivery hours, menu
 *   with prices, FAQs.
 * - Those addresses are added to WordPress's sitemap (/wp-sitemap.xml).
 *
 * Wording is editable in Appearance → Customize → Ghar Masala → Search engines (Google).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** slug => [ view, title, description ]. {areas} = the main areas served. */
function gm_seo_pages() {
	return array(
		'menu'         => array( 'menu', 'Menu — Indian & Bangladeshi Curry Delivery in {town}', 'Our menu of home-cooked Indian and Bangladeshi curries, rice, breads and sides, made fresh to order and delivered across {areas}. Halal. Pre-order online.' ),
		'my-story'     => array( 'story', 'Our Story — Home-Style Bangladeshi Cooking in {town}', 'Ghar Masala means “House of Spice”. Home-style Bangladeshi and Indian food cooked fresh on the day by a local cook in {town}.' ),
		'reviews'      => array( 'reviews', 'Reviews — What Customers Say About Ghar Masala, {town}', 'Reviews from customers in {areas} who have ordered home-cooked curry from Ghar Masala.' ),
		'faqs'         => array( 'faq', 'FAQs — Curry Delivery in {town}', 'Questions about ordering, delivery areas, halal food, spice levels and allergens at Ghar Masala, {town}.' ),
		'allergens'    => array( 'allergens', 'Allergen Information — Ghar Masala, {town}', 'Allergen table for every dish on the Ghar Masala menu: the 14 declarable allergens.' ),
		'how-it-works' => array( 'how', 'How Ordering Works — Pre-Order Curry Delivery in {town}', 'Fill your basket, book a delivery slot and we cook it fresh on the day. Free delivery nearby in {areas}.' ),
		'contact'      => array( 'contact', 'Contact Ghar Masala — Indian Food Delivery, {town}', 'Get in touch with Ghar Masala in {town}: phone, email, WhatsApp, or send us a message.' ),
		'news'         => array( 'news', 'News — Ghar Masala, {town}', 'News and updates from Ghar Masala, home-cooked Indian and Bangladeshi food in {town}.' ),
	);
}

/** The view a /slug/ address opens, or ''. */
function gm_current_view() {
	$slug  = get_query_var( 'gm_view' );
	$pages = gm_seo_pages();
	return ( $slug && isset( $pages[ $slug ] ) ) ? $pages[ $slug ][0] : '';
}

/** Is this the one-page site (home page or one of its section addresses)? */
function gm_is_app() {
	return is_front_page() || '' !== gm_current_view();
}

/** Real address of a section, or '' if it has none (e.g. account, checkout). */
function gm_seo_url( $view ) {
	foreach ( gm_seo_pages() as $slug => $p ) {
		if ( $p[0] === $view ) {
			return home_url( '/' . $slug . '/' );
		}
	}
	return '';
}

function gm_seo_fill( $text ) {
	return strtr(
		$text,
		array(
			'{town}'  => gm_mod( 'seo_town' ),
			'{areas}' => gm_seo_areas_text(),
		)
	);
}

function gm_seo_areas() {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) gm_mod( 'seo_areas' ) ) ) ) );
}

/** "Tividale, Oldbury, Tipton and Dudley Port" */
function gm_seo_areas_text() {
	$a = gm_seo_areas();
	if ( count( $a ) < 2 ) {
		return implode( '', $a );
	}
	$last = array_pop( $a );
	return implode( ', ', $a ) . ' and ' . $last;
}

/* ---------------------------------------------------------------------------
 * Addresses
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	add_rewrite_rule( '^(' . implode( '|', array_map( 'preg_quote', array_keys( gm_seo_pages() ) ) ) . ')/?$', 'index.php?gm_view=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'gm_view';
	return $vars;
} );

add_filter( 'template_include', function ( $template ) {
	if ( gm_current_view() ) {
		$front = locate_template( 'front-page.php' );
		return $front ? $front : $template;
	}
	return $template;
} );

/* Section addresses are not the blog home or a 404, and must not be redirected away. */
add_filter( 'redirect_canonical', function ( $redirect ) {
	return gm_current_view() ? false : $redirect;
} );

add_action( 'wp', function () {
	if ( gm_current_view() ) {
		global $wp_query;
		$wp_query->is_home = false;
		$wp_query->is_404  = false;
		status_header( 200 );
	}
} );

/* ---------------------------------------------------------------------------
 * Titles, descriptions, canonical, social sharing
 * ------------------------------------------------------------------------ */

function gm_seo_meta() {
	$view = gm_current_view();
	if ( $view ) {
		foreach ( gm_seo_pages() as $slug => $p ) {
			if ( $p[0] === $view ) {
				return array(
					'title' => gm_seo_fill( $p[1] ) . ' | Ghar Masala',
					'desc'  => gm_seo_fill( $p[2] ),
					'url'   => home_url( '/' . $slug . '/' ),
				);
			}
		}
	}
	return array(
		'title' => gm_seo_fill( wp_strip_all_tags( gm_mod( 'seo_title' ) ) ),
		'desc'  => gm_seo_fill( wp_strip_all_tags( gm_mod( 'seo_description' ) ) ),
		'url'   => home_url( '/' ),
	);
}

add_filter( 'pre_get_document_title', function ( $title ) {
	return gm_is_app() ? gm_seo_meta()['title'] : $title;
}, 20 );

add_action( 'wp', function () {
	if ( gm_is_app() ) {
		remove_action( 'wp_head', 'rel_canonical' ); // we print our own
	}
} );

/** Is an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) already printing these tags? */
function gm_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

add_action( 'wp_head', function () {
	if ( ! gm_is_app() ) {
		return;
	}
	$m     = gm_seo_meta();
	$image = gm_img( 'img_hero', 'hero.jpg' );
	if ( ! gm_seo_plugin_active() ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $m['desc'] ) );
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $m['url'] ) );
		printf( '<meta property="og:type" content="website">' . "\n" );
		printf( '<meta property="og:site_name" content="Ghar Masala">' . "\n" );
		printf( '<meta property="og:locale" content="en_GB">' . "\n" );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $m['title'] ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $m['desc'] ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $m['url'] ) );
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
	}
	$geo = gm_seo_geo();
	echo '<meta name="geo.region" content="GB-SAW">' . "\n";
	printf( '<meta name="geo.placename" content="%s">' . "\n", esc_attr( gm_mod( 'seo_town' ) ) );
	if ( $geo ) {
		printf( '<meta name="geo.position" content="%1$s;%2$s"><meta name="ICBM" content="%1$s, %2$s">' . "\n", esc_attr( $geo['lat'] ), esc_attr( $geo['lng'] ) );
	}
	foreach ( gm_seo_schema() as $data ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}, 2 );

/* ---------------------------------------------------------------------------
 * Structured data
 * ------------------------------------------------------------------------ */

/** Kitchen location (rounded) from the delivery postcode, stored once. */
function gm_seo_geo() {
	$from  = gm_setting( 'delivery_from' );
	$saved = get_option( 'gm_seo_geo' );
	if ( is_array( $saved ) && ( $saved['postcode'] ?? '' ) === $from ) {
		return $saved;
	}
	if ( ! function_exists( 'gm_postcode_lookup' ) ) {
		return null;
	}
	$p = gm_postcode_lookup( $from );
	if ( is_wp_error( $p ) ) {
		return null;
	}
	$geo = array(
		'postcode' => $from,
		'lat'      => round( $p['lat'], 3 ), // about 100m — the area, not the house
		'lng'      => round( $p['lng'], 3 ),
	);
	update_option( 'gm_seo_geo', $geo, false );
	return $geo;
}

function gm_social_profiles() {
	return array(
		'https://www.facebook.com/people/Ghar-Masala/61586406671855/',
		'https://www.instagram.com/ghar.masalaa',
	);
}

function gm_seo_schema() {
	$home  = home_url( '/' );
	$id    = $home . '#restaurant';
	$s     = gm_schedule();
	$names = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	$days  = array();
	foreach ( (array) $s['open_days'] as $d ) {
		$days[] = $names[ (int) $d ] ?? null;
	}
	$geo   = gm_seo_geo();
	$rules = gm_delivery_rules();
	$areas = array();
	foreach ( gm_seo_areas() as $a ) {
		$areas[] = array( '@type' => 'Place', 'name' => $a );
	}
	if ( $geo ) {
		$areas[] = array(
			'@type'       => 'GeoCircle',
			'geoMidpoint' => array( '@type' => 'GeoCoordinates', 'latitude' => $geo['lat'], 'longitude' => $geo['lng'] ),
			'geoRadius'   => (int) round( (float) $rules['max_miles'] * 1609.34 ),
		);
	}
	$pay = array( 'Cash' );
	if ( gm_stripe_enabled() ) {
		$pay = array_merge( gm_cod_enabled() ? $pay : array(), array( 'Credit Card', 'Debit Card', 'Apple Pay', 'Google Pay' ) );
	}

	$business = array(
		'@context'                  => 'https://schema.org',
		'@type'                     => array( 'Restaurant', 'FoodEstablishment' ),
		'@id'                       => $id,
		'name'                      => 'Ghar Masala',
		'alternateName'             => 'Ghar Masala — House of Spice',
		'description'               => gm_seo_fill( wp_strip_all_tags( gm_mod( 'seo_description' ) ) ),
		'url'                       => $home,
		'logo'                      => gm_logo_url(),
		'image'                     => array( gm_img( 'img_hero', 'hero.jpg' ), gm_img( 'img_story_side', 'curry.jpg' ), gm_img( 'img_story_wide', 'spices.jpg' ) ),
		'telephone'                 => '+' . gm_phone_intl(),
		'email'                     => gm_setting( 'email' ),
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => gm_mod( 'seo_town' ),
			'addressRegion'   => 'West Midlands',
			'postalCode'      => strtok( gm_setting( 'delivery_from' ), ' ' ),
			'addressCountry'  => 'GB',
		),
		'areaServed'                => $areas,
		'servesCuisine'             => array_values( array_filter( array_map( 'trim', explode( ',', (string) gm_mod( 'seo_cuisine' ) ) ) ) ),
		'priceRange'                => '£',
		'currenciesAccepted'        => 'GBP',
		'paymentAccepted'           => implode( ', ', $pay ),
		'acceptsReservations'       => false,
		'hasMenu'                   => gm_seo_url( 'menu' ),
		'openingHoursSpecification' => array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array_values( array_filter( $days ) ),
				'opens'     => $s['open'],
				'closes'    => $s['close'],
			),
		),
		'sameAs'                    => gm_social_profiles(),
		'potentialAction'           => array(
			'@type'  => 'OrderAction',
			'target' => array(
				'@type'          => 'EntryPoint',
				'urlTemplate'    => gm_seo_url( 'menu' ),
				'actionPlatform' => array( 'http://schema.org/DesktopWebPlatform', 'http://schema.org/MobileWebPlatform' ),
			),
			'deliveryMethod' => 'http://purl.org/goodrelations/v1#DeliveryModeOwnFleet',
		),
	);
	if ( $geo ) {
		$business['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => $geo['lat'], 'longitude' => $geo['lng'] );
	}
	$out = array( $business );

	$view = gm_current_view();
	if ( 'menu' === $view || '' === $view ) {
		$sections = array();
		foreach ( gm_menu() as $group ) {
			$items = array();
			foreach ( $group['items'] as $item ) {
				$mi = array(
					'@type'  => 'MenuItem',
					'name'   => wp_strip_all_tags( $item['name'] ),
					'offers' => array( '@type' => 'Offer', 'price' => number_format( (float) $item['price'], 2, '.', '' ), 'priceCurrency' => 'GBP' ),
				);
				if ( ! empty( $item['desc'] ) ) {
					$mi['description'] = wp_strip_all_tags( $item['desc'] );
				}
				$diets = array( 'https://schema.org/HalalDiet' );
				if ( ! empty( $item['veg'] ) ) {
					$diets[] = 'https://schema.org/VegetarianDiet';
				}
				$mi['suitableForDiet'] = $diets;
				$items[]               = $mi;
			}
			$sections[] = array( '@type' => 'MenuSection', 'name' => wp_strip_all_tags( $group['title'] ), 'hasMenuItem' => $items );
		}
		$out[] = array(
			'@context'       => 'https://schema.org',
			'@type'          => 'Menu',
			'@id'            => gm_seo_url( 'menu' ) . '#menu',
			'name'           => 'Ghar Masala menu',
			'url'            => gm_seo_url( 'menu' ),
			'inLanguage'     => 'en-GB',
			'hasMenuSection' => $sections,
		);
	}
	if ( 'faq' === $view ) {
		$qa = array();
		foreach ( gm_faq_list() as $q => $a ) {
			$qa[] = array( '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $a ) );
		}
		$out[] = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qa );
	}
	$out[] = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'WebSite',
		'name'      => 'Ghar Masala',
		'url'       => $home,
		'publisher' => array( '@id' => $id ),
	);
	return $out;
}

/* ---------------------------------------------------------------------------
 * Sitemap: /wp-sitemap.xml lists the section addresses
 * ------------------------------------------------------------------------ */

/* Don't list user accounts (it would reveal the admin username). */
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

add_action( 'wp_sitemaps_init', function ( $sitemaps ) {
	if ( ! class_exists( 'GM_Sitemap_Provider' ) ) {
		class GM_Sitemap_Provider extends WP_Sitemaps_Provider { // phpcs:ignore
			public function __construct() {
				$this->name        = 'gharmasala';
				$this->object_type = 'gharmasala';
			}
			public function get_url_list( $page_num, $object_subtype = '' ) {
				if ( $page_num > 1 ) {
					return array();
				}
				$list = array( array( 'loc' => home_url( '/' ) ) );
				foreach ( array_keys( gm_seo_pages() ) as $slug ) {
					$list[] = array( 'loc' => home_url( '/' . $slug . '/' ) );
				}
				return $list;
			}
			public function get_max_num_pages( $object_subtype = '' ) {
				return 1;
			}
		}
	}
	$sitemaps->registry->add_provider( 'gharmasala', new GM_Sitemap_Provider() );
} );
