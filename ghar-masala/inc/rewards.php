<?php
/**
 * Rewards: a thank-you code for first-time reviewers, and the loyalty
 * programme for registered customers.
 *
 *   Review thank-you  – Discounts → Settings. When you approve a customer's
 *                       first review, they're emailed a one-use % code that
 *                       works on top of any other discount.
 *   Loyalty           – Customers → Loyalty programme. Every order with a food
 *                       total of £20+ (after other discounts) earns a stamp;
 *                       the 10th order is 50% off. Customers can use it or
 *                       save it for a later order. Progress shows in My account and in
 *                       Customers, where you can add or remove stamps.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 50 → "50", 12.5 → "12.5". */
function gm_num( $n ) {
	return rtrim( rtrim( number_format( (float) $n, 2, '.', '' ), '0' ), '.' );
}

/* ===========================================================================
 * Settings
 * ======================================================================== */

function gm_review_reward() {
	return wp_parse_args(
		(array) get_option( 'gm_review_reward', array() ),
		array(
			'enabled' => 1,
			'percent' => 10,
			'days'    => 60, // How long the code lasts.
		)
	);
}

function gm_loyalty() {
	return wp_parse_args(
		(array) get_option( 'gm_loyalty', array() ),
		array(
			'enabled'    => 1,
			'orders'     => 10,  // Stamps needed.
			'min'        => 20,  // £ food total (after discounts) for an order to earn a stamp.
			'percent'    => 50,
			'cap'        => 0,   // £ maximum saving, 0 = no limit.
			'promo_home' => 1,
			'promo_menu' => 1,
			'promo_title' => 'Your 10th order is 50% off',
			'promo_text'  => 'Create a free Ghar Masala account and every order of £20 or more earns a stamp — your 10th order is 50% off. Rather save it for a bigger order? You can.',
		)
	);
}

/* ===========================================================================
 * Review thank-you codes
 * ======================================================================== */

/** Has this email already been sent a review thank-you code? */
function gm_reviewer_rewarded( $email ) {
	$sent = (array) get_option( 'gm_reward_emails', array() );
	return in_array( strtolower( $email ), $sent, true );
}

/** On approval of a customer's first review, create and email their code. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'gm_testimonial' !== $post->post_type || 'publish' !== $new || 'publish' === $old ) {
		return;
	}
	$r     = gm_review_reward();
	$email = strtolower( (string) get_post_meta( $post->ID, '_gm_email', true ) );
	if ( ! $r['enabled'] || ! is_email( $email ) || gm_reviewer_rewarded( $email ) || get_post_meta( $post->ID, '_gm_reward_code', true ) ) {
		return;
	}

	$code = 'THANKS-' . strtoupper( wp_generate_password( 6, false, false ) );
	$id   = wp_insert_post(
		array(
			'post_type'   => 'gm_discount',
			'post_status' => 'publish',
			'post_title'  => 'Thank-you for your review — ' . gm_num( (float) $r['percent'] ) . '% off',
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return;
	}
	$meta = array(
		'kind'         => 'code',
		'code'         => $code,
		'type'         => 'percent',
		'amount'       => (float) $r['percent'],
		'min_spend'    => 0,
		'limit'        => 1,
		'expires'      => gm_now()->modify( '+' . (int) $r['days'] . ' days' )->format( 'Y-m-d' ),
		'always_stack' => 1,
		'reward_email' => $email,
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, '_gm_' . $k, $v );
	}
	update_post_meta( $post->ID, '_gm_reward_code', $code );
	$sent   = (array) get_option( 'gm_reward_emails', array() );
	$sent[] = $email;
	update_option( 'gm_reward_emails', array_values( array_unique( $sent ) ), false );

	$name = trim( strtok( $post->post_title, ',' ) );
	wp_mail(
		$email,
		'Thank you for your review — here’s ' . (float) $r['percent'] . '% off',
		"Hi {$name},\n\nThank you for taking the time to review Ghar Masala — your review is now live on our website.\n\n"
			. 'As a thank-you, here is ' . (float) $r['percent'] . "% off your next order, on top of any other offers:\n\n    {$code}\n\n"
			. 'Use it at checkout by ' . date_i18n( 'j F Y', strtotime( $meta['expires'] ) ) . ". It can be used once.\n\n"
			. gm_view_url( 'menu' ) . "\n\nGhar Masala — tradition served with comfort\n"
	);
}, 10, 3 );

/* ===========================================================================
 * Loyalty
 * ======================================================================== */

/**
 * A customer's loyalty card.
 *
 * @return array { stamps, needed, ready, adjust, history: [ [ ref, date, earned|used|small ] ] }
 */
function gm_loyalty_status( $user_id ) {
	$l      = gm_loyalty();
	$needed = max( 1, (int) $l['orders'] );
	$min    = (int) round( (float) $l['min'] * 100 );
	$ids    = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => array(
				array( 'key' => '_gm_user', 'value' => (int) $user_id ),
				array( 'key' => '_gm_status', 'value' => 'confirmed' ),
			),
		)
	);
	// Orders and hand-added stamps, oldest first, so a reward used later
	// resets the card whatever mix of the two filled it.
	$events = array();
	foreach ( $ids as $id ) {
		$events[] = array( (int) get_post_time( 'U', true, $id ), 'order', $id );
	}
	$adjust = 0;
	foreach ( gm_loyalty_adjustments( $user_id ) as $a ) {
		$events[] = array( (int) $a[0], 'adjust', (int) $a[1] );
		$adjust  += (int) $a[1];
	}
	usort( $events, function ( $x, $y ) {
		return $x[0] - $y[0];
	} );
	$stamps  = 0;
	$history = array();
	foreach ( $events as $e ) {
		if ( 'adjust' === $e[1] ) {
			$stamps = max( 0, $stamps + $e[2] );
			continue;
		}
		$id   = $e[2];
		$sub  = get_post_meta( $id, '_gm_subtotal', true );
		$sub  = '' === $sub ? (int) get_post_meta( $id, '_gm_total', true ) : (int) $sub;
		$food = $sub - (int) get_post_meta( $id, '_gm_discount_pence', true ); // before the loyalty reward
		if ( get_post_meta( $id, '_gm_loyalty_used', true ) ) {
			// The reward order counts as the last stamp on the card (if it qualifies), then the card starts again.
			$stamps    = max( 0, $stamps + ( $food >= $min ? 1 : 0 ) - $needed );
			$history[] = array( gm_order_ref( $id ), get_the_date( 'j M Y', $id ), 'used' );
			continue;
		}
		if ( $food >= $min ) {
			$stamps++;
			$history[] = array( gm_order_ref( $id ), get_the_date( 'j M Y', $id ), 'earned' );
		} else {
			$history[] = array( gm_order_ref( $id ), get_the_date( 'j M Y', $id ), 'small' );
		}
	}
	return array(
		'stamps'  => $stamps,
		'needed'  => $needed,
		'ready'   => $l['enabled'] && $stamps >= $needed - 1, // the next qualifying order is the reward order
		'full'    => $l['enabled'] && $stamps >= $needed,     // saved: any order can use it
		'adjust'  => $adjust,
		'history' => array_reverse( $history ),
	);
}

/** Stamps added or removed by hand in Customers: [ [ timestamp, ±1 ], … ]. */
function gm_loyalty_adjustments( $user_id ) {
	$log = get_user_meta( $user_id, '_gm_loyalty_log', true );
	if ( is_array( $log ) ) {
		return array_filter( $log, 'is_array' );
	}
	$old = (int) get_user_meta( $user_id, '_gm_loyalty_adjust', true ); // before the log existed
	return $old ? array( array( 0, $old ) ) : array();
}

function gm_loyalty_add_stamps( $user_id, $delta ) {
	$log   = gm_loyalty_adjustments( $user_id );
	$log[] = array( time(), (int) $delta );
	update_user_meta( $user_id, '_gm_loyalty_log', array_values( $log ) );
	update_user_meta( $user_id, '_gm_loyalty_adjust', array_sum( array_column( $log, 1 ) ) );
}

/** Loyalty details for the page's JavaScript (the card only for signed-in customers). */
function gm_loyalty_config() {
	$l = gm_loyalty();
	if ( ! $l['enabled'] ) {
		return null;
	}
	$out = array(
		'percent' => (float) $l['percent'],
		'cap'     => (int) round( (float) $l['cap'] * 100 ),
		'label'   => gm_loyalty_label(),
		'min'     => (int) round( (float) $l['min'] * 100 ),
		'ready'   => false,
		'full'    => false,
	);
	if ( is_user_logged_in() ) {
		$st           = gm_loyalty_status( get_current_user_id() );
		$out['ready'] = $st['ready'];
		$out['full']  = $st['full'];
	}
	return $out;
}

/** Can this customer use the reward on an order with this food total (pence, after other discounts)? */
function gm_loyalty_can_use( $user_id, $food ) {
	$st = gm_loyalty_status( $user_id );
	return $st['full'] || ( $st['ready'] && $food >= (int) round( (float) gm_loyalty()['min'] * 100 ) );
}

/** The loyalty saving on a food total (pence), after other discounts. */
function gm_loyalty_value( $food ) {
	$l = gm_loyalty();
	$v = (int) round( $food * (float) $l['percent'] / 100 );
	if ( (float) $l['cap'] > 0 ) {
		$v = min( $v, (int) round( (float) $l['cap'] * 100 ) );
	}
	return max( 0, min( $v, $food ) );
}

function gm_ordinal_suffix( $n ) {
	$n = (int) $n;
	if ( in_array( $n % 100, array( 11, 12, 13 ), true ) ) {
		return 'th';
	}
	return array( 'th', 'st', 'nd', 'rd' )[ $n % 10 ] ?? 'th';
}

function gm_loyalty_label() {
	$l = gm_loyalty();
	return 'Loyalty reward: ' . gm_num( (float) $l['percent'] ) . '% off';
}

/** My account: the stamp card with a progress bar. */
function gm_render_loyalty_card( $user_id ) {
	$l = gm_loyalty();
	if ( ! $l['enabled'] ) {
		return;
	}
	$st     = gm_loyalty_status( $user_id );
	$needed = $st['needed'];
	$have   = min( $st['stamps'], $needed );
	$pct    = gm_num( $l['percent'] );
	$labels = array(
		'earned' => 'Stamp earned',
		'used'   => $pct . '% reward used',
		'small'  => 'Under £' . gm_num( $l['min'] ) . ' — no stamp',
	);
	?>
	<section class="gm-loyalty<?php echo $st['ready'] ? ' is-ready' : ''; ?>" aria-labelledby="gm-loyalty-title">
		<div class="gm-loyalty__head">
			<h2 class="gm-label" id="gm-loyalty-title">Loyalty stamps</h2>
			<p class="gm-loyalty__count gm-tnum"><strong><?php echo (int) $have; ?></strong> / <?php echo (int) $needed; ?></p>
		</div>
		<div class="gm-loyalty__bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo (int) $needed; ?>" aria-valuenow="<?php echo (int) $have; ?>" aria-label="Loyalty progress">
			<span style="width:<?php echo (int) round( $have / $needed * 100 ); ?>%"></span>
		</div>
		<?php if ( $needed <= 20 ) : ?>
			<ol class="gm-loyalty__stamps" aria-hidden="true">
				<?php for ( $i = 1; $i <= $needed; $i++ ) : ?>
					<li class="<?php echo $i <= $have ? 'is-on' : ''; ?><?php echo $i === $needed ? ' is-prize' : ''; ?><?php echo $i === $needed && $st['ready'] ? ' is-next' : ''; ?>"><?php echo $i === $needed ? esc_html( $pct . '%' ) : ( $i <= $have ? '✓' : (int) $i ); ?></li>
				<?php endfor; ?>
			</ol>
		<?php endif; ?>
		<?php if ( $st['full'] ) : ?>
			<p class="gm-loyalty__msg"><strong>You’ve saved a <?php echo esc_html( $pct ); ?>% reward!</strong> It’s offered at checkout on any order — use it whenever you like. It won’t expire.<?php echo $st['stamps'] > $needed ? esc_html( sprintf( ' You also have %d stamp%s towards your next card.', $st['stamps'] - $needed, 1 === $st['stamps'] - $needed ? '' : 's' ) ) : ''; ?></p>
			<a class="gm-btn" href="#menu">Order with my reward</a>
		<?php elseif ( $st['ready'] ) : ?>
			<p class="gm-loyalty__msg"><strong>Your next order is your <?php echo esc_html( $needed . gm_ordinal_suffix( $needed ) ); ?> — and it’s <?php echo esc_html( $pct ); ?>% off!</strong> It applies at checkout to any order of £<?php echo esc_html( gm_num( $l['min'] ) ); ?> or more. Rather save it for a bigger order? Just untick it at checkout.</p>
			<a class="gm-btn" href="#menu">Order my <?php echo esc_html( $pct ); ?>% off meal</a>
		<?php else : ?>
			<p class="gm-loyalty__msg"><?php echo esc_html( sprintf( '%d more order%s of £%s or more, then your %s order is %s%% off.', $needed - 1 - $have, 1 === $needed - 1 - $have ? '' : 's', gm_num( $l['min'] ), $needed . gm_ordinal_suffix( $needed ), $pct ) ); ?></p>
		<?php endif; ?>
		<?php if ( $st['history'] ) : ?>
			<details class="gm-loyalty__history">
				<summary>How you earned them</summary>
				<ul>
					<?php foreach ( array_slice( $st['history'], 0, 20 ) as $h ) : ?>
						<li><span class="gm-tnum"><?php echo esc_html( $h[1] . ' · ' . $h[0] ); ?></span><span class="gm-loyalty__tag gm-loyalty__tag--<?php echo esc_attr( $h[2] ); ?>"><?php echo esc_html( $labels[ $h[2] ] ); ?></span></li>
					<?php endforeach; ?>
					<?php if ( $st['adjust'] ) : ?><li><span>Added by Ghar Masala</span><span class="gm-loyalty__tag gm-loyalty__tag--earned"><?php echo esc_html( ( $st['adjust'] > 0 ? '+' : '' ) . $st['adjust'] . ' stamp' . ( 1 === abs( $st['adjust'] ) ? '' : 's' ) ); ?></span></li><?php endif; ?>
				</ul>
			</details>
		<?php endif; ?>
	</section>
	<?php
}

/** "Order 10 times, get 50% off" advert for the site. */
function gm_render_loyalty_promo( $where ) {
	$l = gm_loyalty();
	if ( ! $l['enabled'] || ( 'login' !== $where && empty( $l[ 'promo_' . $where ] ) ) ) {
		return;
	}
	$signed_in = is_user_logged_in();
	$needed    = max( 1, (int) $l['orders'] );
	?>
	<aside class="gm-loyalty-promo gm-loyalty-promo--<?php echo esc_attr( $where ); ?>">
		<div class="gm-loyalty-promo__stamps" aria-hidden="true">
			<?php for ( $i = 1; $i <= min( $needed, 10 ); $i++ ) : ?><span class="<?php echo $i === min( $needed, 10 ) ? 'is-prize' : ''; ?>"><?php echo $i === min( $needed, 10 ) ? esc_html( (float) $l['percent'] . '%' ) : ''; ?></span><?php endfor; ?>
		</div>
		<div class="gm-loyalty-promo__copy">
			<p class="gm-loyalty-promo__title"><?php echo esc_html( $l['promo_title'] ); ?></p>
			<p class="gm-loyalty-promo__text"><?php echo esc_html( $l['promo_text'] ); ?></p>
		</div>
		<a class="gm-btn gm-loyalty-promo__btn" href="<?php echo esc_url( gm_view_url( $signed_in ? 'account' : 'register' ) ); ?>"><?php echo $signed_in ? 'See my stamps' : 'Create a free account'; ?></a>
	</aside>
	<?php
}

/* ---------------------------------------------------------------------------
 * Admin: Customers → Loyalty programme, and Discounts → Settings (review reward)
 * ------------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'gm-customers', 'Customers', 'All customers', 'list_users', 'gm-customers', 'gm_render_customers_page' );
	add_submenu_page( 'gm-customers', 'Loyalty programme', 'Loyalty programme', 'manage_options', 'gm-loyalty', 'gm_render_loyalty_settings' );
}, 20 );

function gm_render_loyalty_settings() {
	if ( isset( $_POST['gm_loyalty_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['gm_loyalty_nonce'] ), 'gm_loyalty' ) && current_user_can( 'manage_options' ) ) {
		$in = wp_unslash( $_POST );
		update_option(
			'gm_loyalty',
			array(
				'enabled'     => empty( $in['enabled'] ) ? 0 : 1,
				'orders'      => max( 1, min( 50, (int) ( $in['orders'] ?? 10 ) ) ),
				'min'         => max( 0, round( (float) ( $in['min'] ?? 20 ), 2 ) ),
				'percent'     => max( 1, min( 100, round( (float) ( $in['percent'] ?? 50 ), 2 ) ) ),
				'cap'         => max( 0, round( (float) ( $in['cap'] ?? 0 ), 2 ) ),
				'promo_home'  => empty( $in['promo_home'] ) ? 0 : 1,
				'promo_menu'  => empty( $in['promo_menu'] ) ? 0 : 1,
				'promo_title' => sanitize_text_field( $in['promo_title'] ?? '' ),
				'promo_text'  => sanitize_textarea_field( $in['promo_text'] ?? '' ),
			)
		);
		do_action( 'litespeed_purge_all' );
		echo '<div class="notice notice-success"><p>Saved.</p></div>';
	}
	$l = gm_loyalty();
	?>
	<div class="wrap">
		<h1>Loyalty programme</h1>
		<p>Registered customers collect a stamp for every qualifying order. The order that completes the card gets the discount (e.g. the 10th order is 50% off), as long as it meets the minimum spend. Customers can untick it at checkout to save it, and then use it on any later order. Progress shows on their <strong>My account</strong> page and in <a href="<?php echo esc_url( admin_url( 'admin.php?page=gm-customers' ) ); ?>">Customers</a>.</p>
		<form method="post">
			<?php wp_nonce_field( 'gm_loyalty', 'gm_loyalty_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Programme</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $l['enabled'] ); ?>> Switch the loyalty programme on</label></td></tr>
				<tr><th scope="row"><label for="gm-l-orders">Orders needed</label></th><td><input type="number" id="gm-l-orders" name="orders" min="1" max="50" value="<?php echo (int) $l['orders']; ?>" style="width:6em"> <span class="description">the last one is the reward order, e.g. 10 = the 10th order gets the discount</span></td></tr>
				<tr><th scope="row"><label for="gm-l-min">Order must be at least</label></th><td>£ <input type="number" id="gm-l-min" name="min" min="0" step="0.01" value="<?php echo esc_attr( $l['min'] ); ?>" style="width:7em"> <span class="description">food total after any discounts, to earn a stamp</span></td></tr>
				<tr><th scope="row"><label for="gm-l-pct">Reward</label></th><td><input type="number" id="gm-l-pct" name="percent" min="1" max="100" step="0.5" value="<?php echo esc_attr( $l['percent'] ); ?>" style="width:6em"> % off the food total of the order they use it on</td></tr>
				<tr><th scope="row"><label for="gm-l-cap">Biggest saving</label></th><td>£ <input type="number" id="gm-l-cap" name="cap" min="0" step="0.01" value="<?php echo esc_attr( $l['cap'] ); ?>" style="width:7em"> <span class="description">optional limit on the reward, 0 = no limit</span></td></tr>
			</table>
			<h2>Advert on the website</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Show it</th><td>
					<label style="display:block"><input type="checkbox" name="promo_home" value="1" <?php checked( $l['promo_home'] ); ?>> Home page (next to the delivery checker)</label>
					<label style="display:block"><input type="checkbox" name="promo_menu" value="1" <?php checked( $l['promo_menu'] ); ?>> Menu page (next to the delivery checker)</label>
					<p class="description">It always appears on the Sign in / Create account page while the programme is on.</p></td></tr>
				<tr><th scope="row"><label for="gm-l-title">Headline</label></th><td><input type="text" id="gm-l-title" name="promo_title" value="<?php echo esc_attr( $l['promo_title'] ); ?>" class="regular-text"></td></tr>
				<tr><th scope="row"><label for="gm-l-text">Text</label></th><td><textarea id="gm-l-text" name="promo_text" rows="3" class="large-text"><?php echo esc_textarea( $l['promo_text'] ); ?></textarea>
					<p class="description">If you change the numbers above, update this text to match.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* Customers list: +1 / −1 stamp */
add_action( 'admin_post_gm_loyalty_adjust', function () {
	$id    = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
	$delta = isset( $_GET['delta'] ) && '-1' === $_GET['delta'] ? -1 : 1;
	check_admin_referer( 'gm_loyalty_adjust_' . $id );
	if ( current_user_can( 'edit_user', $id ) ) {
		gm_loyalty_add_stamps( $id, $delta );
	}
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=gm-customers' ) );
	exit;
} );

/* Review reward settings live on Discounts → Settings. */
add_action( 'gm_discount_settings_fields', function () {
	$r = gm_review_reward();
	?>
	<tr><th scope="row">Review thank-you code</th>
		<td><label><input type="checkbox" name="gm_rr_enabled" value="1" <?php checked( $r['enabled'] ); ?>> Email first-time reviewers a thank-you code when you approve their review</label>
		<p style="margin-top:8px"><input type="number" name="gm_rr_percent" min="1" max="100" step="0.5" value="<?php echo esc_attr( $r['percent'] ); ?>" style="width:6em"> % off, valid for <input type="number" name="gm_rr_days" min="1" max="365" value="<?php echo (int) $r['days']; ?>" style="width:6em"> days, one use</p>
		<p class="description">Works on top of any other discounts. One code per email address. Customers need to give their email on the review form to receive it.</p></td></tr>
	<?php
} );

add_action( 'gm_discount_settings_save', function ( $in ) {
	update_option(
		'gm_review_reward',
		array(
			'enabled' => empty( $in['gm_rr_enabled'] ) ? 0 : 1,
			'percent' => max( 1, min( 100, round( (float) ( $in['gm_rr_percent'] ?? 10 ), 2 ) ) ),
			'days'    => max( 1, min( 365, (int) ( $in['gm_rr_days'] ?? 60 ) ) ),
		)
	);
} );
