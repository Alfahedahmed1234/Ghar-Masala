<?php
/**
 * Seasonal themes: Appearance → Customize → Ghar Masala → Seasonal theme.
 *
 * Pick a festival (Eid, Ramadan, Diwali, Christmas, New Year, Holi,
 * Valentine's) and switch it Off / On / On between two dates. When on, the
 * home page picture gets balloon lettering in the festival's colours, plus
 * its own decorations (hanging moons, lanterns or baubles, snow, diyas,
 * fireworks…) and a short greeting. Everything else on the site stays the same.
 *
 * The date check also runs in the browser, so a cached page still switches
 * on and off on the right days.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The festivals. hang = [left %, string length px, icon[:colour], size px];
 * fx = extra effects drawn over the picture; icons = either side of the lettering.
 */
function gm_fest_themes() {
	return array(
		'eid'       => array(
			'label' => 'Eid (al-Fitr / al-Adha) – gold moons & stars',
			'title' => 'Eid Mubarak',
			'text'  => 'Wishing you and your family a blessed and joyful Eid',
			'hang'  => array( array( 30, 70, 'star', 26 ), array( 37, 120, 'moon', 44 ), array( 44, 50, 'star', 20 ), array( 56, 95, 'star', 30 ), array( 63, 60, 'moon', 34 ), array( 70, 130, 'star', 24 ) ),
			'fx'    => array( 'twinkle' ),
			'icons' => array( 'moon', 'star' ),
		),
		'ramadan'   => array(
			'label' => 'Ramadan – hanging lanterns',
			'title' => 'Ramadan Kareem',
			'text'  => 'Wishing you a peaceful and blessed Ramadan',
			'hang'  => array( array( 29, 60, 'star', 20 ), array( 35, 105, 'lantern', 34 ), array( 43, 45, 'moon', 30 ), array( 57, 80, 'lantern', 28 ), array( 64, 40, 'star', 22 ), array( 71, 120, 'lantern', 32 ) ),
			'fx'    => array( 'twinkle' ),
			'icons' => array( 'lantern', 'moon' ),
		),
		'diwali'    => array(
			'label' => 'Diwali – diyas, lanterns & sparks',
			'title' => 'Happy Diwali',
			'text'  => 'Wishing you a Diwali full of light, love and good food',
			'hang'  => array( array( 29, 70, 'kandil:magenta', 30 ), array( 36, 115, 'kandil:orange', 38 ), array( 44, 50, 'kandil:teal', 26 ), array( 56, 95, 'kandil:orange', 32 ), array( 63, 55, 'kandil:magenta', 28 ), array( 70, 125, 'kandil:teal', 30 ) ),
			'fx'    => array( 'embers', 'diyas', 'twinkle' ),
			'icons' => array( 'diya', 'diya' ),
		),
		'christmas' => array(
			'label' => 'Christmas – snow & baubles',
			'title' => 'Merry Christmas',
			'text'  => 'Season’s greetings from our kitchen to yours',
			'hang'  => array( array( 29, 65, 'bauble:red', 28 ), array( 36, 115, 'bauble:gold', 36 ), array( 43, 45, 'star', 22 ), array( 57, 90, 'bauble:green', 30 ), array( 64, 55, 'bauble:red', 26 ), array( 71, 125, 'bauble:gold', 30 ) ),
			'fx'    => array( 'snow' ),
			'icons' => array( 'tree', 'snowflake' ),
		),
		'newyear'   => array(
			'label' => 'New Year – fireworks & confetti',
			'title' => 'Happy New Year',
			'text'  => 'Thank you for a wonderful year – here’s to the next one!',
			'hang'  => array( array( 31, 80, 'star', 24 ), array( 40, 45, 'star:silver', 18 ), array( 61, 60, 'star:silver', 20 ), array( 69, 110, 'star', 26 ) ),
			'fx'    => array( 'fireworks', 'confetti' ),
			'icons' => array( 'firework', 'firework' ),
		),
		'holi'      => array(
			'label' => 'Holi – festival of colours',
			'title' => 'Happy Holi',
			'text'  => 'Wishing you a colourful and joyful Holi',
			'hang'  => array(),
			'fx'    => array( 'powder', 'confetti' ),
			'icons' => array( 'splash', 'splash' ),
		),
		'valentine' => array(
			'label' => 'Valentine’s Day – floating hearts',
			'title' => 'Happy Valentine’s',
			'text'  => 'Share the love – and a curry – with someone special',
			'hang'  => array( array( 30, 75, 'heart', 26 ), array( 38, 120, 'heart', 34 ), array( 62, 60, 'heart', 28 ), array( 70, 110, 'heart', 24 ) ),
			'fx'    => array( 'hearts', 'twinkle' ),
			'icons' => array( 'heart', 'heart' ),
		),
	);
}

function gm_fest_mod( $key ) {
	return (string) get_theme_mod( 'gm_season_' . $key, '' );
}

function gm_fest_date( $v ) {
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $v ) ? $v : '';
}

/** The chosen festival's key. */
function gm_fest_key() {
	$k = gm_fest_mod( 'theme' );
	return isset( gm_fest_themes()[ $k ] ) ? $k : 'eid';
}

/** The chosen festival, with the owner's own lettering/greeting if set. */
function gm_fest() {
	$t = gm_fest_themes()[ gm_fest_key() ];
	foreach ( array( 'title', 'text' ) as $f ) {
		$own = trim( gm_fest_mod( $f ) );
		if ( '' !== $own ) {
			$t[ $f ] = '-' === $own ? '' : $own;
		}
	}
	return $t;
}

/** Should the decorations be on the page at all (the browser makes the final date check)? */
function gm_fest_enabled() {
	$mode = gm_fest_mod( 'mode' );
	if ( 'on' === $mode ) {
		return true;
	}
	if ( 'dates' !== $mode ) {
		return false;
	}
	$to = gm_fest_date( gm_fest_mod( 'to' ) );
	// Still print it until the end date has passed (pages may be cached).
	return ! $to || $to >= gm_now()->modify( '-1 day' )->format( 'Y-m-d' );
}

/* ---------------------------------------------------------------------------
 * Customizer
 * ------------------------------------------------------------------------ */

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {
	$themes = gm_fest_themes();
	$wp->add_section( 'gm_season', array( 'title' => 'Seasonal theme (Eid, Diwali, Christmas…)', 'panel' => 'gm_panel', 'description' => 'Dress the home page up for a festival: balloon lettering, decorations and a greeting over the main picture. The rest of the site stays the same.' ) );

	$wp->add_setting( 'gm_season_mode', array( 'default' => 'off', 'sanitize_callback' => function ( $v ) { return in_array( $v, array( 'off', 'on', 'dates' ), true ) ? $v : 'off'; } ) );
	$wp->add_control( 'gm_season_mode', array( 'label' => 'Show it', 'section' => 'gm_season', 'type' => 'radio', 'choices' => array( 'off' => 'Off', 'on' => 'On now', 'dates' => 'On between the dates below' ) ) );

	$wp->add_setting( 'gm_season_theme', array( 'default' => 'eid', 'sanitize_callback' => function ( $v ) { return isset( gm_fest_themes()[ $v ] ) ? $v : 'eid'; } ) );
	$wp->add_control( 'gm_season_theme', array( 'label' => 'Festival', 'section' => 'gm_season', 'type' => 'select', 'choices' => wp_list_pluck( $themes, 'label' ) ) );

	foreach ( array( 'from' => 'From', 'to' => 'Until (last day shown)' ) as $key => $label ) {
		$wp->add_setting( 'gm_season_' . $key, array( 'default' => '', 'sanitize_callback' => 'gm_fest_date' ) );
		$wp->add_control( 'gm_season_' . $key, array( 'label' => $label, 'section' => 'gm_season', 'type' => 'date', 'description' => 'from' === $key ? 'Only used with “On between the dates below”. It switches itself on and off.' : '' ) );
	}

	$titles = array();
	foreach ( $themes as $t ) {
		$titles[] = '“' . $t['title'] . '”';
	}
	$wp->add_setting( 'gm_season_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp->add_control( 'gm_season_title', array( 'label' => 'Balloon lettering', 'section' => 'gm_season', 'type' => 'text', 'description' => 'Leave empty for the festival’s own (' . implode( ', ', $titles ) . '). Keep it short.' ) );
	$wp->add_setting( 'gm_season_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
	$wp->add_control( 'gm_season_text', array( 'label' => 'Greeting underneath', 'section' => 'gm_season', 'type' => 'text', 'description' => 'Leave empty for the festival’s own greeting, or type a single - for none.' ) );
}, 20 );

/* ---------------------------------------------------------------------------
 * Page output
 * ------------------------------------------------------------------------ */

/** Turn the decorations on for today (also works on cached pages). */
add_action( 'wp_body_open', function () {
	if ( ! gm_fest_enabled() ) {
		return;
	}
	$dates = 'dates' === gm_fest_mod( 'mode' );
	$from  = $dates ? gm_fest_date( gm_fest_mod( 'from' ) ) : '';
	$to    = $dates ? gm_fest_date( gm_fest_mod( 'to' ) ) : '';
	?>
	<script>(function(){var d=new Date(),t=d.getFullYear()+'-'+('0'+(d.getMonth()+1)).slice(-2)+'-'+('0'+d.getDate()).slice(-2),f=<?php echo wp_json_encode( $from ); ?>,u=<?php echo wp_json_encode( $to ); ?>;if((!f||t>=f)&&(!u||t<=u))document.body.classList.add('is-festive');})();</script>
	<?php
} );

/** Balloon font, only while a seasonal theme is on. */
add_action( 'wp_enqueue_scripts', function () {
	if ( gm_fest_enabled() ) {
		wp_enqueue_style( 'gm-fest-font', 'https://fonts.googleapis.com/css2?family=Baloo+2:wght@800&display=swap', array(), null );
	}
}, 30 );

/** Clear the page cache when the seasonal settings are saved. */
add_action( 'customize_save_after', function () {
	do_action( 'litespeed_purge_all' );
} );

/** Shared colour gradients for the icons (drawn once, hidden). */
function gm_fest_defs() {
	$g = array(
		'Gold'    => array( '#fff3c4', '#f2b938', '#b8780b' ),
		'Silver'  => array( '#ffffff', '#d9dce6', '#8d93a8' ),
		'Red'     => array( '#ff9a8f', '#e53935', '#8e0e0e' ),
		'Green'   => array( '#b6e3b0', '#2e9d48', '#0f5a26' ),
		'Pink'    => array( '#ffc6da', '#ff4f8b', '#a8124b' ),
		'Orange'  => array( '#ffe08a', '#ff8c1a', '#b8400c' ),
		'Magenta' => array( '#ffb0dc', '#e0218a', '#7d0d50' ),
		'Teal'    => array( '#9ff0e6', '#13a89e', '#0a5651' ),
		'Clay'    => array( '#f4a259', '#c5622a', '#7a3412' ),
	);
	$out = '<svg class="gm-fest-defs" width="0" height="0" aria-hidden="true" focusable="false"><defs>';
	foreach ( $g as $id => $c ) {
		$out .= '<linearGradient id="gmF' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $c[0] . '"/><stop offset=".5" stop-color="' . $c[1] . '"/><stop offset="1" stop-color="' . $c[2] . '"/></linearGradient>';
	}
	$out .= '<linearGradient id="gmFFlame" x1="0" y1="1" x2="0" y2="0"><stop offset="0" stop-color="#fff7cf"/><stop offset=".45" stop-color="#ffc93c"/><stop offset="1" stop-color="#ff6a00"/></linearGradient>';
	return $out . '</defs></svg>';
}

/** One festive icon as inline SVG. $name may carry a colour, e.g. "bauble:red". */
function gm_fest_icon( $name, $size = 32 ) {
	list( $kind, $col ) = array_pad( explode( ':', $name ), 2, '' );
	$fill = 'url(#gmF' . ucfirst( $col ? $col : 'gold' ) . ')';
	$vb   = '0 0 40 40';
	switch ( $kind ) {
		case 'moon':
			$body = '<path d="M24 3a17 17 0 1 0 13 28A14 14 0 1 1 24 3z" fill="' . $fill . '"/>';
			break;
		case 'star':
			$body = '<path d="M20 2l5.3 11.6L38 15l-9.5 8.6L31.2 37 20 30.4 8.8 37l2.7-13.4L2 15l12.7-1.4z" fill="' . $fill . '"/>';
			break;
		case 'lantern':
			$vb   = '0 0 40 56';
			$body = '<circle cx="20" cy="3.5" r="2.5" fill="none" stroke="url(#gmFGold)" stroke-width="1.6"/><path d="M11 15Q20 1 29 15z" fill="url(#gmFGold)"/><path d="M10 15h20l-2 24H12z" fill="url(#gmFGold)"/><path d="M13.5 18h13l-1.6 18h-9.8z" fill="#ffe9a8"/><path d="M17 18v18M23 18v18" stroke="#c98a12" stroke-width="1.2"/><path d="M12 39h16l-3 6H15z" fill="url(#gmFGold)"/><path d="M18 45l2 7 2-7z" fill="url(#gmFGold)"/>';
			break;
		case 'kandil':
			$vb   = '0 0 40 48';
			$body = '<path d="M20 2v3" stroke="url(#gmFGold)" stroke-width="1.6"/><path d="M20 5l13 13-13 17L7 18z" fill="' . $fill . '"/><path d="M20 10l7.5 8L20 28l-7.5-10z" fill="#fff1b8" opacity=".85"/><path d="M7 18h26M20 5v30" stroke="#ffd36b" stroke-width="1.1"/><path d="M20 35v9M16.5 36l-1.5 8M23.5 36l1.5 8" stroke="url(#gmFGold)" stroke-width="1.4" stroke-linecap="round"/>';
			break;
		case 'bauble':
			$vb   = '0 0 40 46';
			$body = '<circle cx="20" cy="3.5" r="2.2" fill="none" stroke="url(#gmFGold)" stroke-width="1.5"/><rect x="15.5" y="5.5" width="9" height="6" rx="1.2" fill="url(#gmFGold)"/><circle cx="20" cy="27" r="16" fill="' . $fill . '"/><path d="M5.5 24q14.5 6 29 0" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="1.6" stroke-dasharray="1 3" stroke-linecap="round"/><ellipse cx="13.5" cy="20" rx="3.6" ry="5.6" transform="rotate(30 13.5 20)" fill="#fff" opacity=".45"/>';
			break;
		case 'tree':
			$body = '<path d="M20 6l9 11h-4.5l7.5 11H8l7.5-11H11z" fill="url(#gmFGreen)"/><rect x="17.5" y="28" width="5" height="6" rx="1" fill="#7a4a21"/><path d="M20 0.5l1.6 3.4 3.7.4-2.8 2.5.8 3.6L20 8.6l-3.3 1.8.8-3.6-2.8-2.5 3.7-.4z" fill="url(#gmFGold)"/><circle cx="16" cy="21" r="1.6" fill="#ff5a4f"/><circle cx="23.5" cy="16" r="1.4" fill="#ffd54a"/><circle cx="25" cy="24.5" r="1.6" fill="#ffd54a"/><circle cx="19" cy="13.5" r="1.3" fill="#ff5a4f"/><circle cx="13" cy="25.5" r="1.3" fill="#fff"/>';
			break;
		case 'snowflake':
			$arm  = '<path d="M20 3v34M15.5 7.5L20 11l4.5-3.5M15.5 32.5L20 29l4.5 3.5"/>';
			$body = '<g fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' . $arm . '<g transform="rotate(60 20 20)">' . $arm . '</g><g transform="rotate(120 20 20)">' . $arm . '</g></g>';
			break;
		case 'diya':
			$body = '<path class="gm-fest-flame" d="M20 3c4 6 6 10 4.2 14.2Q22.6 21 20 21t-4.2-3.8C14 13 16 9 20 3z" fill="url(#gmFFlame)"/><path class="gm-fest-flame" d="M20 11c1.8 3 2.6 5 1.6 7Q21 19.6 20 19.6t-1.6-1.6c-1-2 0-4 1.6-7z" fill="#fffbe6"/><path d="M3 23q17-3 34 0-2.5 13-17 13T3 23z" fill="url(#gmFClay)"/><ellipse cx="20" cy="23.2" rx="17" ry="2.6" fill="#ffcf5a"/><path d="M9 28.5h22" stroke="#ffd36b" stroke-width="1.3" stroke-dasharray="1.5 2.5" stroke-linecap="round"/>';
			break;
		case 'heart':
			$body = '<path d="M20 36C8 27 3 20 3 13a8.6 8.6 0 0 1 17-3.6A8.6 8.6 0 0 1 37 13c0 7-5 14-17 23z" fill="' . ( $col ? $fill : 'url(#gmFPink)' ) . '"/><ellipse cx="11.5" cy="12.5" rx="2.6" ry="4" transform="rotate(35 11.5 12.5)" fill="#fff" opacity=".45"/>';
			break;
		case 'firework':
			$body = '<g stroke="url(#gmFGold)" stroke-width="2.2" stroke-linecap="round"><path d="M20 2v8M20 30v8M2 20h8M30 20h8M7.3 7.3l5.7 5.7M27 27l5.7 5.7M7.3 32.7l5.7-5.7M27 13l5.7-5.7"/></g><circle cx="20" cy="20" r="3.4" fill="#fff6d0"/>';
			break;
		case 'splash':
			$body = '<circle cx="14" cy="15" r="9" fill="#ff3d8b" opacity=".9"/><circle cx="26" cy="14" r="8" fill="#ffd400" opacity=".9"/><circle cx="20" cy="25" r="9" fill="#1fb5ff" opacity=".85"/><circle cx="29" cy="27" r="5" fill="#2ed573" opacity=".9"/><circle cx="9" cy="28" r="4" fill="#a55eea" opacity=".9"/>';
			break;
		default:
			return '';
	}
	list( , , $w, $h ) = explode( ' ', $vb );
	$px = (int) $size;
	return '<svg viewBox="' . $vb . '" width="' . $px . '" height="' . (int) round( $px * $h / $w ) . '" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** Repeatable 0–1 "random" numbers, so the layout is the same on every load. */
function gm_fest_rand( $i, $salt ) {
	$x = sin( $i * 12.9898 + $salt * 78.233 ) * 43758.5453;
	return $x - floor( $x );
}

function gm_fest_n( $v, $dp = 2 ) {
	return number_format( (float) $v, $dp, '.', '' );
}

/** Everything drawn over the home page picture (behind the text). */
function gm_render_fest_decor() {
	if ( ! gm_fest_enabled() ) {
		return;
	}
	$t   = gm_fest();
	$key = gm_fest_key();
	$r   = 'gm_fest_rand';
	$n   = 'gm_fest_n';

	echo '<div class="gm-fest-decor gm-fest--' . esc_attr( $key ) . '" aria-hidden="true">';
	echo gm_fest_defs(); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.

	foreach ( $t['hang'] as $i => $h ) {
		printf(
			'<span class="gm-fest-hang" style="left:%1$d%%;--len:%2$dpx;--d:-%3$ss">%4$s</span>',
			(int) $h[0],
			(int) $h[1],
			esc_attr( $n( $i * 0.7 ) ),
			gm_fest_icon( $h[2], $h[3] ) // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
		);
	}

	foreach ( $t['fx'] as $fx ) {
		switch ( $fx ) {
			case 'twinkle':
				foreach ( array( array( 8, 30 ), array( 18, 62 ), array( 86, 26 ), array( 92, 58 ), array( 12, 82 ), array( 80, 76 ), array( 50, 18 ) ) as $i => $tw ) {
					printf( '<i class="gm-fest-twinkle" style="left:%d%%;top:%d%%;--d:%ss"></i>', (int) $tw[0], (int) $tw[1], esc_attr( $n( $i * 0.6, 1 ) ) );
				}
				break;

			case 'snow':
				for ( $i = 0; $i < 60; $i++ ) {
					$s = 2.5 + $r( $i, 1 ) * 5.5;
					$d = 9 + $r( $i, 2 ) * 10;
					printf(
						'<i class="gm-fest-fall%s" style="left:%s%%;--s:%spx;--t:%ss;--d:-%ss;--x:%spx;--o:%s"></i>',
						$i % 3 ? ' gm-fest-m' : '',
						esc_attr( $n( $r( $i, 3 ) * 100, 1 ) ),
						esc_attr( $n( $s, 1 ) ),
						esc_attr( $n( $d ) ),
						esc_attr( $n( $r( $i, 4 ) * $d ) ),
						esc_attr( $n( ( $r( $i, 5 ) - 0.5 ) * 120, 0 ) ),
						esc_attr( $n( 0.45 + $s / 16 ) )
					);
				}
				break;

			case 'confetti':
				$cols = 'holi' === $key ? array( '#ff3d8b', '#ffd400', '#1fb5ff', '#2ed573', '#a55eea', '#ff7f11' ) : array( '#f2b938', '#fff3c4', '#d9dce6', '#c98a12', '#ffffff' );
				for ( $i = 0; $i < 36; $i++ ) {
					$d = 7 + $r( $i, 6 ) * 7;
					printf(
						'<i class="gm-fest-fall gm-fest-conf%s" style="left:%s%%;--s:%spx;--t:%ss;--d:-%ss;--x:%spx;--c:%s;--o:.9"></i>',
						$i % 2 ? ' gm-fest-m' : '',
						esc_attr( $n( $r( $i, 7 ) * 100, 1 ) ),
						esc_attr( $n( 6 + $r( $i, 8 ) * 5, 1 ) ),
						esc_attr( $n( $d ) ),
						esc_attr( $n( $r( $i, 9 ) * $d ) ),
						esc_attr( $n( ( $r( $i, 10 ) - 0.5 ) * 160, 0 ) ),
						esc_attr( $cols[ $i % count( $cols ) ] )
					);
				}
				break;

			case 'embers':
				for ( $i = 0; $i < 30; $i++ ) {
					$d = 6 + $r( $i, 11 ) * 7;
					printf(
						'<i class="gm-fest-rise gm-fest-ember%s" style="left:%s%%;--s:%spx;--t:%ss;--d:-%ss;--x:%spx;--o:%s"></i>',
						$i % 2 ? ' gm-fest-m' : '',
						esc_attr( $n( $r( $i, 12 ) * 100, 1 ) ),
						esc_attr( $n( 2 + $r( $i, 13 ) * 3.5, 1 ) ),
						esc_attr( $n( $d ) ),
						esc_attr( $n( $r( $i, 14 ) * $d ) ),
						esc_attr( $n( ( $r( $i, 15 ) - 0.5 ) * 90, 0 ) ),
						esc_attr( $n( 0.55 + $r( $i, 16 ) * 0.45 ) )
					);
				}
				break;

			case 'hearts':
				for ( $i = 0; $i < 16; $i++ ) {
					$d = 9 + $r( $i, 17 ) * 8;
					printf(
						'<span class="gm-fest-rise gm-fest-heart%s" style="left:%s%%;--t:%ss;--d:-%ss;--x:%spx;--o:%s">%s</span>',
						$i % 2 ? ' gm-fest-m' : '',
						esc_attr( $n( $r( $i, 18 ) * 100, 1 ) ),
						esc_attr( $n( $d ) ),
						esc_attr( $n( $r( $i, 19 ) * $d ) ),
						esc_attr( $n( ( $r( $i, 20 ) - 0.5 ) * 100, 0 ) ),
						esc_attr( $n( 0.5 + $r( $i, 21 ) * 0.4 ) ),
						gm_fest_icon( 0 === $i % 3 ? 'heart:red' : 'heart', (int) ( 12 + $r( $i, 22 ) * 16 ) ) // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
					);
				}
				break;

			case 'fireworks':
				$bursts = array( array( 14, 22, '#f2b938' ), array( 84, 18, '#ff6b8b' ), array( 24, 58, '#7fd8ff' ), array( 78, 52, '#fff3c4' ), array( 50, 12, '#b48cff' ), array( 90, 80, '#f2b938' ), array( 8, 84, '#ff9f43' ) );
				foreach ( $bursts as $b => $f ) {
					printf( '<span class="gm-fest-burst%s" style="left:%d%%;top:%d%%;--c:%s;--d:%ss">', $b > 3 ? ' gm-fest-m' : '', (int) $f[0], (int) $f[1], esc_attr( $f[2] ), esc_attr( $n( $b * 0.9, 1 ) ) );
					for ( $a = 0; $a < 14; $a++ ) {
						printf( '<i style="--a:%ddeg"></i>', (int) ( $a * 360 / 14 ) );
					}
					echo '</span>';
				}
				break;

			case 'powder':
				$cols = array( '#ff3d8b', '#ffd400', '#1fb5ff', '#2ed573', '#a55eea', '#ff7f11', '#ff3d8b', '#1fb5ff' );
				$pos  = array( array( 6, 20 ), array( 92, 14 ), array( 14, 70 ), array( 88, 64 ), array( 30, 8 ), array( 70, 92 ), array( 4, 96 ), array( 60, 4 ) );
				foreach ( $pos as $i => $p ) {
					printf( '<i class="gm-fest-powder%s" style="left:%d%%;top:%d%%;--c:%s;--d:-%ss;--s:%dpx"></i>', $i > 5 ? ' gm-fest-m' : '', (int) $p[0], (int) $p[1], esc_attr( $cols[ $i ] ), esc_attr( $n( $i * 0.8, 1 ) ), (int) ( 180 + $r( $i, 23 ) * 140 ) );
				}
				break;

			case 'diyas':
				echo '<div class="gm-fest-diyas">';
				for ( $i = 0; $i < 9; $i++ ) {
					printf( '<span class="gm-fest-diya%s" style="--d:-%ss">%s</span>', $i % 2 ? ' gm-fest-m' : '', esc_attr( $n( $r( $i, 24 ) * 2 ) ), gm_fest_icon( 'diya', 40 ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
				}
				echo '</div>';
				break;
		}
	}
	echo '</div>';
}

/** Balloon lettering + greeting, at the top of the home page text. */
function gm_render_fest_title() {
	if ( ! gm_fest_enabled() ) {
		return;
	}
	$t     = gm_fest();
	$title = trim( $t['title'] );
	$key   = gm_fest_key();
	if ( '' !== $title ) {
		echo '<div class="gm-fest-title gm-fest--' . esc_attr( $key ) . '" role="text" aria-label="' . esc_attr( $title ) . '">';
		echo '<span class="gm-fest-title__icon" aria-hidden="true">' . gm_fest_icon( $t['icons'][0], 46 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
		echo '<span class="gm-fest-title__words" aria-hidden="true">';
		$i = 0;
		foreach ( preg_split( '/\s+/u', $title ) as $word ) {
			echo '<span class="gm-fest-word">';
			foreach ( preg_split( '//u', $word, -1, PREG_SPLIT_NO_EMPTY ) as $ch ) {
				printf( '<span class="gm-fest-b" style="--i:%d">%s</span>', (int) $i++, esc_html( $ch ) );
			}
			echo '</span>';
			$i++;
		}
		echo '</span>';
		echo '<span class="gm-fest-title__icon gm-fest-title__icon--r" aria-hidden="true">' . gm_fest_icon( $t['icons'][1], 46 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed SVG.
		echo '</div>';
	}
	if ( '' !== trim( $t['text'] ) ) {
		echo '<p class="gm-fest-text gm-fest--' . esc_attr( $key ) . '">' . esc_html( $t['text'] ) . '</p>';
	}
}
