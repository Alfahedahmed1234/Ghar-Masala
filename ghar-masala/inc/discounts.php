<?php
/**
 * Discounts, managed in WP Admin → Discounts.
 *
 *   Discount code  – the customer types it at checkout (e.g. WELCOME10).
 *   Automatic      – applies by itself once the food total reaches a minimum
 *                    spend (e.g. spend £15, get 10% off).
 *
 * Both are worked out on the food total (before delivery). Publish = on,
 * Draft = off. Discounts → Settings decides whether a code can be used on top
 * of an automatic discount; when it can't, the customer gets the better one.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	register_post_type(
		'gm_discount',
		array(
			'labels'        => array(
				'name'               => 'Discounts',
				'singular_name'      => 'Discount',
				'menu_name'          => 'Discounts',
				'all_items'          => 'All discounts',
				'add_new'            => 'Add discount',
				'add_new_item'       => 'Add a discount',
				'edit_item'          => 'Edit discount',
				'search_items'       => 'Search discounts',
				'not_found'          => 'No discounts yet.',
				'not_found_in_trash' => 'No discounts in the bin.',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 4,
			'menu_icon'     => 'dashicons-tickets-alt',
			'supports'      => array( 'title' ),
		)
	);
} );

/* ---------------------------------------------------------------------------
 * Reading the rules
 * ------------------------------------------------------------------------ */

function gm_discount_stacking() {
	return (bool) get_option( 'gm_discount_stack', 0 );
}

/** One discount as a plain array, or null. */
function gm_discount_rule( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'gm_discount' !== $post->post_type ) {
		return null;
	}
	$m = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, '_gm_' . $k, true );
	};
	$type = 'fixed' === $m( 'type' ) ? 'fixed' : 'percent';
	return array(
		'id'      => $post->ID,
		'kind'    => 'auto' === $m( 'kind' ) ? 'auto' : 'code',
		'code'    => strtoupper( (string) $m( 'code' ) ),
		'label'   => wp_specialchars_decode( $post->post_title, ENT_QUOTES ),
		'type'    => $type,
		'amount'  => 'fixed' === $type ? (int) round( (float) $m( 'amount' ) * 100 ) : (float) $m( 'amount' ), // pence, or percent
		'min'     => (int) round( (float) $m( 'min_spend' ) * 100 ),
		'expires' => (string) $m( 'expires' ),
		'limit'   => (int) $m( 'limit' ),
		'used'    => (int) $m( 'used' ),
		'active'  => 'publish' === $post->post_status,
	);
}

/** Live rules of one kind: published, not expired, not used up. */
function gm_discount_rules( $kind ) {
	$today = gm_now()->format( 'Y-m-d' );
	$rules = array();
	foreach ( get_posts( array( 'post_type' => 'gm_discount', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $post ) {
		$r = gm_discount_rule( $post );
		if ( $r['kind'] !== $kind || $r['amount'] <= 0 ) {
			continue;
		}
		if ( ( $r['expires'] && $r['expires'] < $today ) || ( $r['limit'] && $r['used'] >= $r['limit'] ) ) {
			continue;
		}
		$rules[] = $r;
	}
	return $rules;
}

function gm_discount_value( array $r, $subtotal ) {
	$v = 'fixed' === $r['type'] ? $r['amount'] : (int) round( $subtotal * $r['amount'] / 100 );
	return max( 0, min( $v, $subtotal ) );
}

/** "10% off" / "£2.00 off" */
function gm_discount_offer( array $r ) {
	return ( 'fixed' === $r['type'] ? gm_money( $r['amount'] ) : rtrim( rtrim( number_format( $r['amount'], 2 ), '0' ), '.' ) . '%' ) . ' off';
}

/** The public parts of a rule, for the page's JavaScript. */
function gm_discount_public( array $r ) {
	return array(
		'kind'   => $r['kind'],
		'code'   => 'code' === $r['kind'] ? $r['code'] : '',
		'label'  => $r['label'] ? $r['label'] : gm_discount_offer( $r ),
		'offer'  => gm_discount_offer( $r ),
		'type'   => $r['type'],
		'amount' => $r['amount'],
		'min'    => $r['min'],
	);
}

function gm_find_discount_code( $code ) {
	$code = strtoupper( trim( (string) $code ) );
	if ( '' === $code ) {
		return null;
	}
	foreach ( gm_discount_rules( 'code' ) as $r ) {
		if ( $r['code'] === $code ) {
			return $r;
		}
	}
	return null;
}

/**
 * Work out the discount on a food subtotal (pence).
 *
 * @return array|WP_Error {
 *     pence  int      Total discount.
 *     lines  array[]  [ [ 'label' => '10% off orders over £15', 'pence' => 150 ] ]
 *     code   ?array   The code rule that was applied, if any.
 *     note   string   Explanation when a code could not be combined.
 * }
 */
function gm_apply_discounts( $subtotal, $code = '' ) {
	$auto      = null;
	$auto_v    = 0;
	foreach ( gm_discount_rules( 'auto' ) as $r ) {
		$v = $subtotal >= $r['min'] ? gm_discount_value( $r, $subtotal ) : 0;
		if ( $v > $auto_v ) {
			$auto   = $r;
			$auto_v = $v;
		}
	}

	$code_rule = null;
	$code_v    = 0;
	if ( '' !== trim( (string) $code ) ) {
		$code_rule = gm_find_discount_code( $code );
		if ( ! $code_rule ) {
			return new WP_Error( 'gm_discount_invalid', 'That discount code isn’t valid or has expired.' );
		}
		if ( $subtotal < $code_rule['min'] ) {
			return new WP_Error( 'gm_discount_min', 'The code ' . $code_rule['code'] . ' needs a food total of at least ' . gm_money( $code_rule['min'] ) . '.' );
		}
		$code_v = gm_discount_value( $code_rule, $subtotal );
	}

	$out = array( 'pence' => 0, 'lines' => array(), 'code' => null, 'note' => '' );
	$add = function ( $r, $v ) use ( &$out ) {
		$out['lines'][] = array( 'label' => ( 'code' === $r['kind'] ? 'Code ' . $r['code'] . ': ' : '' ) . ( $r['label'] ? $r['label'] : gm_discount_offer( $r ) ), 'pence' => $v );
		$out['pence']  += $v;
		if ( 'code' === $r['kind'] ) {
			$out['code'] = $r;
		}
	};

	if ( $auto && $code_rule && ! gm_discount_stacking() ) {
		// Not combinable: give the customer whichever saves them more.
		if ( $code_v > $auto_v ) {
			$add( $code_rule, $code_v );
		} else {
			$add( $auto, $auto_v );
			$out['note'] = 'Discount codes can’t be combined with our automatic offer, so we’ve applied the better saving.';
		}
	} else {
		if ( $auto ) {
			$add( $auto, $auto_v );
		}
		if ( $code_rule ) {
			$add( $code_rule, $code_v );
		}
	}
	$out['pence'] = min( $out['pence'], $subtotal );
	return $out;
}

/** Count a code as used once its order is confirmed. */
function gm_count_discount_use( $order_id ) {
	$rule_id = (int) get_post_meta( $order_id, '_gm_discount_code_id', true );
	if ( $rule_id && ! get_post_meta( $order_id, '_gm_discount_counted', true ) ) {
		update_post_meta( $order_id, '_gm_discount_counted', 1 );
		update_post_meta( $rule_id, '_gm_used', (int) get_post_meta( $rule_id, '_gm_used', true ) + 1 );
	}
}

/* REST: check a code from the checkout. */
add_action( 'rest_api_init', function () {
	register_rest_route(
		'ghar-masala/v1',
		'/discount',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( WP_REST_Request $request ) {
				do_action( 'litespeed_control_set_nocache', 'ghar masala discount' );
				$r = gm_find_discount_code( (string) $request['code'] );
				if ( ! $r ) {
					return new WP_Error( 'gm_discount_invalid', 'That discount code isn’t valid or has expired.', array( 'status' => 404 ) );
				}
				return gm_discount_public( $r );
			},
		)
	);
} );

/* ---------------------------------------------------------------------------
 * Admin: edit screen
 * ------------------------------------------------------------------------ */

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gm_discount' === $post->post_type ? 'Name shown to customers, e.g. “10% off orders over £15”' : $text;
}, 10, 2 );

add_action( 'add_meta_boxes_gm_discount', function () {
	remove_meta_box( 'slugdiv', 'gm_discount', 'normal' );
	add_meta_box( 'gm_discount_box', 'Discount details', 'gm_render_discount_box', 'gm_discount', 'normal', 'high' );
} );

function gm_render_discount_box( $post ) {
	wp_nonce_field( 'gm_discount_save', 'gm_discount_nonce' );
	$m    = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, '_gm_' . $k, true );
		return '' === $v ? $d : $v;
	};
	$kind = $m( 'kind', 'code' );
	?>
	<style>.gm-dk label{display:block;margin:0 0 8px}.gm-dk .gm-code-only{display:<?php echo 'auto' === $kind ? 'none' : 'table-row'; ?>}</style>
	<table class="form-table gm-dk" role="presentation">
		<tr><th scope="row">Type</th>
			<td>
				<label><input type="radio" name="gm_kind" value="code" <?php checked( $kind, 'code' ); ?> onchange="document.querySelectorAll('.gm-code-only').forEach(function(r){r.style.display='table-row'})"> <strong>Discount code</strong> — customers type a code at checkout</label>
				<label><input type="radio" name="gm_kind" value="auto" <?php checked( $kind, 'auto' ); ?> onchange="document.querySelectorAll('.gm-code-only').forEach(function(r){r.style.display='none'})"> <strong>Automatic</strong> — applies by itself when the food total reaches the minimum spend</label>
			</td></tr>
		<tr class="gm-code-only"><th scope="row"><label for="gm-code">Code</label></th>
			<td><input type="text" id="gm-code" name="gm_code" value="<?php echo esc_attr( $m( 'code' ) ); ?>" style="text-transform:uppercase;width:14em" placeholder="e.g. WELCOME10">
			<p class="description">Letters and numbers, no spaces. Customers can type it in upper or lower case.</p></td></tr>
		<tr><th scope="row"><label for="gm-amount">Discount</label></th>
			<td><input type="number" id="gm-amount" name="gm_amount" min="0" step="0.01" value="<?php echo esc_attr( $m( 'amount' ) ); ?>" style="width:7em" required>
				<select name="gm_type"><option value="percent" <?php selected( $m( 'type', 'percent' ), 'percent' ); ?>>% off</option><option value="fixed" <?php selected( $m( 'type' ), 'fixed' ); ?>>£ off</option></select>
				<p class="description">Taken off the food total (delivery is charged as normal).</p></td></tr>
		<tr><th scope="row"><label for="gm-min">Minimum spend</label></th>
			<td>£ <input type="number" id="gm-min" name="gm_min_spend" min="0" step="0.01" value="<?php echo esc_attr( $m( 'min_spend', '0' ) ); ?>" style="width:7em">
			<p class="description">Food total needed before it applies. For automatic discounts the basket tells customers how much more to add.</p></td></tr>
		<tr><th scope="row"><label for="gm-expires">Ends on</label></th>
			<td><input type="date" id="gm-expires" name="gm_expires" value="<?php echo esc_attr( $m( 'expires' ) ); ?>"> <span class="description">Optional — last day it can be used.</span></td></tr>
		<tr class="gm-code-only"><th scope="row"><label for="gm-limit">Use limit</label></th>
			<td><input type="number" id="gm-limit" name="gm_limit" min="0" step="1" value="<?php echo esc_attr( $m( 'limit', '0' ) ); ?>" style="width:7em"> <span class="description">Total orders that can use it (0 = unlimited). Used so far: <strong><?php echo (int) $m( 'used', 0 ); ?></strong></span></td></tr>
	</table>
	<p><strong>Publish</strong> to switch the discount on; switch it to <strong>Draft</strong> to turn it off. Whether codes can be added on top of automatic discounts is set in <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gm_discount&page=gm-discount-settings' ) ); ?>">Discounts → Settings</a>.</p>
	<?php
}

add_action( 'save_post_gm_discount', function ( $id ) {
	if ( ! isset( $_POST['gm_discount_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gm_discount_nonce'] ), 'gm_discount_save' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$in   = wp_unslash( $_POST );
	$kind = 'auto' === ( $in['gm_kind'] ?? '' ) ? 'auto' : 'code';
	$type = 'fixed' === ( $in['gm_type'] ?? '' ) ? 'fixed' : 'percent';
	$amt  = max( 0, round( (float) ( $in['gm_amount'] ?? 0 ), 2 ) );
	if ( 'percent' === $type ) {
		$amt = min( 100, $amt );
	}
	update_post_meta( $id, '_gm_kind', $kind );
	update_post_meta( $id, '_gm_type', $type );
	update_post_meta( $id, '_gm_amount', $amt );
	update_post_meta( $id, '_gm_code', strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $in['gm_code'] ?? '' ) ) ) );
	update_post_meta( $id, '_gm_min_spend', max( 0, round( (float) ( $in['gm_min_spend'] ?? 0 ), 2 ) ) );
	update_post_meta( $id, '_gm_limit', max( 0, (int) ( $in['gm_limit'] ?? 0 ) ) );
	$exp = (string) ( $in['gm_expires'] ?? '' );
	update_post_meta( $id, '_gm_expires', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $exp ) ? $exp : '' );
	do_action( 'litespeed_purge_all' );
} );

/* Admin: list */
add_filter( 'manage_gm_discount_posts_columns', function () {
	return array(
		'cb'          => '<input type="checkbox">',
		'title'       => 'Discount',
		'gm_kind'     => 'Type',
		'gm_offer'    => 'Discount',
		'gm_min'      => 'Min. spend',
		'gm_used'     => 'Used',
		'gm_dstatus'  => 'Status',
	);
} );

add_action( 'manage_gm_discount_posts_custom_column', function ( $col, $id ) {
	$r = gm_discount_rule( $id );
	switch ( $col ) {
		case 'gm_kind':
			echo 'auto' === $r['kind'] ? 'Automatic' : 'Code <code>' . esc_html( $r['code'] ) . '</code>';
			break;
		case 'gm_offer':
			echo esc_html( gm_discount_offer( $r ) );
			break;
		case 'gm_min':
			echo $r['min'] ? esc_html( gm_money( $r['min'] ) ) : '—';
			break;
		case 'gm_used':
			echo (int) $r['used'] . ( $r['limit'] ? ' / ' . (int) $r['limit'] : '' );
			break;
		case 'gm_dstatus':
			$today = gm_now()->format( 'Y-m-d' );
			if ( ! $r['active'] ) {
				echo 'Off (draft)';
			} elseif ( $r['expires'] && $r['expires'] < $today ) {
				echo 'Expired';
			} elseif ( $r['limit'] && $r['used'] >= $r['limit'] ) {
				echo 'Used up';
			} else {
				echo '<strong style="color:#008a20">Live</strong>' . ( $r['expires'] ? ' until ' . esc_html( date_i18n( 'j M Y', strtotime( $r['expires'] ) ) ) : '' );
			}
			break;
	}
}, 10, 2 );

/* Admin: settings */
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=gm_discount', 'Discount settings', 'Settings', 'manage_options', 'gm-discount-settings', function () {
		if ( isset( $_POST['gm_discount_settings_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['gm_discount_settings_nonce'] ), 'gm_discount_settings' ) ) {
			update_option( 'gm_discount_stack', empty( $_POST['gm_discount_stack'] ) ? 0 : 1 );
			do_action( 'litespeed_purge_all' );
			echo '<div class="notice notice-success"><p>Saved.</p></div>';
		}
		?>
		<div class="wrap">
			<h1>Discount settings</h1>
			<form method="post">
				<?php wp_nonce_field( 'gm_discount_settings', 'gm_discount_settings_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row">Codes on top of automatic discounts</th>
						<td><label><input type="checkbox" name="gm_discount_stack" value="1" <?php checked( gm_discount_stacking() ); ?>> Allow a discount code to be used <strong>as well as</strong> an automatic discount</label>
						<p class="description">When this is off and both apply, the customer gets whichever saves them more.</p></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	} );
} );
