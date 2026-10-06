<?php
/**
 * Banners & announcements, managed in WP Admin → Banners.
 *
 * Each banner: a message, an optional discount code (with a Copy button), an
 * optional button linking somewhere on the site, a colour, where it appears,
 * optional start/end dates and whether customers can close it.
 * Several banners in the top bar take turns.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_banner_places() {
	return array(
		'top'      => 'Top of every page (announcement bar)',
		'home'     => 'Home page, under the main picture',
		'menu'     => 'Menu page, above the dishes',
		'checkout' => 'Checkout, above the payment details',
	);
}

function gm_banner_styles() {
	return array(
		'saffron' => 'Saffron (gold)',
		'green'   => 'Ghar Masala green',
		'dark'    => 'Dark',
		'red'     => 'Red (urgent notices)',
	);
}

function gm_banner_links() {
	return array(
		''          => 'No button',
		'menu'      => 'Menu',
		'order'     => 'Your order / book a slot',
		'reviews'   => 'Reviews',
		'news'      => 'News',
		'contact'   => 'Contact us',
		'allergens' => 'Allergens',
		'how'       => 'How it works',
		'account'   => 'My account',
		'custom'    => 'Another web address…',
	);
}

add_action( 'init', function () {
	register_post_type(
		'gm_banner',
		array(
			'labels'        => array(
				'name'               => 'Banners',
				'singular_name'      => 'Banner',
				'menu_name'          => 'Banners',
				'all_items'          => 'All banners',
				'add_new'            => 'Add banner',
				'add_new_item'       => 'Add a banner',
				'edit_item'          => 'Edit banner',
				'search_items'       => 'Search banners',
				'not_found'          => 'No banners yet.',
				'not_found_in_trash' => 'No banners in the bin.',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 4,
			'menu_icon'     => 'dashicons-megaphone',
			'supports'      => array( 'title', 'page-attributes' ),
		)
	);
} );

/** A banner's settings as a plain array. */
function gm_banner( $post ) {
	$post = get_post( $post );
	$m    = function ( $k, $d = '' ) use ( $post ) {
		$v = get_post_meta( $post->ID, '_gm_' . $k, true );
		return '' === $v || null === $v ? $d : $v;
	};
	$places = $m( 'places', array( 'top' ) );
	return array(
		'id'          => $post->ID,
		'message'     => (string) $m( 'message' ),
		'code'        => strtoupper( (string) $m( 'code' ) ),
		'link'        => (string) $m( 'link' ),
		'url'         => (string) $m( 'url' ),
		'button'      => (string) $m( 'button', 'Order now' ),
		'style'       => array_key_exists( $m( 'style' ), gm_banner_styles() ) ? $m( 'style' ) : 'saffron',
		'places'      => is_array( $places ) ? $places : array( 'top' ),
		'start'       => (string) $m( 'start' ),
		'end'         => (string) $m( 'end' ),
		'dismissible' => (bool) $m( 'dismissible', 1 ),
		'active'      => 'publish' === $post->post_status,
	);
}

/** Published banners for one place whose dates include today. */
function gm_banners_for( $place ) {
	$today = gm_now()->format( 'Y-m-d' );
	$out   = array();
	$posts = get_posts(
		array(
			'post_type'      => 'gm_banner',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		)
	);
	foreach ( $posts as $post ) {
		$b = gm_banner( $post );
		if ( '' === trim( $b['message'] ) || ! in_array( $place, $b['places'], true ) ) {
			continue;
		}
		if ( ( $b['start'] && $b['start'] > $today ) || ( $b['end'] && $b['end'] < $today ) ) {
			continue;
		}
		$out[] = $b;
	}
	return $out;
}

/** Where a banner's button goes. */
function gm_banner_href( array $b ) {
	if ( 'custom' === $b['link'] ) {
		return $b['url'];
	}
	return $b['link'] ? gm_view_url( $b['link'] ) : '';
}

/** Print the banners for a place. Top-bar banners take turns. */
function gm_render_banners( $place ) {
	$banners = gm_banners_for( $place );
	if ( ! $banners ) {
		return;
	}
	$rotate = 'top' === $place && count( $banners ) > 1;
	printf( '<div class="gm-banners gm-banners--%1$s" data-gm-banners%2$s>', esc_attr( $place ), $rotate ? ' data-gm-rotate' : '' );
	foreach ( $banners as $i => $b ) {
		$href = gm_banner_href( $b );
		printf(
			'<div class="gm-banner gm-banner--%1$s" data-gm-banner="%2$s" data-start="%3$s" data-end="%4$s"%5$s>',
			esc_attr( $b['style'] ),
			esc_attr( $b['id'] . '-' . md5( $b['message'] . $b['code'] ) ), // a new message shows again even if an old one was closed
			esc_attr( $b['start'] ),
			esc_attr( $b['end'] ),
			$rotate && $i ? ' hidden' : ''
		);
		echo '<div class="gm-banner__inner">';
		echo '<p class="gm-banner__text">' . esc_html( $b['message'] ) . '</p>';
		if ( $b['code'] ) {
			printf(
				'<span class="gm-banner__code"><span>Code</span> <strong>%1$s</strong> <button type="button" data-gm-copy="%1$s" aria-label="Copy code %1$s">Copy</button></span>',
				esc_attr( $b['code'] )
			);
		}
		if ( $href ) {
			$external = 'custom' === $b['link'] && 0 !== strpos( $href, home_url() );
			printf(
				'<a class="gm-banner__btn" href="%s"%s>%s</a>',
				esc_url( $href ),
				$external ? ' target="_blank" rel="noopener"' : '',
				esc_html( $b['button'] )
			);
		}
		if ( $b['dismissible'] ) {
			echo '<button type="button" class="gm-banner__close" data-gm-dismiss aria-label="Close this message">×</button>';
		}
		echo '</div></div>';
	}
	echo '</div>';
}

/** Copy, close (remembered on this device), date check and rotation. */
add_action( 'wp_footer', function () {
	?>
	<script>
	(function () {
		var KEY = 'gm_closed_banners';
		var closed = [];
		try { closed = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) {}
		var today = new Date().toISOString().slice(0, 10);
		// Pages can be cached, so check the dates in the browser too.
		document.querySelectorAll('[data-gm-banner]').forEach(function (b) {
			var off = closed.indexOf(b.getAttribute('data-gm-banner')) !== -1 ||
				(b.dataset.start && b.dataset.start > today) || (b.dataset.end && b.dataset.end < today);
			if (off) b.remove();
		});
		document.querySelectorAll('[data-gm-banners]').forEach(function (box) {
			var items = box.querySelectorAll('[data-gm-banner]');
			if (!items.length) { box.remove(); return; }
			items.forEach(function (b, i) { b.hidden = box.hasAttribute('data-gm-rotate') && i > 0; });
			if (box.hasAttribute('data-gm-rotate') && items.length > 1) {
				var n = 0;
				setInterval(function () {
					var list = box.querySelectorAll('[data-gm-banner]');
					if (list.length < 2 || box.matches(':hover')) return;
					list[n % list.length].hidden = true;
					n = (n + 1) % list.length;
					list[n].hidden = false;
				}, 6000);
			}
		});
		document.addEventListener('click', function (e) {
			var copy = e.target.closest('[data-gm-copy]');
			if (copy) {
				var code = copy.getAttribute('data-gm-copy');
				var done = function () { copy.textContent = 'Copied ✓'; setTimeout(function () { copy.textContent = 'Copy'; }, 2000); };
				if (navigator.clipboard) navigator.clipboard.writeText(code).then(done, done); else done();
				var field = document.getElementById('gm-discount');
				if (field && !field.value) field.value = code; // ready for checkout
				return;
			}
			var x = e.target.closest('[data-gm-dismiss]');
			if (x) {
				var b = x.closest('[data-gm-banner]');
				closed.push(b.getAttribute('data-gm-banner'));
				try { localStorage.setItem(KEY, JSON.stringify(closed.slice(-50))); } catch (err) {}
				var box = b.closest('[data-gm-banners]');
				b.remove();
				var rest = box.querySelectorAll('[data-gm-banner]');
				if (!rest.length) box.remove(); else rest[0].hidden = false;
			}
		});
	})();
	</script>
	<?php
} );

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------ */

add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gm_banner' === $post->post_type ? 'Name for your own reference, e.g. “October 10% code”' : $text;
}, 10, 2 );

add_action( 'add_meta_boxes_gm_banner', function () {
	remove_meta_box( 'slugdiv', 'gm_banner', 'normal' );
	add_meta_box( 'gm_banner_box', 'Banner', 'gm_render_banner_box', 'gm_banner', 'normal', 'high' );
} );

function gm_render_banner_box( $post ) {
	wp_nonce_field( 'gm_banner_save', 'gm_banner_nonce' );
	$b = gm_banner( $post );
	?>
	<table class="form-table" role="presentation">
		<tr><th scope="row"><label for="gm-b-msg">Message</label></th>
			<td><input type="text" id="gm-b-msg" name="gm_message" value="<?php echo esc_attr( $b['message'] ); ?>" class="large-text" maxlength="160" placeholder="e.g. 10% off your first order this week!" required>
			<p class="description">Keep it short — one line reads best on phones.</p></td></tr>
		<tr><th scope="row"><label for="gm-b-code">Discount code</label></th>
			<td><input type="text" id="gm-b-code" name="gm_code" value="<?php echo esc_attr( $b['code'] ); ?>" style="text-transform:uppercase;width:14em" placeholder="Optional, e.g. WELCOME10">
			<p class="description">Shown with a “Copy” button. Set the code itself up in <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gm_discount' ) ); ?>">Discounts</a>.</p></td></tr>
		<tr><th scope="row"><label for="gm-b-link">Button</label></th>
			<td><select id="gm-b-link" name="gm_link" onchange="document.getElementById('gm-b-url-row').style.display=this.value==='custom'?'':'none'">
				<?php foreach ( gm_banner_links() as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $b['link'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
			</select>
			&nbsp; <label>Button text <input type="text" name="gm_button" value="<?php echo esc_attr( $b['button'] ); ?>" style="width:12em" maxlength="30"></label></td></tr>
		<tr id="gm-b-url-row" style="<?php echo 'custom' === $b['link'] ? '' : 'display:none'; ?>"><th scope="row"><label for="gm-b-url">Web address</label></th>
			<td><input type="url" id="gm-b-url" name="gm_url" value="<?php echo esc_attr( $b['url'] ); ?>" class="regular-text" placeholder="https://"></td></tr>
		<tr><th scope="row">Show it</th>
			<td><?php foreach ( gm_banner_places() as $k => $label ) : ?>
				<label style="display:block;margin-bottom:6px"><input type="checkbox" name="gm_places[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $b['places'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?></td></tr>
		<tr><th scope="row"><label for="gm-b-style">Colour</label></th>
			<td><select id="gm-b-style" name="gm_style"><?php foreach ( gm_banner_styles() as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $b['style'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
		<tr><th scope="row">Dates</th>
			<td><label>From <input type="date" name="gm_start" value="<?php echo esc_attr( $b['start'] ); ?>"></label> &nbsp;
				<label>until <input type="date" name="gm_end" value="<?php echo esc_attr( $b['end'] ); ?>"></label>
				<p class="description">Optional. Leave empty to show it until you switch it off.</p></td></tr>
		<tr><th scope="row">Closing</th>
			<td><label><input type="checkbox" name="gm_dismissible" value="1" <?php checked( $b['dismissible'] ); ?>> Let customers close it (it stays closed on their device)</label></td></tr>
	</table>
	<p><strong>Publish</strong> to show it; switch it to <strong>Draft</strong> to hide it. With more than one banner in the top bar they take turns every few seconds — use <strong>Order</strong> (on the right) to choose which comes first.</p>
	<?php
}

add_action( 'save_post_gm_banner', function ( $id ) {
	if ( ! isset( $_POST['gm_banner_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gm_banner_nonce'] ), 'gm_banner_save' ) || ! current_user_can( 'edit_post', $id ) ) {
		return;
	}
	$in    = wp_unslash( $_POST );
	$date  = function ( $v ) {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : '';
	};
	$links = gm_banner_links();
	update_post_meta( $id, '_gm_message', sanitize_text_field( mb_substr( (string) ( $in['gm_message'] ?? '' ), 0, 160 ) ) );
	update_post_meta( $id, '_gm_code', strtoupper( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $in['gm_code'] ?? '' ) ) ) );
	update_post_meta( $id, '_gm_link', array_key_exists( $in['gm_link'] ?? '', $links ) ? $in['gm_link'] : '' );
	update_post_meta( $id, '_gm_url', esc_url_raw( (string) ( $in['gm_url'] ?? '' ) ) );
	update_post_meta( $id, '_gm_button', sanitize_text_field( mb_substr( (string) ( $in['gm_button'] ?? '' ), 0, 30 ) ) );
	update_post_meta( $id, '_gm_places', array_values( array_intersect( (array) ( $in['gm_places'] ?? array() ), array_keys( gm_banner_places() ) ) ) );
	update_post_meta( $id, '_gm_style', array_key_exists( $in['gm_style'] ?? '', gm_banner_styles() ) ? $in['gm_style'] : 'saffron' );
	update_post_meta( $id, '_gm_start', $date( $in['gm_start'] ?? '' ) );
	update_post_meta( $id, '_gm_end', $date( $in['gm_end'] ?? '' ) );
	update_post_meta( $id, '_gm_dismissible', empty( $in['gm_dismissible'] ) ? 0 : 1 );
	do_action( 'litespeed_purge_all' );
} );

add_filter( 'manage_gm_banner_posts_columns', function () {
	return array(
		'cb'          => '<input type="checkbox">',
		'title'       => 'Banner',
		'gm_message'  => 'Message',
		'gm_places'   => 'Shown',
		'gm_dates'    => 'Dates',
		'gm_bstatus'  => 'Status',
	);
} );

add_action( 'manage_gm_banner_posts_custom_column', function ( $col, $id ) {
	$b = gm_banner( $id );
	switch ( $col ) {
		case 'gm_message':
			echo esc_html( $b['message'] ) . ( $b['code'] ? ' <code>' . esc_html( $b['code'] ) . '</code>' : '' );
			break;
		case 'gm_places':
			$short = array( 'top' => 'Top bar', 'home' => 'Home', 'menu' => 'Menu', 'checkout' => 'Checkout' );
			echo esc_html( implode( ', ', array_map( function ( $p ) use ( $short ) { return $short[ $p ] ?? $p; }, $b['places'] ) ) );
			break;
		case 'gm_dates':
			$f = function ( $d ) {
				return date_i18n( 'j M Y', strtotime( $d ) );
			};
			echo esc_html( $b['start'] || $b['end'] ? ( $b['start'] ? $f( $b['start'] ) : 'Now' ) . ' – ' . ( $b['end'] ? $f( $b['end'] ) : 'no end' ) : 'Always' );
			break;
		case 'gm_bstatus':
			$today = gm_now()->format( 'Y-m-d' );
			if ( ! $b['active'] ) {
				echo 'Off (draft)';
			} elseif ( $b['end'] && $b['end'] < $today ) {
				echo 'Ended';
			} elseif ( $b['start'] && $b['start'] > $today ) {
				echo 'Scheduled';
			} else {
				echo '<strong style="color:#008a20">Showing</strong>';
			}
			break;
	}
}, 10, 2 );
