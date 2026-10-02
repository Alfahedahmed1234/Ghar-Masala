<?php
/**
 * Sticky green header used on every inner page.
 */
?>
<header class="gm-topbar" data-gm-topbar>
	<div class="gm-topbar__inner">
		<a class="gm-logo" href="<?php echo esc_url( gm_view_url() ); ?>"><img src="<?php echo esc_url( gm_logo_url() ); ?>" alt="Ghar Masala — tradition served with comfort" width="79" height="48"></a>
		<?php get_template_part( 'template-parts/halal' ); ?>
		<nav class="gm-nav" aria-label="Main">
			<a href="<?php echo esc_url( gm_view_url() ); ?>" data-nav="home">Home</a>
			<a href="<?php echo esc_url( gm_view_url( 'menu' ) ); ?>" data-nav="menu">Menu</a>
			<a href="<?php echo esc_url( gm_view_url( 'how' ) ); ?>" data-nav="how">How it works</a>
			<a href="<?php echo esc_url( gm_view_url( 'story' ) ); ?>" data-nav="story">My story</a>
			<a href="<?php echo esc_url( gm_view_url( 'account' ) ); ?>" data-nav="account"><?php echo is_user_logged_in() ? 'My account' : 'Sign in'; ?></a>
			<a class="gm-nav__order" href="<?php echo esc_url( gm_view_url( 'order' ) ); ?>">Your order<span class="gm-tnum" data-gm-badge></span></a>
		</nav>
	</div>
</header>
