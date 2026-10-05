<?php
/**
 * Menu, allergen table and ordering rules.
 *
 * This is the one file to edit when the menu, prices or allergens change.
 * Prices are in pounds. Keep each dish `id` unique and never reuse an old id
 * for a different dish — past orders refer to dishes by id.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ordering rules.
 */
function gm_rules() {
	return array(
		'min_order'    => 10.00,                                   // Minimum basket (pounds).
		'slot_starts'  => array( '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30' ),
		'window_mins'  => 15,                                      // Length of each delivery window.
		'closed_days'  => array( 5, 6 ),                           // 0 = Sunday … 6 = Saturday. Friday & Saturday closed.
		'cutoff_hour'  => 19,                                      // Orders close at 7pm the day before.
		'hold_minutes' => 35,                                      // How long an unpaid card checkout holds its slot.
		'timezone'     => 'Europe/London',
	);
}

/**
 * The menu the site started with. It is copied into WP Admin → Menu the first
 * time the theme runs (see inc/menu-admin.php); after that, edit dishes there.
 */
function gm_menu_seed() {
	return array(
		array(
			'title' => 'Starters',
			'note'  => 'Served with salad & mint sauce',
			'items' => array(
				array( 'id' => 'vegSamosa', 'name' => 'Vegetable Samosas', 'price' => 3.95, 'veg' => true, 'desc' => 'Golden, crisp pastries generously filled with gently spiced vegetables, fried until perfectly flaky and satisfying.', 'allergens' => array() ),
				array( 'id' => 'lambSamosa', 'name' => 'Lamb Samosas', 'price' => 3.95, 'desc' => 'Crispy pastry parcels filled with seasoned minced lamb, rich in flavour and comforting in every bite.', 'allergens' => array() ),
				array( 'id' => 'tikkaRolls', 'name' => 'Chicken Tikka Spring-Rolls', 'price' => 3.95, 'rec' => true, 'desc' => 'Crisp golden rolls filled with our spiced chicken tikka, fried until flaky and satisfying.', 'allergens' => array( 'dairy' => 'P', 'mustard' => 'Y', 'sulphites' => 'Y', 'egg' => 'P', 'gluten' => 'Y' ) ),
				array( 'id' => 'chickenTikka', 'name' => 'Chicken Tikka', 'price' => 4.95, 'desc' => 'Tender chicken pieces marinated in our flavourful homemade tikka spices, gently cooked with fried onions for extra depth.', 'allergens' => array( 'mustard' => 'Y', 'sulphites' => 'Y' ) ),
				array( 'id' => 'fishPakora', 'name' => 'Fish Pakora', 'price' => 4.95, 'desc' => 'Succulent pieces of fish coated in our in-house spiced batter, fried until crisp and golden.', 'allergens' => array( 'fish' => 'Y', 'dairy' => 'P', 'egg' => 'Y', 'gluten' => 'Y', 'celery' => 'P' ) ),
			),
		),
		array(
			'title' => 'Curries',
			'note'  => 'Spice level adjusted on request',
			'items' => array(
				array( 'id' => 'chickenCurry', 'name' => 'Chicken Curry (boneless)', 'price' => 7.95, 'rec' => true, 'adjustable' => true, 'desc' => 'Tender chicken breast pieces, slow-cooked in a rich, home style curry sauce, balanced with traditional spices for comforting flavour.', 'allergens' => array() ),
				array( 'id' => 'lambCurry', 'name' => 'Lamb Curry', 'price' => 8.95, 'rec' => true, 'adjustable' => true, 'desc' => 'Succulent lamb on the bone, gently cooked until tender in a hearty, spiced curry sauce full of warmth and depth.', 'allergens' => array() ),
				array( 'id' => 'prawnCurry', 'name' => 'King Prawn Curry', 'price' => 9.45, 'rec' => true, 'adjustable' => true, 'desc' => 'Juicy king prawns cooked in a fragrant, lightly spiced curry sauce — comforting, rich and perfectly balanced.', 'allergens' => array( 'crustaceans' => 'Y' ) ),
				array( 'id' => 'tarkaDhal', 'name' => 'Tarka Dhal', 'price' => 6.45, 'rec' => true, 'veg' => true, 'adjustable' => true, 'desc' => 'A lentil curry slow-cooked until soft and creamy, finished with a fragrant tarka of garlic, onions, dry chillies and warming spices.', 'allergens' => array( 'dairy' => 'P' ) ),
			),
		),
		array(
			'title' => 'Sundries',
			'note'  => '',
			'items' => array(
				array( 'id' => 'paratha', 'name' => 'Paratha', 'price' => 1.95, 'veg' => true, 'desc' => 'Soft, flaky flatbread, freshly cooked and perfect for scooping up curries.', 'allergens' => array( 'dairy' => 'P', 'gluten' => 'Y' ) ),
				array( 'id' => 'boiledRice', 'name' => 'Boiled Rice', 'price' => 3.45, 'veg' => true, 'desc' => 'Steamed plain rice, light and fluffy — the perfect base for any curry.', 'allergens' => array() ),
				array( 'id' => 'friedRice', 'name' => 'Fried Rice', 'price' => 4.45, 'veg' => true, 'desc' => 'Lightly fried rice seasoned for flavour, simple and satisfying.', 'allergens' => array() ),
				array( 'id' => 'chips', 'name' => 'Chips', 'price' => 2.95, 'veg' => true, 'desc' => 'Classic golden chips, freshly cooked and crisp.', 'allergens' => array() ),
				array( 'id' => 'masalaChips', 'name' => 'Masala Chips', 'price' => 4.95, 'rec' => true, 'veg' => true, 'desc' => 'Crispy chips tossed in warming masala spices for a bold, flavourful twist.', 'allergens' => array( 'sulphites' => 'Y' ) ),
				array( 'id' => 'biranBread', 'name' => 'Biran Bread', 'price' => 3.95, 'rec' => true, 'veg' => true, 'desc' => 'Traditional street style bread stir-fry with onions and warming spices, finished with fresh coriander for a comforting, savoury bite.', 'allergens' => array( 'gluten' => 'Y' ) ),
			),
		),
		array(
			'title' => 'English',
			'note'  => 'Served with chips & a can of drink',
			'items' => array(
				array( 'id' => 'filletBurger', 'name' => 'Chicken Fillet Burger (meal)', 'price' => 7.95, 'desc' => 'Crispy chicken fillet served in a soft brioche bun with fresh salad and mayo.', 'allergens' => array( 'gluten' => 'P' ) ),
				array( 'id' => 'nuggets', 'name' => 'Chicken Nuggets & Chips', 'price' => 7.95, 'desc' => 'Golden, crunchy chicken nuggets and chips served with fresh salad.', 'allergens' => array( 'gluten' => 'P' ) ),
				array( 'id' => 'fishChips', 'name' => 'Fish & Chips', 'price' => 7.95, 'desc' => 'Crispy battered cod served with a side of fresh salad.', 'allergens' => array( 'gluten' => 'Y' ) ),
			),
		),
		array(
			'title' => 'Drinks',
			'note'  => '',
			'items' => array(
				array( 'id' => 'coke', 'name' => 'Coke (can)', 'price' => 1.45, 'veg' => true, 'no_allergen_row' => true ),
				array( 'id' => 'cokeZero', 'name' => 'Coke Zero (can)', 'price' => 1.45, 'veg' => true, 'no_allergen_row' => true ),
				array( 'id' => 'rubMango', 'name' => 'Rubicon Mango (can)', 'price' => 1.45, 'veg' => true, 'no_allergen_row' => true ),
				array( 'id' => 'rubPassion', 'name' => 'Rubicon Passion (can)', 'price' => 1.45, 'veg' => true, 'no_allergen_row' => true ),
				array( 'id' => 'j2o', 'name' => 'J2O (orange & passion)', 'price' => 2.95, 'veg' => true, 'no_allergen_row' => true ),
				array( 'id' => 'water', 'name' => 'Water (bottle)', 'price' => 0.89, 'veg' => true, 'no_allergen_row' => true ),
			),
		),
	);
}

/**
 * The 14 declarable allergens, in table order.
 */
function gm_allergen_cols() {
	return array(
		'fish'        => 'Fish',
		'crustaceans' => 'Crustaceans',
		'dairy'       => 'Dairy',
		'mustard'     => 'Mustard',
		'sulphites'   => 'Sulphites',
		'egg'         => 'Egg',
		'gluten'      => 'Gluten',
		'celery'      => 'Celery',
		'nuts'        => 'Nuts',
		'peanuts'     => 'Peanuts',
		'molluscs'    => 'Molluscs',
		'soya'        => 'Soya',
		'lupins'      => 'Lupins',
		'sesame'      => 'Sesame',
	);
}

/**
 * Allergen table, built from the dishes' allergen settings in WP Admin → Menu.
 * "Y" = contains, "P" = may contain traces.
 */
function gm_allergen_table() {
	$groups = array();
	foreach ( gm_menu() as $group ) {
		$rows = array();
		foreach ( $group['items'] as $item ) {
			if ( empty( $item['no_allergen_row'] ) ) {
				$rows[] = array(
					'name'  => $item['name'],
					'marks' => $item['allergens'] ?? array(),
				);
			}
		}
		if ( $rows ) {
			$groups[] = array( 'title' => $group['title'], 'rows' => $rows );
		}
	}
	return $groups;
}

/**
 * Flat id => item lookup, prices converted to pence.
 */
function gm_menu_index() {
	static $index = null;
	if ( null === $index ) {
		$index = array();
		foreach ( gm_menu() as $group ) {
			foreach ( $group['items'] as $item ) {
				$item['pence']        = (int) round( $item['price'] * 100 );
				$index[ $item['id'] ] = $item;
			}
		}
	}
	return $index;
}

function gm_money( $pence ) {
	return '£' . number_format( $pence / 100, 2 );
}
