<?php
/**
 * Opening times, closed dates and delivery-slot capacity.
 * Managed in WP Admin → Orders → Opening times & slots.
 *
 *   gm_schedule        option: regular week (days, opening/closing time,
 *                      slot length, delivery window, cut-off, orders per slot)
 *                      and a list of closed dates (holidays).
 *   gm_slot_overrides  option: per-date changes from the slot grid —
 *                      [ '2026-10-08' => [ 'closed' => 1, '18:30' => 2, … ] ]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function gm_schedule_defaults() {
	return array(
		'open_days'    => array( 0, 1, 2, 3, 4 ), // 0 = Sunday … 6 = Saturday.
		'open'         => '18:00',
		'close'        => '22:00',
		'every'        => 30,                     // Minutes between slot start times.
		'window'       => 15,                     // Length of each delivery window.
		'cutoff'       => 19,                     // Orders close at this hour the day before.
		'capacity'     => 1,                      // Orders each slot can take.
		'closed_dates' => '',
	);
}

function gm_schedule() {
	static $s = null;
	if ( null === $s ) {
		$s = wp_parse_args( (array) get_option( 'gm_schedule', array() ), gm_schedule_defaults() );
	}
	return $s;
}

function gm_slot_overrides() {
	$o = get_option( 'gm_slot_overrides', array() );
	return is_array( $o ) ? $o : array();
}

/** "18:00" → 1080 */
function gm_minutes( $hhmm ) {
	list( $h, $m ) = array_map( 'intval', explode( ':', $hhmm ) );
	return $h * 60 + $m;
}

function gm_hhmm( $minutes ) {
	return sprintf( '%02d:%02d', intdiv( $minutes, 60 ) % 24, $minutes % 60 );
}

/** Slot start times for the regular week, e.g. [ '18:00', '18:30', … ]. */
function gm_slot_starts() {
	$s     = gm_schedule();
	$open  = gm_minutes( $s['open'] );
	$close = gm_minutes( $s['close'] );
	$every = max( 5, (int) $s['every'] );
	$out   = array();
	for ( $t = $open; $t + $every <= $close && count( $out ) < 96; $t += $every ) {
		$out[] = gm_hhmm( $t );
	}
	return $out;
}

/** "18:30" → "6:30pm"; with $short, "18:00" → "6pm". */
function gm_time_label( $hhmm, $short = false ) {
	$mins = gm_minutes( $hhmm );
	$h    = intdiv( $mins, 60 ) % 24;
	$m    = $mins % 60;
	$h12  = $h % 12 ? $h % 12 : 12;
	$time = ( $short && ! $m ) ? (string) $h12 : $h12 . ':' . sprintf( '%02d', $m );
	return $time . ( $h < 12 ? 'am' : 'pm' );
}

/** "6–10pm", or "11am–2pm" when the times straddle midday. */
function gm_hours_text() {
	$s    = gm_schedule();
	$from = gm_time_label( $s['open'], true );
	$to   = gm_time_label( $s['close'], true );
	if ( substr( $from, -2 ) === substr( $to, -2 ) ) {
		$from = substr( $from, 0, -2 );
	}
	return $from . '–' . $to;
}

/**
 * "Sunday to Thursday", "every day", or "Monday, Wednesday and Friday".
 * With $short: "Sun–Thu".
 */
function gm_days_text( $short = false ) {
	$names = $short ? array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ) : array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	$days  = array_values( array_unique( array_map( 'intval', (array) gm_schedule()['open_days'] ) ) );
	sort( $days );
	if ( ! $days ) {
		return 'no days (closed)';
	}
	if ( 7 === count( $days ) ) {
		return $short ? 'Daily' : 'every day';
	}
	// One unbroken run round the week (e.g. Sun–Thu, or Fri–Mon)?
	foreach ( $days as $start ) {
		if ( in_array( ( $start + 6 ) % 7, $days, true ) ) {
			continue; // Not the start of the run.
		}
		$run = array();
		for ( $d = $start; in_array( $d % 7, $days, true ) && count( $run ) < 7; $d++ ) {
			$run[] = $d % 7;
		}
		if ( count( $run ) === count( $days ) ) {
			if ( 1 === count( $run ) ) {
				return $names[ $run[0] ];
			}
			return $names[ $run[0] ] . ( $short ? '–' : ' to ' ) . $names[ end( $run ) ];
		}
		break;
	}
	$list = array_map(
		function ( $d ) use ( $names ) {
			return $names[ $d ];
		},
		$days
	);
	$last = array_pop( $list );
	return implode( ', ', $list ) . ' and ' . $last;
}

/** "7pm the day before" */
function gm_cutoff_text() {
	return gm_time_label( gm_hhmm( (int) gm_schedule()['cutoff'] * 60 ), true ) . ' the day before';
}

/** Closed dates from the holidays box, as a set: [ 'Y-m-d' => true ]. */
function gm_closed_dates() {
	static $dates = null;
	if ( null !== $dates ) {
		return $dates;
	}
	$dates = array();
	$tz    = gm_tz();
	foreach ( preg_split( '/[\r\n,]+/', (string) gm_schedule()['closed_dates'] ) as $line ) {
		$parts = preg_split( '/\s*(?:–|—|-\s|\sto\s)\s*/', trim( $line ) );
		$from  = gm_parse_date( $parts[0] ?? '', $tz );
		if ( ! $from ) {
			continue;
		}
		$to = isset( $parts[1] ) ? gm_parse_date( $parts[1], $tz ) : $from;
		$to = $to && $to >= $from ? $to : $from;
		for ( $d = $from, $n = 0; $d <= $to && $n < 400; $d = $d->modify( '+1 day' ), $n++ ) {
			$dates[ $d->format( 'Y-m-d' ) ] = true;
		}
	}
	return $dates;
}

/** Accepts 25/12/2026, 5/1/2027, 25-12-2026, 25.12.2026 or 2026-12-25. */
function gm_parse_date( $text, $tz ) {
	$text = trim( $text );
	foreach ( array( 'd/m/Y', 'j/n/Y', 'Y-m-d', 'd-m-Y', 'j-n-Y', 'd.m.Y', 'j.n.Y' ) as $format ) {
		$d = DateTimeImmutable::createFromFormat( '!' . $format, $text, $tz );
		if ( $d && $d->format( $format ) === $text ) { // Rejects 31/02 and the like.
			return $d;
		}
	}
	return null;
}

/** Is the kitchen delivering on this date at all (regular days, holidays, the grid)? */
function gm_date_closed( $date ) {
	$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, gm_tz() );
	if ( ! $day ) {
		return true;
	}
	if ( ! in_array( (int) $day->format( 'w' ), array_map( 'intval', (array) gm_schedule()['open_days'] ), true ) ) {
		return true;
	}
	if ( isset( gm_closed_dates()[ $date ] ) ) {
		return true;
	}
	return ! empty( gm_slot_overrides()[ $date ]['closed'] );
}

/** How many orders a slot can take on a date (0 = blocked). */
function gm_slot_capacity( $date, $time ) {
	$o = gm_slot_overrides();
	if ( isset( $o[ $date ][ $time ] ) ) {
		return max( 0, (int) $o[ $date ][ $time ] );
	}
	return max( 0, (int) gm_schedule()['capacity'] );
}

/* ---------------------------------------------------------------------------
 * Admin screen: Orders → Opening times & slots
 * ------------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=gm_order', 'Opening times & slots', 'Opening times & slots', 'manage_options', 'gm-schedule', 'gm_render_schedule_page' );
} );

add_action( 'admin_post_gm_save_schedule', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'gm_save_schedule' );
	$in = wp_unslash( $_POST );

	// Regular week.
	$time = function ( $v, $fallback ) {
		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', (string) $v ) ? $v : $fallback;
	};
	$d    = gm_schedule_defaults();
	$s    = array(
		'open_days'    => array_values( array_intersect( array_map( 'intval', (array) ( $in['open_days'] ?? array() ) ), range( 0, 6 ) ) ),
		'open'         => $time( $in['open'] ?? '', $d['open'] ),
		'close'        => $time( $in['close'] ?? '', $d['close'] ),
		'every'        => in_array( (int) ( $in['every'] ?? 30 ), array( 15, 20, 30, 45, 60 ), true ) ? (int) $in['every'] : 30,
		'window'       => max( 5, min( 120, (int) ( $in['window'] ?? 15 ) ) ),
		'cutoff'       => max( 0, min( 23, (int) ( $in['cutoff'] ?? 19 ) ) ),
		'capacity'     => max( 0, min( 50, (int) ( $in['capacity'] ?? 1 ) ) ),
		'closed_dates' => sanitize_textarea_field( $in['closed_dates'] ?? '' ),
	);
	if ( gm_minutes( $s['close'] ) <= gm_minutes( $s['open'] ) ) {
		$s['close'] = gm_hhmm( min( 23 * 60 + 59, gm_minutes( $s['open'] ) + $s['every'] ) );
	}
	update_option( 'gm_schedule', $s );

	// Slot grid: keep only the cells that differ from the regular capacity.
	$overrides = gm_slot_overrides();
	$today     = gm_now()->format( 'Y-m-d' );
	foreach ( array_keys( $overrides ) as $date ) {
		if ( $date < $today ) {
			unset( $overrides[ $date ] ); // Tidy away the past.
		}
	}
	foreach ( (array) ( $in['grid'] ?? array() ) as $date => $cells ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			continue;
		}
		$row = array();
		if ( ! empty( $cells['closed'] ) ) {
			$row['closed'] = 1;
		}
		foreach ( (array) $cells as $t => $cap ) {
			if ( preg_match( '/^\d{2}:\d{2}$/', $t ) && '' !== $cap && (int) $cap !== $s['capacity'] ) {
				$row[ $t ] = max( 0, min( 50, (int) $cap ) );
			}
		}
		if ( $row ) {
			$overrides[ $date ] = $row;
		} else {
			unset( $overrides[ $date ] );
		}
	}
	update_option( 'gm_slot_overrides', $overrides );

	do_action( 'litespeed_purge_all' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=gm_order&page=gm-schedule&saved=1' ) );
	exit;
} );

function gm_render_schedule_page() {
	$s      = gm_schedule();
	$o      = gm_slot_overrides();
	$starts = gm_slot_starts();
	$names  = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
	$today  = gm_now()->setTime( 0, 0 );
	$ahead  = max( 14, (int) gm_setting( 'days_ahead' ) );
	$counts = gm_slot_counts( $today->modify( '+1 day' )->format( 'Y-m-d' ), $today->modify( '+' . $ahead . ' days' )->format( 'Y-m-d' ) );
	?>
	<div class="wrap">
		<h1>Opening times &amp; delivery slots</h1>
		<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Saved. The website is updated.</p></div>
		<?php endif; ?>
		<style>
			.gm-grid{border-collapse:collapse;background:#fff;margin-top:8px}
			.gm-grid th,.gm-grid td{border:1px solid #dcdcde;padding:6px 8px;text-align:center;font-size:13px}
			.gm-grid th:first-child,.gm-grid td:first-child{text-align:left;white-space:nowrap}
			.gm-grid input[type=number]{width:52px}
			.gm-grid .gm-booked{display:block;font-size:11px;color:#2271b1}
			.gm-grid .gm-full{color:#b32d2e;font-weight:600}
			.gm-grid tr.gm-off td{background:#f6f7f7;color:#8c8f94}
			.gm-grid .gm-changed{background:#fcf9e8}
			.gm-grid .gm-blocked{background:#fcf0f1}
			.gm-days label{display:inline-block;margin:0 14px 6px 0}
			.gm-scroll{overflow-x:auto;max-width:100%}
		</style>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gm_save_schedule">
			<?php wp_nonce_field( 'gm_save_schedule' ); ?>

			<h2>Regular opening times</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Delivery days</th>
					<td class="gm-days">
						<?php foreach ( array( 1, 2, 3, 4, 5, 6, 0 ) as $d ) : ?>
							<label><input type="checkbox" name="open_days[]" value="<?php echo (int) $d; ?>" <?php checked( in_array( $d, array_map( 'intval', (array) $s['open_days'] ), true ) ); ?>> <?php echo esc_html( $names[ $d ] ); ?></label>
						<?php endforeach; ?>
					</td></tr>
				<tr><th scope="row"><label for="gm-open">Opening time</label></th>
					<td><input type="time" id="gm-open" name="open" value="<?php echo esc_attr( $s['open'] ); ?>" step="300"> &nbsp; <label for="gm-close"><strong>Closing time</strong></label> <input type="time" id="gm-close" name="close" value="<?php echo esc_attr( $s['close'] ); ?>" step="300">
					<p class="description">Shown on the site as “Deliveries <?php echo esc_html( gm_hours_text() ); ?>, <?php echo esc_html( gm_days_text() ); ?>”. The last slot starts one slot-length before closing.</p></td></tr>
				<tr><th scope="row"><label for="gm-every">A new slot every</label></th>
					<td><select id="gm-every" name="every"><?php foreach ( array( 15, 20, 30, 45, 60 ) as $m ) : ?><option value="<?php echo (int) $m; ?>" <?php selected( (int) $s['every'], $m ); ?>><?php echo (int) $m; ?> minutes</option><?php endforeach; ?></select>
					&nbsp; <label for="gm-window"><strong>Delivery window</strong></label> <input type="number" id="gm-window" name="window" min="5" max="120" value="<?php echo (int) $s['window']; ?>" style="width:5em"> minutes
					<p class="description">Currently: <?php echo esc_html( implode( ', ', array_map( 'gm_time_label', $starts ) ) ); ?></p></td></tr>
				<tr><th scope="row"><label for="gm-capacity">Orders per slot</label></th>
					<td><input type="number" id="gm-capacity" name="capacity" min="0" max="50" value="<?php echo (int) $s['capacity']; ?>" style="width:5em">
					<p class="description">How many orders each slot can take as standard. Change individual slots in the grid below.</p></td></tr>
				<tr><th scope="row"><label for="gm-cutoff">Order deadline</label></th>
					<td><select id="gm-cutoff" name="cutoff"><?php for ( $h = 0; $h <= 23; $h++ ) : ?><option value="<?php echo (int) $h; ?>" <?php selected( (int) $s['cutoff'], $h ); ?>><?php echo esc_html( gm_time_label( gm_hhmm( $h * 60 ), true ) ); ?></option><?php endfor; ?></select> the day before delivery</td></tr>
				<tr><th scope="row"><label for="gm-closed">Closed dates</label></th>
					<td><textarea id="gm-closed" name="closed_dates" rows="5" cols="40" placeholder="25/12/2026&#10;31/12/2026 - 02/01/2027"><?php echo esc_textarea( $s['closed_dates'] ); ?></textarea>
					<p class="description">Holidays and days off — one date per line (e.g. <code>25/12/2026</code>) or a range (<code>31/12/2026 - 02/01/2027</code>).</p></td></tr>
			</table>

			<h2>Delivery slots — next <?php echo (int) $ahead; ?> days</h2>
			<p>Each box is how many orders that slot can take: raise it to take more, set <strong>0</strong> to block the time. Tick <strong>Closed</strong> to shut a whole day. Changes only affect that date; the regular times above stay as they are.</p>
			<?php if ( ! $starts ) : ?>
				<p><em>Set opening and closing times above to see the slots.</em></p>
			<?php else : ?>
			<div class="gm-scroll">
				<table class="gm-grid">
					<thead><tr><th>Date</th><th>Closed</th><?php foreach ( $starts as $t ) : ?><th><?php echo esc_html( gm_time_label( $t ) ); ?></th><?php endforeach; ?></tr></thead>
					<tbody>
					<?php
					for ( $i = 1; $i <= $ahead; $i++ ) :
						$day      = $today->modify( '+' . $i . ' days' );
						$date     = $day->format( 'Y-m-d' );
						$regular  = in_array( (int) $day->format( 'w' ), array_map( 'intval', (array) $s['open_days'] ), true ) && ! isset( gm_closed_dates()[ $date ] );
						$day_shut = ! empty( $o[ $date ]['closed'] );
						?>
						<tr class="<?php echo ( ! $regular || $day_shut ) ? 'gm-off' : ''; ?>">
							<td><strong><?php echo esc_html( $day->format( 'D j M' ) ); ?></strong><?php echo $regular ? '' : '<br><small>' . ( isset( gm_closed_dates()[ $date ] ) ? 'Closed date' : 'Not a delivery day' ) . '</small>'; ?></td>
							<td><?php if ( $regular ) : ?><input type="checkbox" name="grid[<?php echo esc_attr( $date ); ?>][closed]" value="1" <?php checked( $day_shut ); ?> aria-label="Close <?php echo esc_attr( $day->format( 'l j F' ) ); ?>"><?php else : ?>—<?php endif; ?></td>
							<?php
							foreach ( $starts as $t ) :
								$cap    = gm_slot_capacity( $date, $t );
								$booked = (int) ( $counts[ $date ][ $t ] ?? 0 );
								$class  = isset( $o[ $date ][ $t ] ) ? ( 0 === $cap ? 'gm-blocked' : 'gm-changed' ) : '';
								?>
								<td class="<?php echo esc_attr( $class ); ?>">
									<?php if ( $regular ) : ?>
										<input type="number" min="0" max="50" name="grid[<?php echo esc_attr( $date ); ?>][<?php echo esc_attr( $t ); ?>]" value="<?php echo (int) $cap; ?>" aria-label="<?php echo esc_attr( $day->format( 'D j M' ) . ' ' . gm_time_label( $t ) ); ?>">
									<?php else : ?>—<?php endif; ?>
									<?php if ( $booked ) : ?><span class="gm-booked <?php echo $booked >= $cap ? 'gm-full' : ''; ?>"><?php echo (int) $booked; ?> booked</span><?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endfor; ?>
					</tbody>
				</table>
			</div>
			<p class="description">Yellow = changed from the regular number; red = blocked.</p>
			<?php endif; ?>

			<?php submit_button( 'Save opening times & slots' ); ?>
		</form>
	</div>
	<?php
}
