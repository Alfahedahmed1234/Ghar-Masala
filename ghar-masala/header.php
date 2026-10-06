<?php
/**
 * Document head and opening <body>.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#006a4e">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="gm-skip" href="#gm-main">Skip to content</a>
<?php gm_render_banners( 'top' ); ?>
<?php if ( is_front_page() ) : ?><div class="gm-countdown" data-gm-countdown hidden></div><?php endif; ?>
