<?php
/**
 * Settings → Ghar Masala: contact details, order emails and payments.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_setting_defaults() {
	return array(
		'phone'          => '07497 601572',
		'email'          => 'ghar.masala@gmail.com',
		'order_email'    => '',
		'days_ahead'     => 7,
		'delivery_from'  => 'B69 1NY',
		'delivery_area'  => 'Tividale Viewpoint B69',
		'free_miles'     => 2,
		'per_mile'       => 1.00,
		'max_miles'      => 3,
		'cod_enabled'    => 1,
		'stripe_secret'  => '',
		'stripe_webhook' => '',
	);
}

function gm_setting( $key ) {
	$opts     = get_option( 'gm_settings', array() );
	$defaults = gm_setting_defaults();
	$value    = isset( $opts[ $key ] ) ? $opts[ $key ] : $defaults[ $key ];
	if ( 'order_email' === $key && ! $value ) {
		$value = get_option( 'admin_email' );
	}
	return $value;
}

/** International form of the phone number for tel: and wa.me links, e.g. 447497601572. */
function gm_phone_intl() {
	$digits = preg_replace( '/\D+/', '', gm_setting( 'phone' ) );
	if ( 0 === strpos( $digits, '0' ) ) {
		$digits = '44' . substr( $digits, 1 );
	}
	return $digits;
}

function gm_stripe_enabled() {
	return '' !== trim( (string) gm_setting( 'stripe_secret' ) );
}

function gm_cod_enabled() {
	// Without card payments, pay-on-delivery is the only way to take an order.
	return ! gm_stripe_enabled() || (bool) gm_setting( 'cod_enabled' );
}

add_action( 'admin_menu', function () {
	add_options_page( 'Ghar Masala', 'Ghar Masala', 'manage_options', 'ghar-masala', 'gm_render_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting(
		'gm_settings',
		'gm_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'gm_sanitize_settings',
			'default'           => gm_setting_defaults(),
		)
	);
} );

function gm_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	return array(
		'phone'          => sanitize_text_field( $input['phone'] ?? '' ),
		'email'          => sanitize_email( $input['email'] ?? '' ),
		'order_email'    => sanitize_email( $input['order_email'] ?? '' ),
		'days_ahead'     => max( 1, min( 14, (int) ( $input['days_ahead'] ?? 7 ) ) ),
		'delivery_from'  => gm_postcode_key( $input['delivery_from'] ?? '' ) ? strtoupper( sanitize_text_field( $input['delivery_from'] ) ) : 'B69 1NY',
		'delivery_area'  => sanitize_text_field( $input['delivery_area'] ?? '' ),
		'free_miles'     => max( 0, round( (float) ( $input['free_miles'] ?? 2 ), 1 ) ),
		'per_mile'       => max( 0, round( (float) ( $input['per_mile'] ?? 1 ), 2 ) ),
		'max_miles'      => max( 0.5, round( (float) ( $input['max_miles'] ?? 3 ), 1 ) ),
		'cod_enabled'    => empty( $input['cod_enabled'] ) ? 0 : 1,
		'stripe_secret'  => sanitize_text_field( $input['stripe_secret'] ?? '' ),
		'stripe_webhook' => sanitize_text_field( $input['stripe_webhook'] ?? '' ),
	);
}

function gm_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o       = wp_parse_args( get_option( 'gm_settings', array() ), gm_setting_defaults() );
	$webhook = rest_url( 'ghar-masala/v1/stripe-webhook' );
	?>
	<div class="wrap">
		<h1>Ghar Masala</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'gm_settings' ); ?>
			<h2>Contact</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="gm-phone">Kitchen phone</label></th>
					<td><input class="regular-text" id="gm-phone" name="gm_settings[phone]" value="<?php echo esc_attr( $o['phone'] ); ?>">
					<p class="description">Shown on the site and used for the WhatsApp button.</p></td></tr>
				<tr><th scope="row"><label for="gm-email">Public email</label></th>
					<td><input class="regular-text" type="email" id="gm-email" name="gm_settings[email]" value="<?php echo esc_attr( $o['email'] ); ?>"></td></tr>
			</table>

			<h2>Orders</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="gm-order-email">Send new orders to</label></th>
					<td><input class="regular-text" type="email" id="gm-order-email" name="gm_settings[order_email]" value="<?php echo esc_attr( $o['order_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<p class="description">Leave blank to use the site admin email.</p></td></tr>
				<tr><th scope="row"><label for="gm-days">Days bookable ahead</label></th>
					<td><input type="number" min="1" max="14" id="gm-days" name="gm_settings[days_ahead]" value="<?php echo esc_attr( $o['days_ahead'] ); ?>"></td></tr>
				<tr><th scope="row">Pay on delivery</th>
					<td><label><input type="checkbox" name="gm_settings[cod_enabled]" value="1" <?php checked( $o['cod_enabled'], 1 ); ?>> Let customers pay on delivery (cash or card at the door)</label>
					<p class="description">Always on while no Stripe key is set below.</p></td></tr>
			</table>

			<h2>Delivery charge</h2>
			<p>Worked out at checkout from the customer's postcode, as the crow flies from the kitchen.</p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="gm-from">Kitchen postcode</label></th>
					<td><input class="regular-text" id="gm-from" name="gm_settings[delivery_from]" value="<?php echo esc_attr( $o['delivery_from'] ); ?>">
					<p class="description">Distances are measured from here.</p></td></tr>
				<tr><th scope="row"><label for="gm-area">Shown to customers as</label></th>
					<td><input class="regular-text" id="gm-area" name="gm_settings[delivery_area]" value="<?php echo esc_attr( $o['delivery_area'] ); ?>">
					<p class="description">e.g. “We deliver up to 3 miles from Tividale Viewpoint B69”. Leave blank to show the postcode.</p></td></tr>
				<tr><th scope="row"><label for="gm-free">Free delivery up to</label></th>
					<td><input type="number" min="0" step="0.1" id="gm-free" name="gm_settings[free_miles]" value="<?php echo esc_attr( $o['free_miles'] ); ?>" style="width:6em"> miles</td></tr>
				<tr><th scope="row"><label for="gm-mile">Then, per extra mile</label></th>
					<td>£ <input type="number" min="0" step="0.01" id="gm-mile" name="gm_settings[per_mile]" value="<?php echo esc_attr( number_format( (float) $o['per_mile'], 2, '.', '' ) ); ?>" style="width:6em">
					<p class="description">Charged for every mile started, e.g. 2.3 miles = 1 extra mile.</p></td></tr>
				<tr><th scope="row"><label for="gm-max">Furthest delivery</label></th>
					<td><input type="number" min="0.5" step="0.1" id="gm-max" name="gm_settings[max_miles]" value="<?php echo esc_attr( $o['max_miles'] ); ?>" style="width:6em"> miles
					<p class="description">Postcodes further away than this can't check out.</p></td></tr>
			</table>

			<h2 id="gm-card">Card payments (Stripe)</h2>
			<p>Paste your Stripe keys to take card payments online. Customers are sent to Stripe's secure checkout page — card numbers never touch this website.</p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="gm-sk">Secret key</label></th>
					<td><input class="regular-text code" type="password" autocomplete="off" id="gm-sk" name="gm_settings[stripe_secret]" value="<?php echo esc_attr( $o['stripe_secret'] ); ?>" placeholder="sk_live_…">
					<p class="description">Stripe Dashboard → Developers → API keys. Use an <code>sk_test_…</code> key first to try it out.</p></td></tr>
				<tr><th scope="row"><label for="gm-wh">Webhook signing secret</label></th>
					<td><input class="regular-text code" type="password" autocomplete="off" id="gm-wh" name="gm_settings[stripe_webhook]" value="<?php echo esc_attr( $o['stripe_webhook'] ); ?>" placeholder="whsec_…">
					<p class="description">Recommended. In Stripe add a webhook endpoint for <code><?php echo esc_html( $webhook ); ?></code> with the event <code>checkout.session.completed</code>, then paste its signing secret here. It confirms orders even if a customer closes the tab right after paying.</p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/** Remind the owner on the dashboard that card payments need a Stripe key. */
add_action( 'admin_notices', function () {
	if ( gm_stripe_enabled() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && ! in_array( $screen->id, array( 'dashboard', 'edit-gm_order', 'settings_page_ghar-masala' ), true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p><strong>Ghar Masala:</strong> the “Pay by card” option is hidden until you add your Stripe secret key. <a href="%s">Add it in Settings → Ghar Masala</a>.</p></div>',
		esc_url( admin_url( 'options-general.php?page=ghar-masala#gm-card' ) )
	);
} );
