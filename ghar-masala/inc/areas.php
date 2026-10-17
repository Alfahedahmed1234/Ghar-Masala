<?php
/**
 * Local area pages: /indian-takeaway-oldbury/, /indian-takeaway-tividale/…
 *
 * One page for each area in Customize → Ghar Masala → Search engines →
 * "Areas you deliver to", so Google has a page that is genuinely about
 * "Indian takeaway in Oldbury" to show for that search. Each page has its own
 * introduction (editable in Customize → Ghar Masala → Area pages), the live
 * delivery rules and postcode checker, popular dishes, reviews from customers
 * in that area, local delivery FAQs and links to the other areas.
 *
 * They open inside the one-page site (like /menu/), so the basket, menu and
 * checkout all work from them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GM_AREA_PREFIX = 'indian-takeaway-';

/** slug => area name, e.g. 'oldbury' => 'Oldbury'. */
function gm_areas() {
	$out = array();
	foreach ( gm_seo_areas() as $name ) {
		$slug = sanitize_title( $name );
		if ( $slug ) {
			$out[ $slug ] = wp_strip_all_tags( $name );
		}
	}
	return $out;
}

/** The area this address is for (slug), or ''. */
function gm_current_area() {
	$slug = (string) get_query_var( 'gm_area' );
	return '' !== $slug && isset( gm_areas()[ $slug ] ) ? $slug : '';
}

function gm_area_url( $slug ) {
	return home_url( '/' . GM_AREA_PREFIX . $slug . '/' );
}

/** Built-in introductions for the original areas. {area} is filled in. Edit them in the Customizer. */
function gm_area_default_intros() {
	return array(
		'tividale'     => 'Ghar Masala is Tividale’s own home kitchen. We cook here in Tividale, so your curry travels minutes rather than miles and arrives hot, the way it left the pan. Every order is cooked fresh on the day — authentic Bangladeshi and Indian curries, rice and breads, halal and made without a base sauce.',
		'oldbury'      => 'Looking for an Indian takeaway in Oldbury that tastes like proper home cooking? Ghar Masala is a family kitchen just up the road in Tividale, delivering home-style Bangladeshi and Indian curries across Oldbury. Nothing is cooked in advance: you book a slot, and your food is made fresh that evening.',
		'tipton'       => 'Tipton is right next door to our kitchen in Tividale, which makes it one of our quickest deliveries. Ghar Masala cooks Bangladeshi and Indian curries the way they are made at home — spices blended in the kitchen, meat marinated overnight and everything cooked on the day it is delivered.',
		'dudley-port'  => 'Dudley Port sits on Tividale’s doorstep, a few minutes from where we cook. If you want an Indian takeaway that tastes like someone’s kitchen rather than a production line, Ghar Masala cooks every order fresh on the day — halal, no base sauce, and as spicy as you like.',
		'great-bridge' => 'Ghar Masala delivers home-cooked Indian and Bangladeshi food to Great Bridge from our kitchen in nearby Tividale. It is a short, simple menu cooked properly: curries made from scratch on the day, fluffy rice, fresh breads and sundries, all halal.',
		'rowley-regis' => 'Tividale and Rowley Regis go back a long way, and our kitchen is only a short drive away. Ghar Masala brings authentic, home-style Bangladeshi and Indian curry to Rowley Regis — pre-ordered, cooked fresh on the day of delivery and never held over.',
		'burnt-tree'   => 'Burnt Tree is just around the island from our kitchen in Tividale. Ghar Masala cooks home-style Indian and Bangladeshi curries to order — no base sauce, nothing reheated — and delivers them to your door in a booked slot.',
	);
}

function gm_area_intro( $slug ) {
	$own = trim( (string) get_theme_mod( 'gm_area_intro_' . $slug, '' ) );
	if ( '' === $own ) {
		$own = gm_area_default_intros()[ $slug ] ?? 'Ghar Masala delivers home-cooked Indian and Bangladeshi food to {area} from our kitchen in {town}. Every order is cooked fresh on the day it is delivered — authentic curries, rice and breads, halal and made without a base sauce.';
	}
	return gm_seo_fill( strtr( $own, array( '{area}' => gm_areas()[ $slug ] ?? '' ) ) );
}

/** Dishes to feature: the "Recommended" ones, otherwise the first few curries. */
function gm_area_dishes( $limit = 6 ) {
	$rec = array();
	$all = array();
	foreach ( gm_menu() as $group ) {
		foreach ( $group['items'] as $item ) {
			$all[] = $item;
			if ( ! empty( $item['rec'] ) ) {
				$rec[] = $item;
			}
		}
	}
	return array_slice( $rec ? $rec : $all, 0, $limit );
}

/** Up to $n 4–5★ reviews, those from this area first. */
function gm_area_reviews( $area, $n = 3 ) {
	$local = array();
	$other = array();
	foreach ( gm_testimonials() as $t ) {
		$r = get_post_meta( $t->ID, '_gm_rating', true );
		if ( '' !== $r && (int) $r < 4 ) {
			continue;
		}
		if ( false !== stripos( get_the_title( $t ), $area ) ) {
			$local[] = $t;
		} else {
			$other[] = $t;
		}
	}
	return array_slice( array_merge( $local, $other ), 0, $n );
}

/** Questions & answers for an area page. */
function gm_area_faqs( $slug ) {
	$area  = gm_areas()[ $slug ] ?? '';
	$rules = gm_delivery_rules();
	$min   = gm_money( (int) round( gm_rules()['min_order'] * 100 ) );
	return array(
		sprintf( 'Do you deliver to %s?', $area )                  => sprintf( 'Yes — we deliver across most of %1$s from our kitchen in %2$s. %3$s Put your postcode in the checker above to see your exact delivery charge.', $area, gm_mod( 'seo_town' ), gm_delivery_summary() ),
		sprintf( 'How do I order an Indian takeaway in %s?', $area ) => sprintf( 'Choose your dishes on our menu, book a delivery slot and pay online. We deliver %1$s, %2$s, and orders for an evening close at %3$s — because every order is cooked fresh on the day.', gm_hours_text(), gm_days_text(), gm_cutoff_text() ),
		'Is your food halal?'                                      => 'Yes. All of our meat is halal, and everything is cooked in our own kitchen.',
		'Can I choose how spicy my curry is?'                      => 'Yes. Curries marked “spice to order” can be made mild, medium or hot — pick the spice level in your basket.',
		'Is there a minimum order?'                                => sprintf( 'Yes, %1$s. Delivery is free within %2$s miles.', $min, gm_number( $rules['free_miles'] ) ),
	);
}

/* ---------------------------------------------------------------------------
 * Addresses
 * ------------------------------------------------------------------------ */

add_action( 'init', function () {
	add_rewrite_rule( '^' . GM_AREA_PREFIX . '([a-z0-9-]+)/?$', 'index.php?gm_area=$matches[1]', 'top' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'gm_area';
	return $vars;
} );

/* An area that isn't in the list is a real 404. */
add_action( 'wp', function () {
	if ( '' !== (string) get_query_var( 'gm_area' ) && ! gm_current_area() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}, 5 );

/* ---------------------------------------------------------------------------
 * Customizer: an introduction per area
 * ------------------------------------------------------------------------ */

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$wp->add_section( 'gm_areas', array( 'title' => 'Area pages (Google)', 'panel' => 'gm_panel', 'description' => 'Each area you deliver to has its own page, e.g. ' . esc_html( gm_area_url( 'oldbury' ) ) . ' — this is what Google shows for searches like “Indian takeaway in Oldbury”. Write a few sentences in your own words for each area: something personal and local works best. Leave a box empty to use the built-in text. Add or remove areas in “Search engines (Google)”.' ) );
	foreach ( gm_areas() as $slug => $name ) {
		$wp->add_setting( 'gm_area_intro_' . $slug, array( 'default' => '', 'sanitize_callback' => 'sanitize_textarea_field' ) );
		$wp->add_control( 'gm_area_intro_' . $slug, array(
			'label'       => $name . ' page',
			'section'     => 'gm_areas',
			'type'        => 'textarea',
			'description' => '<a href="' . esc_url( gm_area_url( $slug ) ) . '" target="_blank" rel="noopener">' . esc_html( wp_make_link_relative( gm_area_url( $slug ) ) ) . '</a>',
			'input_attrs' => array( 'placeholder' => gm_area_intro( $slug ) ),
		) );
	}
}, 30 );

/* ---------------------------------------------------------------------------
 * The page
 * ------------------------------------------------------------------------ */

function gm_render_area_view() {
	$slug = gm_current_area();
	if ( ! $slug ) {
		return;
	}
	$area   = gm_areas()[ $slug ];
	$rules  = gm_delivery_rules();
	$others = array_diff_key( gm_areas(), array( $slug => 1 ) );
	?>
<section class="gm-view gm-area" data-view="area"<?php gm_view_hidden( 'area' ); ?>>
	<div class="gm-wrap gm-page">
		<nav class="gm-crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span aria-hidden="true">›</span><span aria-current="page">Indian takeaway in <?php echo esc_html( $area ); ?></span></nav>
		<span class="gm-eyebrow">Delivering to <?php echo esc_html( $area ); ?> · fresh to order</span>
		<h1 class="gm-title">Indian Takeaway in <?php echo esc_html( $area ); ?></h1>
		<p class="gm-area__sub">Home-cooked Indian &amp; Bangladeshi curry, delivered to your door in <?php echo esc_html( $area ); ?>.</p>
		<div class="gm-lede"><?php echo wp_kses_post( wpautop( esc_html( gm_area_intro( $slug ) ) ) ); ?></div>
		<div class="gm-area__ctas">
			<a class="gm-btn" href="#menu">See the menu &amp; order</a>
			<a class="gm-btn-outline gm-btn-outline--lg" href="#how">How it works</a>
		</div>

		<ul class="gm-area__facts">
			<li><strong>Free delivery</strong><span>within <?php echo esc_html( gm_number( $rules['free_miles'] ) ); ?> miles of <?php echo esc_html( gm_mod( 'seo_town' ) ); ?></span></li>
			<li><strong><?php echo esc_html( gm_money( (int) round( gm_rules()['min_order'] * 100 ) ) ); ?> minimum</strong><span>per order</span></li>
			<li><strong><?php echo esc_html( gm_hours_text() ); ?></strong><span><?php echo esc_html( gm_days_text() ); ?></span></li>
			<li><strong>Order by <?php echo esc_html( gm_time_label( gm_hhmm( (int) gm_schedule()['cutoff'] * 60 ), true ) ); ?></strong><span>the day before — cooked fresh on the day</span></li>
		</ul>

		<h2 class="gm-subtitle gm-area__h">Do we deliver to your street in <?php echo esc_html( $area ); ?>?</h2>
		<?php gm_render_delivery_checker(); ?>

		<?php $gm_dishes = gm_area_dishes(); ?>
		<?php if ( $gm_dishes ) : ?>
			<h2 class="gm-subtitle gm-area__h">Popular dishes</h2>
			<ul class="gm-area__dishes">
				<?php foreach ( $gm_dishes as $gm_d ) : ?>
					<li>
						<span class="gm-area__dish"><?php echo esc_html( $gm_d['name'] ); ?><?php if ( ! empty( $gm_d['veg'] ) ) : ?> <span class="gm-veg gm-veg--sm" title="Vegetarian">V</span><?php endif; ?></span>
						<span class="gm-tnum"><?php echo esc_html( gm_money( (int) round( $gm_d['price'] * 100 ) ) ); ?></span>
						<?php if ( ! empty( $gm_d['desc'] ) ) : ?><span class="gm-area__desc"><?php echo esc_html( $gm_d['desc'] ); ?></span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p><a class="gm-textlink" href="#menu">See the full menu →</a></p>
		<?php endif; ?>

		<h2 class="gm-subtitle gm-area__h">Why <?php echo esc_html( $area ); ?> orders from Ghar Masala</h2>
		<ul class="gm-area__why">
			<li><strong>Cooked on the day.</strong> Pre-orders mean we shop and cook for your slot — nothing is held over or reheated.</li>
			<li><strong>No base sauce.</strong> Every curry is built from scratch with spices blended in our kitchen.</li>
			<li><strong>Halal.</strong> All of our meat is halal.</li>
			<li><strong>Spice to order.</strong> Mild, medium or hot — you choose.</li>
			<li><strong>Local and family-run.</strong> The person who cooks your food is the person who brings it to your door.</li>
		</ul>

		<?php $gm_reviews = gm_area_reviews( $area ); ?>
		<?php if ( $gm_reviews ) : ?>
			<h2 class="gm-subtitle gm-area__h">What customers say</h2>
			<div class="gm-quotes gm-area__quotes">
				<?php foreach ( $gm_reviews as $gm_t ) : ?>
					<figure class="gm-quotes__item">
						<p class="gm-stars" aria-label="<?php echo esc_attr( (int) get_post_meta( $gm_t->ID, '_gm_rating', true ) ? (int) get_post_meta( $gm_t->ID, '_gm_rating', true ) : 5 ); ?> out of 5 stars"><?php echo esc_html( str_repeat( '★', (int) get_post_meta( $gm_t->ID, '_gm_rating', true ) ? (int) get_post_meta( $gm_t->ID, '_gm_rating', true ) : 5 ) ); ?></p>
						<blockquote class="gm-quotes__text"><?php echo esc_html( '“' . wp_trim_words( trim( wp_strip_all_tags( $gm_t->post_content ), " \t\n\r\"“”" ), 45 ) . '”' ); ?></blockquote>
						<figcaption class="gm-quotes__by">— <?php echo esc_html( get_the_title( $gm_t ) ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
			<p><a class="gm-textlink" href="<?php echo esc_url( gm_view_url( 'reviews' ) ); ?>">Read all reviews →</a></p>
		<?php endif; ?>

		<h2 class="gm-subtitle gm-area__h"><?php echo esc_html( $area ); ?> delivery questions</h2>
		<div class="gm-faq gm-area__faq">
			<?php foreach ( gm_area_faqs( $slug ) as $q => $a ) : ?>
				<details>
					<summary><?php echo esc_html( $q ); ?><span aria-hidden="true">+</span></summary>
					<p><?php echo esc_html( $a ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>

		<?php if ( $others ) : ?>
			<h2 class="gm-subtitle gm-area__h">We also deliver to</h2>
			<ul class="gm-local__areas gm-area__others">
				<?php foreach ( $others as $gm_s => $gm_n ) : ?>
					<li><a href="<?php echo esc_url( gm_area_url( $gm_s ) ); ?>">Indian takeaway <?php echo esc_html( $gm_n ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<div class="gm-cta-row">
			<p>Hungry? Book your slot now — <?php echo esc_html( $area ); ?> deliveries run <?php echo esc_html( gm_hours_text() ); ?>.</p>
			<a class="gm-btn" href="#menu">Order now</a>
		</div>
	</div>
</section>
	<?php
}
