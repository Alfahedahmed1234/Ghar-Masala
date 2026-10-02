<?php
/**
 * Everything that is not the front page: blog posts (shown as News), ordinary
 * pages such as the privacy policy, archives and 404s.
 */

get_header();
get_template_part( 'template-parts/topbar' );
?>

<main id="gm-main" class="gm-wrap gm-page gm-content">
	<?php if ( is_404() ) : ?>
		<h1 class="gm-title">Page not found</h1>
		<p class="gm-lede">That page has moved or never existed. <a href="<?php echo esc_url( gm_view_url( 'menu' ) ); ?>">See the menu</a> or go back to the <a href="<?php echo esc_url( gm_view_url() ); ?>">home page</a>.</p>

	<?php elseif ( is_singular() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<?php if ( is_single() ) : ?><p class="gm-eyebrow"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></p><?php endif; ?>
				<h1 class="gm-title"><?php the_title(); ?></h1>
				<div class="gm-entry"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>

	<?php else : ?>
		<h1 class="gm-title"><?php echo is_home() ? 'News' : esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		<div class="gm-news">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article>
					<p class="gm-news__date"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></p>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php endif; ?>
</main>

<?php
get_template_part( 'template-parts/site-footer' );
get_footer();
