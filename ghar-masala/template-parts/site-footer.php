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
			<div class="gm-social">
				<a href="https://www.facebook.com/gharmasala" target="_blank" rel="noopener" aria-label="Facebook" title="Facebook"><svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.2h2.5l.4-2.9h-2.9V9.05c0-.84.24-1.42 1.45-1.42h1.55V5.04C16.68 5 15.9 4.94 15 4.94c-2.2 0-3.7 1.34-3.7 3.8v2.16H8.8v2.9h2.5V21h2.2z"/></svg></a>
				<a href="https://www.instagram.com/gharmasala" target="_blank" rel="noopener" aria-label="Instagram" title="Instagram"><svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M12 6.9A5.1 5.1 0 1 0 17.1 12 5.1 5.1 0 0 0 12 6.9zm0 8.42A3.32 3.32 0 1 1 15.32 12 3.32 3.32 0 0 1 12 15.32zM17.3 5.55a1.19 1.19 0 1 0 1.19 1.19 1.19 1.19 0 0 0-1.19-1.19zM16.2 3H7.8A4.8 4.8 0 0 0 3 7.8v8.4A4.8 4.8 0 0 0 7.8 21h8.4a4.8 4.8 0 0 0 4.8-4.8V7.8A4.8 4.8 0 0 0 16.2 3zm3 13.2a3 3 0 0 1-3 3H7.8a3 3 0 0 1-3-3V7.8a3 3 0 0 1 3-3h8.4a3 3 0 0 1 3 3z"/></svg></a>
				<a href="https://wa.me/<?php echo esc_attr( gm_phone_intl() ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp"><svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.79.96-.96 1.16-.18.2-.35.22-.65.07-.3-.15-1.13-.42-2.15-1.33-.79-.71-1.32-1.58-1.47-1.88-.15-.3-.02-.47.13-.62.15-.15.35-.4.52-.6.13-.16.2-.28.3-.48.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.21-.24-.58-.48-.5-.66-.51h-.56c-.2 0-.5.07-.77.37-.27.3-1.02.99-1.02 2.41 0 1.43 1.04 2.8 1.19 3 .15.2 2.05 3.2 5.02 4.37.7.3 1.25.48 1.68.61.71.22 1.35.19 1.86.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12 22a9.9 9.9 0 0 1-5.05-1.38L3 22l1.42-3.86A9.9 9.9 0 0 1 2.1 12 9.9 9.9 0 1 1 12 22zm0-1.9a8 8 0 0 0 6.66-12.44A8 8 0 0 0 5.34 16.2l.24.37-.84 2.28 2.35-.8.36.22A7.96 7.96 0 0 0 12 20.1z"/></svg></a>
			</div>
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
				<a href="<?php echo esc_url( gm_view_url( 'testimonials' ) ); ?>">Testimonials</a>
				<a href="<?php echo esc_url( gm_view_url( 'news' ) ); ?>">News</a>
				<a href="<?php echo esc_url( gm_view_url( 'faq' ) ); ?>">FAQs</a>
				<?php if ( get_privacy_policy_url() ) : ?><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy policy</a><?php endif; ?>
			</div>
		</div>
	</div>
	<div class="gm-footer__legal">
		<p class="gm-allergen-note"><?php echo gm_allergen_note(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in gm_allergen_note(). ?></p>
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> Ghar Masala Ltd · company no. 16189818 · Tividale, Oldbury B69 · <?php echo esc_html( lcfirst( rtrim( gm_delivery_summary(), '.' ) ) ); ?></p>
	</div>
</footer>
