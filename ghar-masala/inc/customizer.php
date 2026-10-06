<?php
/**
 * Appearance → Customize → "Ghar Masala": edit the site's words, pictures,
 * colours and fonts with a live preview.
 *
 * Text boxes accept these placeholders, filled in from your settings so they
 * stay correct when you change hours or delivery rules:
 *   {hours} {days} {cutoff} {window} {phone} {delivery} {days_ahead}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_mod_defaults() {
	return array(
		// Home
		'home_kicker'      => 'Tividale · Oldbury · Fresh to order',
		'home_title'       => 'Good food starts in the Kitchen.',
		'img_hero'         => '',
		// Countdown & welcome
		'countdown_show'   => 'show',
		'countdown_text'   => 'Cooked fresh on the day — pre-orders for {next} close in',
		'welcome_show'     => 'show',
		'welcome_back'     => 'Welcome back, {name}! We hope you enjoyed your {dish} — it was lovely cooking for you.',
		'welcome_upcoming' => 'Welcome back, {name}! Your {dish} is booked in for {date} — we’re looking forward to cooking for you.',
		'welcome_first'    => 'Welcome, {name} — lovely to have you at Ghar Masala. Pull up a chair and have a look at the menu.',
		'checker_show'     => 'show',
		'checker_title'    => 'Do we deliver to you?',
		'checker_out'      => 'We don’t usually deliver that far — but get in touch and we’ll see if we can arrange something.',
		// Search engines (local SEO)
		'seo_title'        => 'Ghar Masala | Home-Cooked Indian & Bangladeshi Curry Delivery in Tividale & Oldbury',
		'seo_description'  => 'Fresh, home-style Indian and Bangladeshi curries cooked to order and delivered across {areas}. Halal, no base sauce, free delivery within 2 miles. Pre-order online.',
		'seo_town'         => 'Tividale',
		'seo_areas'        => 'Tividale, Oldbury, Tipton, Dudley Port, Great Bridge, Rowley Regis, Burnt Tree',
		'seo_cuisine'      => 'Indian, Bangladeshi, Curry, Halal',
		'local_heading'    => 'Home-cooked Indian & Bangladeshi food, delivered in {town}',
		'local_text'       => 'Ghar Masala is a local, family-style kitchen cooking authentic Bangladeshi and Indian curries the way they’re made at home — fresh on the day, halal, and with no base sauce. We deliver to {areas}: free within 2 miles, and up to 3 miles from Tividale. Order online for an evening delivery slot.',
		// My story
		'story_title'      => '<strong>Ghar Masala</strong>… it means House of Spice.',
		'img_story_wide'   => '',
		'story_caption_1'  => 'Spices blended in the kitchen, not bought in as paste.',
		'story_intro'      => 'I was born and raised in the UK, in a Bengali household with Bangladeshi heritage. Food was how culture got passed down: how the family talked to each other, how guests were welcomed, how a bad day was fixed. Nothing about it was fussy. A curry, a pot of rice, something fried if people were lucky.

Years later I was working in a family restaurant, and the same question kept coming back over the counter — <em>can I buy the curry you cook at home?</em> Not the heavy, glossy version on the menu. The everyday one. There was a gap sitting in plain sight, and I decided to cook for it.',
		'story_quote'      => 'Tradition served with comfort. It is on the logo because it is the whole brief.',
		'story_body'       => 'So Ghar Masala runs on pre-orders only. You book a slot, I shop for that evening, and everything is cooked on the day it goes out — spices blended here, meat marinated overnight, no vats of sauce sitting around. It is why the menu is short, and why I am not trying to sell you everything.

I tested it the honest way: leaflets through doors in Tividale, cooking for neighbours and strangers, listening to what came back. People told me the food tasted like someone\'s kitchen rather than a takeaway, and that the portions were generous. That was the moment it stopped being an idea.

The person who cooks your order is the person who drives it to your door. For now that is the whole business, and I would rather it stayed that close for as long as it can.',
		'story_signoff'    => 'Ahmed — founder, Ghar Masala',
		'img_story_side'   => '',
		'story_caption_2'  => 'Cooked on the day of delivery — never held over.',
		// How it works
		'how_intro'        => 'Every dish is cooked to order, so the kitchen works to booked slots rather than walk-ins. That means a fixed {window}-minute delivery window and no guesswork about when your food arrives.',
		'img_how'          => '',
		// Reviews, contact, FAQs
		'reviews_heading'  => 'What our customers are saying',
		'reviews_intro'    => 'What people who have ordered from Ghar Masala say about the food.',
		'contact_intro'    => 'Questions about the menu, allergies, catering or an order? Get in touch — we usually reply the same day.',
		'faqs'             => 'What makes you different?
Fresh ingredients, cooked on the day of delivery — the pre-order method makes that possible. No base sauce, and nothing ultra-processed like others.

How do I place an order?
Build your basket on the Menu page, then book a delivery slot — that takes you straight to checkout.

How far ahead can I order?
Up to {days_ahead} days ahead. Orders for a given evening must be placed by {cutoff}.

Is there a minimum order?
Yes, £10. {delivery} Your charge is worked out from your postcode at checkout.

What are your delivery hours?
Deliveries run {hours}, {days}. Each slot gives you a {window}-minute delivery window.

Is the food halal?
Yes — look for the حلال mark in the header on every page.

Can I choose how spicy my curry is?
Yes — curries marked “spice to order” have a spice-level choice in your basket. Madras and Vindaloo are 30p extra.

Do you cater for allergies?
Check the allergen table before you order and add a note at checkout — we will always talk it through with you.

Do you have a loyalty scheme?
Yes — create a free account and every order of £20 or more earns a stamp. Your 10th order is 50% off (or save the reward for a later order).

Can I change or cancel an order after paying?
Ring the kitchen as soon as you can on {phone} — we can usually help if your slot has not started yet.',
		// Look
		'color_green'      => '#006a4e',
		'color_gold'       => '#eda01e',
		'color_bg'         => '#f7f5f1',
		'color_text'       => '#16211c',
		'font_heading'     => 'Archivo',
		'font_body'        => 'Archivo',
		'font_size'        => 16,
	);
}

function gm_mod( $key ) {
	$d = gm_mod_defaults();
	$v = get_theme_mod( 'gm_' . $key, $d[ $key ] ?? '' );
	return '' === $v || null === $v ? ( $d[ $key ] ?? '' ) : $v;
}

/** Replace {hours} etc. with the live values. */
function gm_fill( $text ) {
	return strtr(
		(string) $text,
		array(
			'{hours}'      => gm_hours_text(),
			'{days}'       => gm_days_text(),
			'{cutoff}'     => gm_cutoff_text(),
			'{window}'     => (string) (int) gm_schedule()['window'],
			'{phone}'      => gm_setting( 'phone' ),
			'{delivery}'   => gm_delivery_summary(),
			'{days_ahead}' => (string) (int) gm_setting( 'days_ahead' ),
		)
	);
}

/** Text box → safe paragraphs (blank line = new paragraph; <strong>/<em> allowed). */
function gm_paras( $key ) {
	return wp_kses( wpautop( gm_fill( gm_mod( $key ) ) ), array( 'p' => array(), 'br' => array(), 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array() ) ) );
}

/** One line of text with <strong>/<em> allowed. */
function gm_line( $key ) {
	return wp_kses( gm_fill( gm_mod( $key ) ), array( 'strong' => array(), 'em' => array(), 'br' => array() ) );
}

/** A picture setting, or the theme's own photo. */
function gm_img( $key, $fallback ) {
	$v = gm_mod( $key );
	return $v ? $v : gm_asset( 'images/' . $fallback );
}

/** FAQs: blocks separated by a blank line; first line = question, the rest = answer. */
function gm_faq_list() {
	$out = array();
	foreach ( preg_split( "/\R\s*\R/", trim( str_replace( "\r", '', gm_mod( 'faqs' ) ) ) ) as $block ) {
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $block ) ), 'strlen' ) );
		if ( count( $lines ) >= 2 ) {
			$out[ gm_fill( array_shift( $lines ) ) ] = gm_fill( implode( ' ', $lines ) );
		}
	}
	return $out;
}

function gm_font_choices() {
	// name => Google Fonts family spec ('' = bundled with the theme)
	return array(
		'Archivo'          => '',
		'Poppins'          => 'Poppins:wght@400;500;600;700',
		'Montserrat'       => 'Montserrat:wght@400;500;600;700',
		'Nunito'           => 'Nunito:wght@400;500;600;700',
		'Lato'             => 'Lato:wght@400;700',
		'Open Sans'        => 'Open+Sans:wght@400;500;600;700',
		'Playfair Display' => 'Playfair+Display:wght@400;500;600;700',
		'Lora'             => 'Lora:wght@400;500;600;700',
		'DM Serif Display' => 'DM+Serif+Display',
		'Merriweather'     => 'Merriweather:wght@400;700',
	);
}

/* ---------------------------------------------------------------------------
 * The Customizer panel
 * ------------------------------------------------------------------------ */

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$d = gm_mod_defaults();
	$wp->add_panel( 'gm_panel', array( 'title' => 'Ghar Masala', 'priority' => 1, 'description' => 'Change the words, pictures, colours and fonts on your website. In text boxes you can use {hours}, {days}, {cutoff}, {window}, {phone}, {delivery} and {days_ahead} — they fill in from your settings.' ) );

	$section = function ( $id, $title ) use ( $wp ) {
		$wp->add_section( 'gm_' . $id, array( 'title' => $title, 'panel' => 'gm_panel' ) );
	};
	$text = function ( $section, $key, $label, $type = 'text', $desc = '' ) use ( $wp, $d ) {
		$wp->add_setting( 'gm_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => 'textarea' === $type ? 'wp_kses_post' : 'wp_kses_post' ) );
		$wp->add_control( 'gm_' . $key, array( 'label' => $label, 'section' => 'gm_' . $section, 'type' => $type, 'description' => $desc ) );
	};
	$image = function ( $section, $key, $label, $desc ) use ( $wp ) {
		$wp->add_setting( 'gm_' . $key, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp->add_control( new WP_Customize_Image_Control( $wp, 'gm_' . $key, array( 'label' => $label, 'section' => 'gm_' . $section, 'description' => $desc ) ) );
	};

	$section( 'home', 'Home page' );
	$text( 'home', 'home_kicker', 'Small line above the headline' );
	$text( 'home', 'home_title', 'Headline' );
	$image( 'home', 'img_hero', 'Main picture', 'A wide, landscape photo works best (at least 1600px wide). Remove it to go back to the original.' );

	$section( 'welcome', 'Countdown, welcome & delivery checker' );
	$show = function ( $key, $label, $desc ) use ( $wp, $d ) {
		$wp->add_setting( 'gm_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => function ( $v ) { return 'hide' === $v ? 'hide' : 'show'; } ) );
		$wp->add_control( 'gm_' . $key, array( 'label' => $label, 'section' => 'gm_welcome', 'type' => 'select', 'choices' => array( 'show' => 'Show', 'hide' => 'Hide' ), 'description' => $desc ) );
	};
	$show( 'countdown_show', 'Next delivery & countdown', 'Shows the next delivery date and a live countdown to its order deadline on the home and menu pages.' );
	$text( 'welcome', 'countdown_text', 'Countdown wording', 'text', '{next} = the next delivery day, e.g. “Wednesday 7 October”. The timer follows.' );
	$show( 'welcome_show', 'Welcome message for signed-in customers', 'Greets customers by name on the home and menu pages.' );
	$text( 'welcome', 'welcome_back', 'After a past order', 'textarea', '{name} = first name, {dish} = what they ordered last time.' );
	$text( 'welcome', 'welcome_upcoming', 'When an order is still to come', 'textarea', '{name}, {dish}, {date} = the delivery day.' );
	$text( 'welcome', 'welcome_first', 'Signed in, no orders yet', 'textarea', '{name} = first name.' );
	$show( 'checker_show', 'Postcode delivery checker', 'Lets customers check their postcode on the menu page before ordering.' );
	$text( 'welcome', 'checker_title', 'Delivery checker heading' );
	$text( 'welcome', 'checker_out', 'Message when we don’t deliver there', 'textarea', 'Call and “Message us” buttons are added underneath.' );

	$section( 'seo', 'Search engines (Google)' );
	$text( 'seo', 'seo_title', 'Home page title in Google', 'text', 'About 50–60 characters. Include what you sell and where, e.g. “Indian Curry Delivery in Tividale”.' );
	$text( 'seo', 'seo_description', 'Home page description in Google', 'textarea', 'About 150 characters. {areas} = the areas below.' );
	$text( 'seo', 'seo_town', 'Your town', 'text', 'Used in page titles, e.g. “Menu — Curry Delivery in Tividale”.' );
	$text( 'seo', 'seo_areas', 'Areas you deliver to', 'textarea', 'Separate with commas. Shown on the home page and told to Google.' );
	$text( 'seo', 'seo_cuisine', 'Cuisine', 'text', 'Separate with commas.' );
	$text( 'seo', 'local_heading', 'Home page: local heading', 'text', '{town} and {areas} work here.' );
	$text( 'seo', 'local_text', 'Home page: local introduction', 'textarea', 'A short paragraph about your food and where you deliver — this helps Google show you to local people.' );

	$section( 'story', 'My story' );
	$text( 'story', 'story_title', 'Title', 'text', 'Wrap words in <strong>…</strong> for bold.' );
	$image( 'story', 'img_story_wide', 'Wide picture', 'Shown across the page under the title.' );
	$text( 'story', 'story_caption_1', 'Wide picture caption' );
	$text( 'story', 'story_intro', 'Opening paragraphs', 'textarea', 'Leave a blank line between paragraphs.' );
	$text( 'story', 'story_quote', 'Quote' );
	$text( 'story', 'story_body', 'More of the story', 'textarea', 'Leave a blank line between paragraphs.' );
	$text( 'story', 'story_signoff', 'Signed' );
	$image( 'story', 'img_story_side', 'Side picture', 'A landscape photo works best.' );
	$text( 'story', 'story_caption_2', 'Side picture caption' );

	$section( 'how', 'How it works' );
	$text( 'how', 'how_intro', 'Introduction', 'textarea' );
	$image( 'how', 'img_how', 'Picture', 'A tall (portrait) photo works best. It is shown in black and white.' );

	$section( 'pages', 'Reviews, contact & FAQs' );
	$text( 'pages', 'reviews_heading', 'Reviews slideshow heading' );
	$text( 'pages', 'reviews_intro', 'Reviews page introduction', 'textarea' );
	$text( 'pages', 'contact_intro', 'Contact page introduction', 'textarea' );
	$text( 'pages', 'faqs', 'FAQs', 'textarea', 'One question per block: the question on the first line, the answer underneath, then a blank line before the next question.' );

	$section( 'look', 'Colours & fonts' );
	foreach ( array( 'color_green' => 'Main colour (green)', 'color_gold' => 'Highlight colour (gold)', 'color_bg' => 'Page background', 'color_text' => 'Text colour' ) as $key => $label ) {
		$wp->add_setting( 'gm_' . $key, array( 'default' => $d[ $key ], 'sanitize_callback' => 'sanitize_hex_color' ) );
		$wp->add_control( new WP_Customize_Color_Control( $wp, 'gm_' . $key, array( 'label' => $label, 'section' => 'gm_look' ) ) );
	}
	$fonts = array_combine( array_keys( gm_font_choices() ), array_keys( gm_font_choices() ) );
	foreach ( array( 'font_heading' => 'Font for headings', 'font_body' => 'Font for text' ) as $key => $label ) {
		$wp->add_setting( 'gm_' . $key, array( 'default' => 'Archivo', 'sanitize_callback' => function ( $v ) { return array_key_exists( $v, gm_font_choices() ) ? $v : 'Archivo'; } ) );
		$wp->add_control( 'gm_' . $key, array( 'label' => $label, 'section' => 'gm_look', 'type' => 'select', 'choices' => $fonts ) );
	}
	$wp->add_setting( 'gm_font_size', array( 'default' => 16, 'sanitize_callback' => 'absint' ) );
	$wp->add_control( 'gm_font_size', array( 'label' => 'Text size', 'section' => 'gm_look', 'type' => 'select', 'choices' => array( 15 => 'A little smaller', 16 => 'Standard', 17 => 'A little larger', 18 => 'Larger' ) ) );
} );

/* ---------------------------------------------------------------------------
 * Apply colours & fonts
 * ------------------------------------------------------------------------ */

add_action( 'wp_enqueue_scripts', function () {
	$fonts = gm_font_choices();
	$need  = array_unique( array_filter( array( $fonts[ gm_mod( 'font_heading' ) ] ?? '', $fonts[ gm_mod( 'font_body' ) ] ?? '' ) ) );
	if ( $need ) {
		wp_enqueue_style( 'gm-fonts', 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $need ) . '&display=swap', array(), null );
	}

	// Only print what has been changed, so the standard look stays exactly as designed.
	$d     = gm_mod_defaults();
	$green = gm_mod( 'color_green' );
	$gold  = gm_mod( 'color_gold' );
	$vars  = '';
	if ( strtolower( $green ) !== $d['color_green'] ) {
		$vars .= '--color-accent:' . $green . ';'
			. '--color-accent-600:color-mix(in srgb,' . $green . ' 82%,#000);'
			. '--color-accent-700:color-mix(in srgb,' . $green . ' 67%,#000);'
			. '--color-accent-100:color-mix(in srgb,' . $green . ' 9%,#fff);';
	}
	if ( strtolower( $gold ) !== $d['color_gold'] ) {
		$vars .= '--color-accent-2:' . $gold . ';'
			. '--color-accent-2-500:' . $gold . ';'
			. '--color-accent-2-400:color-mix(in srgb,' . $gold . ' 85%,#fff);'
			. '--color-accent-2-600:color-mix(in srgb,' . $gold . ' 87%,#000);'
			. '--color-accent-2-700:color-mix(in srgb,' . $gold . ' 68%,#000);'
			. '--color-accent-2-800:color-mix(in srgb,' . $gold . ' 49%,#000);'
			. '--color-accent-2-900:color-mix(in srgb,' . $gold . ' 31%,#000);'
			. '--color-accent-2-100:color-mix(in srgb,' . $gold . ' 12%,#fff);';
	}
	if ( strtolower( gm_mod( 'color_bg' ) ) !== $d['color_bg'] ) {
		$vars .= '--color-bg:' . gm_mod( 'color_bg' ) . ';--cream:' . gm_mod( 'color_bg' ) . ';';
	}
	if ( strtolower( gm_mod( 'color_text' ) ) !== $d['color_text'] ) {
		$vars .= '--color-text:' . gm_mod( 'color_text' ) . ';';
	}
	if ( 'Archivo' !== gm_mod( 'font_body' ) ) {
		$vars .= '--font:"' . gm_mod( 'font_body' ) . '",system-ui,sans-serif;';
	}
	if ( 'Archivo' !== gm_mod( 'font_heading' ) ) {
		$vars .= '--font-head:"' . gm_mod( 'font_heading' ) . '",system-ui,sans-serif;';
	}
	$css  = $vars ? ':root{' . $vars . '}' : '';
	$size = max( 15, min( 18, (int) gm_mod( 'font_size' ) ) );
	if ( 16 !== $size ) {
		$css .= '#gm-main,.gm-footer{zoom:' . round( $size / 16, 4 ) . '}'; // scales every text size together
	}
	if ( '' === $css ) {
		return;
	}
	wp_add_inline_style( 'ghar-masala', $css );
}, 20 );
