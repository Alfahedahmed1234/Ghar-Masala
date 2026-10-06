<?php
/**
 * WP Admin → Customers: every customer account with their orders and spend,
 * and quick actions (view orders, edit details, send a password reset, delete).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_menu_page( 'Customers', 'Customers', 'list_users', 'gm-customers', 'gm_render_customers_page', 'dashicons-groups', 4 );
} );

/** Orders and spend for a customer: [ count, spent (pence), last order date ]. */
function gm_customer_stats( $user_id ) {
	$ids   = get_posts(
		array(
			'post_type'      => 'gm_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_gm_user', 'value' => (int) $user_id ),
				array( 'key' => '_gm_status', 'value' => 'confirmed' ),
			),
		)
	);
	$spent = 0;
	$last  = '';
	foreach ( $ids as $id ) {
		$spent += (int) get_post_meta( $id, '_gm_total', true );
		$date   = get_post_field( 'post_date', $id );
		$last   = max( $last, $date );
	}
	return array( count( $ids ), $spent, $last );
}

function gm_render_customers_page() {
	if ( ! current_user_can( 'list_users' ) ) {
		return;
	}
	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$paged  = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
	$query  = new WP_User_Query(
		array(
			'role__not_in'   => array( 'administrator', 'editor', 'author', 'shop_manager' ),
			'search'         => $search ? '*' . $search . '*' : '',
			'search_columns' => array( 'user_email', 'display_name', 'user_login' ),
			'number'         => 50,
			'paged'          => $paged,
			'orderby'        => 'registered',
			'order'          => 'DESC',
		)
	);
	$total = (int) $query->get_total();
	$pages = (int) ceil( $total / 50 );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Customers</h1>
		<span class="subtitle"><?php echo (int) $total; ?> account<?php echo 1 === $total ? '' : 's'; ?></span>
		<hr class="wp-header-end">
		<?php
		$notice = isset( $_GET['gm_done'] ) ? sanitize_key( $_GET['gm_done'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$msgs   = array(
			'reset'   => 'Password reset email sent.',
			'deleted' => 'Account deleted. Their past orders are kept in Orders.',
			'failed'  => 'Something went wrong — please try again.',
		);
		if ( isset( $msgs[ $notice ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'failed' === $notice ? 'error' : 'success', esc_html( $msgs[ $notice ] ) );
		}
		?>
		<form method="get" style="margin:12px 0">
			<input type="hidden" name="page" value="gm-customers">
			<p class="search-box"><label class="screen-reader-text" for="gm-cust-search">Search customers</label>
				<input type="search" id="gm-cust-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Name or email">
				<input type="submit" class="button" value="Search customers"></p>
		</form>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr>
				<th style="width:20%">Customer</th><th style="width:18%">Contact &amp; address</th><th>Joined</th><th>Orders</th><th>Spent</th><th>Last order</th><th style="width:15%">Loyalty</th><th style="width:22%">Actions</th>
			</tr></thead>
			<tbody>
			<?php if ( ! $query->get_results() ) : ?>
				<tr><td colspan="8"><?php echo $search ? 'No customers match that search.' : 'No customer accounts yet. Customers appear here when they create an account on the website.'; ?></td></tr>
			<?php endif; ?>
			<?php
			foreach ( $query->get_results() as $user ) :
				list( $count, $spent, $last ) = gm_customer_stats( $user->ID );
				$saved   = gm_saved_details( $user->ID );
				$act_url = function ( $action ) use ( $user ) {
					return wp_nonce_url( admin_url( 'admin-post.php?action=' . $action . '&user=' . $user->ID ), $action . '_' . $user->ID );
				};
				?>
				<tr>
					<td><strong><?php echo esc_html( $user->display_name ); ?></strong><br><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></td>
					<td><?php
					if ( $saved ) {
						echo esc_html( $saved['phone'] ) . '<br>' . esc_html( trim( $saved['address'] . ', ' . $saved['postcode'], ', ' ) );
					} else {
						echo '<span style="color:#787c82">No orders yet</span>';
					}
					?></td>
					<td><?php echo esc_html( date_i18n( 'j M Y', strtotime( $user->user_registered ) ) ); ?></td>
					<td><?php echo (int) $count; ?></td>
					<td><?php echo esc_html( gm_money( $spent ) ); ?></td>
					<td><?php echo $last ? esc_html( date_i18n( 'j M Y', strtotime( $last ) ) ) : '—'; ?></td>
					<td><?php
					if ( gm_loyalty()['enabled'] ) {
						$st  = gm_loyalty_status( $user->ID );
						$pct = min( 100, round( $st['stamps'] / $st['needed'] * 100 ) );
						printf( '<strong>%d / %d</strong> stamps%s', (int) min( $st['stamps'], 999 ), (int) $st['needed'], $st['ready'] ? ' <span style="background:#00a32a;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px">Reward ready</span>' : '' );
						printf( '<div style="height:6px;background:#dcdcde;margin:5px 0"><div style="height:6px;width:%d%%;background:#eda01e"></div></div>', (int) $pct );
						$adj = function ( $d ) use ( $user ) {
							return wp_nonce_url( admin_url( 'admin-post.php?action=gm_loyalty_adjust&user=' . $user->ID . '&delta=' . $d ), 'gm_loyalty_adjust_' . $user->ID );
						};
						printf( '<a href="%s" title="Take a stamp away">−1</a> · <a href="%s" title="Give a stamp">+1</a>%s', esc_url( $adj( '-1' ) ), esc_url( $adj( '1' ) ), $st['adjust'] ? ' <span style="color:#787c82">(adjusted ' . ( $st['adjust'] > 0 ? '+' : '' ) . (int) $st['adjust'] . ')</span>' : '' );
					} else {
						echo '<span style="color:#787c82">Off</span>';
					}
					?></td>
					<td>
						<?php if ( $count ) : ?><a class="button button-small" href="<?php echo esc_url( admin_url( 'edit.php?post_type=gm_order&gm_customer=' . $user->ID ) ); ?>">View orders</a><?php endif; ?>
						<a class="button button-small" href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">Edit details</a>
						<a class="button button-small" href="<?php echo esc_url( $act_url( 'gm_customer_reset' ) ); ?>" onclick="return confirm('Email <?php echo esc_js( $user->display_name ); ?> a link to set a new password?')">Send password reset</a>
						<a class="button button-small button-link-delete" href="<?php echo esc_url( $act_url( 'gm_customer_delete' ) ); ?>" onclick="return confirm('Delete the account for <?php echo esc_js( $user->display_name ); ?>? Their past orders stay in Orders. This cannot be undone.')">Delete</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $pages > 1 ) : ?>
			<p class="tablenav"><?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'current' => $paged,
						'total'   => $pages,
					)
				)
			);
			?></p>
		<?php endif; ?>
	</div>
	<?php
}

/* Actions */

add_action( 'admin_post_gm_customer_reset', function () {
	$id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
	check_admin_referer( 'gm_customer_reset_' . $id );
	$user = get_userdata( $id );
	$ok   = current_user_can( 'edit_user', $id ) && $user && gm_send_reset_email( $user );
	wp_safe_redirect( admin_url( 'admin.php?page=gm-customers&gm_done=' . ( $ok ? 'reset' : 'failed' ) ) );
	exit;
} );

add_action( 'admin_post_gm_customer_delete', function () {
	$id = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;
	check_admin_referer( 'gm_customer_delete_' . $id );
	$user = get_userdata( $id );
	$ok   = false;
	if ( $user && current_user_can( 'delete_user', $id ) && ! $user->has_cap( 'edit_posts' ) && get_current_user_id() !== $id ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$ok = wp_delete_user( $id );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=gm-customers&gm_done=' . ( $ok ? 'deleted' : 'failed' ) ) );
	exit;
} );

/* Orders list: "View orders" for one customer. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && 'gm_order' === $q->get( 'post_type' ) && ! empty( $_GET['gm_customer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$q->set( 'meta_query', array( array( 'key' => '_gm_user', 'value' => absint( $_GET['gm_customer'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}
} );

add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( $screen && 'edit-gm_order' === $screen->id && ! empty( $_GET['gm_customer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$user = get_userdata( absint( $_GET['gm_customer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		printf(
			'<div class="notice notice-info"><p>Showing orders for <strong>%s</strong>. <a href="%s">Show all orders</a></p></div>',
			esc_html( $user ? $user->display_name : 'a deleted customer' ),
			esc_url( admin_url( 'edit.php?post_type=gm_order' ) )
		);
	}
} );
