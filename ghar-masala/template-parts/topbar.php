<?php
/**
 * Sticky green header used on every inner page.
 */
?>
<header class="gm-topbar" data-gm-topbar>
	<div class="gm-topbar__inner">
		<a class="gm-logo" href="<?php echo esc_url( gm_view_url() ); ?>"><img src="<?php echo esc_url( gm_logo_url() ); ?>" alt="Ghar Masala — tradition served with comfort" width="79" height="48"></a>
		<?php get_template_part( 'template-parts/halal' ); ?>
		<?php get_template_part( 'template-parts/nav', null, array( 'variant' => 'bar' ) ); ?>
	</div>
</header>
