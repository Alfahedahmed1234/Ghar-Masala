<?php
/**
 * Main navigation + basket. Used in the home-page hero ($args['variant'] = 'hero')
 * and in the green bar on every other page ('bar').
 *
 * Desktop: "Home" and "Menu" open their sub-links on hover (or keyboard focus).
 * Phones: the ☰ button opens the whole list. The basket opens a mini basket on
 * hover, or on tap where there is no mouse.
 */
$gm_variant = $args['variant'] ?? 'bar';
$gm_id      = 'gm-nav-' . $gm_variant;
$gm_home    = gm_is_app() ? '' : gm_view_url();
// Sections with their own address link there (good for Google); app.js opens them without a page load.
$gm_link    = function ( $view ) use ( $gm_home ) {
	$url = gm_seo_url( $view );
	return $url ? $url : $gm_home . '#' . $view;
};
?>
<nav class="gm-nav gm-nav--<?php echo esc_attr( $gm_variant ); ?>" aria-label="Main" data-gm-nav>
	<button type="button" class="gm-nav__toggle" data-gm-navtoggle aria-expanded="false" aria-controls="<?php echo esc_attr( $gm_id ); ?>">
		<span class="gm-burger" aria-hidden="true"><i></i><i></i><i></i></span><span class="gm-nav__toggle-label">Menu</span>
	</button>
	<ul class="gm-nav__list" id="<?php echo esc_attr( $gm_id ); ?>">
		<li class="gm-nav__item gm-nav__item--sub">
			<a href="<?php echo esc_url( gm_is_app() ? home_url( '/' ) : gm_view_url() ); ?>" data-nav="home">Home</a>
			<button type="button" class="gm-nav__caret" data-gm-subtoggle aria-expanded="false" aria-label="More Home links"><svg viewBox="0 0 12 12" width="10" height="10" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
			<ul class="gm-nav__sub">
				<li><a href="<?php echo esc_url( $gm_link( 'story' ) ); ?>" data-nav="story">My story</a></li>
				<li><a href="<?php echo esc_url( $gm_link( 'news' ) ); ?>" data-nav="news">News</a></li>
				<li><a href="<?php echo esc_url( $gm_link( 'reviews' ) ); ?>" data-nav="reviews">Reviews</a></li>
				<li><a href="<?php echo esc_url( $gm_link( 'faq' ) ); ?>" data-nav="faq">FAQs</a></li>
			</ul>
		</li>
		<li class="gm-nav__item gm-nav__item--sub">
			<a href="<?php echo esc_url( $gm_link( 'menu' ) ); ?>" data-nav="menu">Menu</a>
			<button type="button" class="gm-nav__caret" data-gm-subtoggle aria-expanded="false" aria-label="More Menu links"><svg viewBox="0 0 12 12" width="10" height="10" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button>
			<ul class="gm-nav__sub">
				<li><a href="<?php echo esc_url( $gm_link( 'allergens' ) ); ?>" data-nav="allergens">Allergens</a></li>
				<li><a href="<?php echo esc_url( $gm_link( 'how' ) ); ?>" data-nav="how">How it works</a></li>
			</ul>
		</li>
		<li class="gm-nav__item">
			<a href="<?php echo esc_url( $gm_home . '#account' ); ?>" data-nav="account">My account</a>
		</li>
		<li class="gm-nav__item">
			<a href="<?php echo esc_url( $gm_link( 'contact' ) ); ?>" data-nav="contact">Contact us</a>
		</li>
	</ul>
	<div class="gm-basket" data-gm-basketwrap>
		<a class="gm-nav__order" href="<?php echo esc_url( $gm_home . '#order' ); ?>" data-gm-baskettoggle aria-expanded="false" aria-haspopup="true">
			<span class="gm-basket__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18l-1.6 9.2a2 2 0 0 1-2 1.7H6.6a2 2 0 0 1-2-1.7L3 9z"/><path d="M8.5 9l3-5.5M15.5 9l-3-5.5"/><path d="M9 13v3.5M12 13v3.5M15 13v3.5"/></svg>
				<span class="gm-basket__count gm-tnum" data-gm-badge hidden>0</span>
			</span>
			<span class="gm-nav__order-label">Your order</span>
		</a>
		<div class="gm-mini" data-gm-mini hidden></div>
	</div>
</nav>
