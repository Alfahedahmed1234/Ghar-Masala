<?php
/**
 * Contact / hours / links footer.
 */
$gm_phone = gm_setting( 'phone' );
$gm_email = gm_setting( 'email' );
?>
<footer class="gm-footer" data-gm-footer>
	<div class="gm-footer__grid">
		<div>
			<h5 class="gm-footer__head">Contact</h5>
			<div class="gm-footer__list">
				<a href="tel:+<?php echo esc_attr( gm_phone_intl() ); ?>"><?php echo esc_html( $gm_phone ); ?></a>
				<?php if ( $gm_email ) : ?><a href="mailto:<?php echo esc_attr( $gm_email ); ?>"><?php echo esc_html( $gm_email ); ?></a><?php endif; ?>
			</div>
			<?php get_template_part( 'template-parts/social' ); ?>
		</div>
		<div>
			<h5 class="gm-footer__head">Hours</h5>
			<div class="gm-footer__list">
				<span>Deliveries 6–10pm, Sun–Thu</span>
				<span class="gm-muted">Order by 7pm the day before</span>
			</div>
		</div>
		<div>
			<h5 class="gm-footer__head">Menu</h5>
			<div class="gm-footer__list">
				<a href="<?php echo esc_url( gm_view_url( 'menu' ) ); ?>">Full menu</a>
				<a href="<?php echo esc_url( gm_view_url( 'allergens' ) ); ?>">Allergens</a>
				<a href="<?php echo esc_url( gm_view_url( 'how' ) ); ?>">How ordering works</a>
			</div>
		</div>
		<div>
			<h5 class="gm-footer__head">About</h5>
			<div class="gm-footer__list">
				<a href="<?php echo esc_url( gm_view_url( 'story' ) ); ?>">My story</a>
				<a href="<?php echo esc_url( gm_view_url( 'reviews' ) ); ?>">Reviews</a>
				<a href="<?php echo esc_url( gm_view_url( 'news' ) ); ?>">News</a>
				<a href="<?php echo esc_url( gm_view_url( 'faq' ) ); ?>">FAQs</a>
				<a href="<?php echo esc_url( gm_view_url( 'contact' ) ); ?>">Contact us</a>
				<?php if ( get_privacy_policy_url() ) : ?><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy policy</a><?php endif; ?>
			</div>
		</div>
	</div>
	<div class="gm-footer__legal">
		<p class="gm-allergen-note"><?php echo gm_allergen_note(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in gm_allergen_note(). ?></p>
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Ghar Masala Ltd · company no. 16189818 · Tividale, Oldbury B69 · <?php echo esc_html( lcfirst( rtrim( gm_delivery_summary(), '.' ) ) ); ?></p>
	</div>
</footer>
