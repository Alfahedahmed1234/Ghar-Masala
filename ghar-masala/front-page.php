<?php
/**
 * The Ghar Masala site: every "page" is a view on the front page, switched by
 * the URL hash (#menu, #story, …) in assets/js/app.js.
 */

get_header();

$gm_phone     = gm_setting( 'phone' );
$gm_tel       = 'tel:+' . gm_phone_intl();
$gm_logo      = gm_logo_url();
$gm_user      = wp_get_current_user();
$gm_signed_in = is_user_logged_in();
$gm_hours     = gm_hours_text();                       // e.g. 6–10pm
$gm_days      = gm_days_text();                        // e.g. Sunday to Thursday
$gm_cut       = gm_cutoff_text();                      // e.g. 7pm the day before
$gm_win       = (int) gm_schedule()['window'];         // e.g. 15
$gm_every     = (int) gm_schedule()['every'];          // e.g. 30
?>

<?php get_template_part( 'template-parts/topbar' ); ?>

<main id="gm-main">

<?php /* ===================================================== Home */ ?>
<section class="gm-view" data-view="home">
	<div class="gm-hero" style="background-image:linear-gradient(180deg,rgba(0,29,22,.8) 0%,rgba(0,29,22,.52) 45%,rgba(0,29,22,.8) 100%),url('<?php echo esc_url( gm_asset( 'images/hero.jpg' ) ); ?>')">
		<header class="gm-hero__bar">
			<a class="gm-logo" href="<?php echo esc_url( gm_view_url() ); ?>"><img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala — tradition served with comfort" width="106" height="64"></a>
			<?php get_template_part( 'template-parts/halal' ); ?>
			<?php get_template_part( 'template-parts/nav', null, array( 'variant' => 'hero' ) ); ?>
		</header>
		<div class="gm-hero__body">
			<span class="gm-hero__kicker">Tividale · Oldbury · Fresh to order</span>
			<h1 class="gm-hero__title">Good food starts in the Kitchen.</h1>
			<div class="gm-hero__ctas">
				<a class="gm-hero__cta gm-hero__cta--main" href="#menu">Order now</a>
				<a class="gm-hero__cta" href="#how">How it works</a>
			</div>
			<div class="gm-hero__facts">
				<div><p class="gm-hero__fact"><?php echo esc_html( $gm_win ); ?> min</p><p class="gm-hero__fact-label">Delivery window</p></div>
				<div><p class="gm-hero__fact">£10</p><p class="gm-hero__fact-label">Minimum spend</p></div>
				<div><p class="gm-hero__fact">2 miles</p><p class="gm-hero__fact-label">Free radius from Tividale Viewpoint</p></div>
			</div>
		</div>
	</div>
</section>

<?php /* ===================================================== My story */ ?>
<section class="gm-view" data-view="story" hidden>
	<div class="gm-wrap gm-page-head">
		<span class="gm-eyebrow">My story</span>
		<h1 class="gm-title" style="max-width:20ch"><strong>Ghar Masala</strong>… it means House of Spice.</h1>
	</div>
	<figure class="gm-story__wide">
		<img src="<?php echo esc_url( gm_asset( 'images/spices.jpg' ) ); ?>" alt="Turmeric, chilli powder and saffron in wooden spoons" loading="lazy" width="1600" height="1097">
		<figcaption class="gm-wrap">Spices blended in the kitchen, not bought in as paste.</figcaption>
	</figure>
	<div class="gm-wrap gm-story__body">
		<div class="gm-prose">
			<p>I was born and raised in the UK, in a Bengali household with Bangladeshi heritage. Food was how culture got passed down: how the family talked to each other, how guests were welcomed, how a bad day was fixed. Nothing about it was fussy. A curry, a pot of rice, something fried if people were lucky.</p>
			<p>Years later I was working in a family restaurant, and the same question kept coming back over the counter — <em>can I buy the curry you cook at home?</em> Not the heavy, glossy version on the menu. The everyday one. There was a gap sitting in plain sight, and I decided to cook for it.</p>
		</div>
		<blockquote class="gm-quote"><p>Tradition served with comfort. It is on the logo because it is the whole brief.</p></blockquote>
		<div class="gm-split">
			<div class="gm-prose">
				<p>So Ghar Masala runs on pre-orders only. You book a slot, I shop for that evening, and everything is cooked on the day it goes out — spices blended here, meat marinated overnight, no vats of sauce sitting around. It is why the menu is short, and why I am not trying to sell you everything.</p>
				<p>I tested it the honest way: leaflets through doors in Tividale, cooking for neighbours and strangers, listening to what came back. People told me the food tasted like someone's kitchen rather than a takeaway, and that the portions were generous. That was the moment it stopped being an idea.</p>
				<p>The person who cooks your order is the person who drives it to your door. For now that is the whole business, and I would rather it stayed that close for as long as it can.</p>
				<p class="gm-signoff">Ahmed — founder, Ghar Masala</p>
			</div>
			<figure>
				<img src="<?php echo esc_url( gm_asset( 'images/curry.jpg' ) ); ?>" alt="A home-style curry finished with fresh coriander" loading="lazy" width="1400" height="1176" style="aspect-ratio:5/4">
				<figcaption>Cooked on the day of delivery — never held over.</figcaption>
			</figure>
		</div>
		<div class="gm-cta-row">
			<a class="gm-btn" href="#menu">See the menu</a>
			<p class="gm-muted">Deliveries <?php echo esc_html( $gm_hours ); ?>, <?php echo esc_html( $gm_days ); ?> · Order by <?php echo esc_html( $gm_cut ); ?></p>
		</div>
	</div>
</section>

<?php /* ===================================================== Reviews */ ?>
<section class="gm-view" data-view="reviews" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">Reviews</h1>
		<?php $gm_testimonials = gm_testimonials(); ?>
		<?php if ( $gm_testimonials ) : ?>
			<p class="gm-lede">What people who have ordered from Ghar Masala say about the food.</p>
			<div class="gm-quotes">
				<?php foreach ( $gm_testimonials as $gm_t ) : ?>
					<figure class="gm-quotes__item">
						<?php $gm_stars = (int) get_post_meta( $gm_t->ID, '_gm_rating', true ); ?>
						<?php if ( $gm_stars ) : ?><p class="gm-stars" aria-label="<?php echo esc_attr( $gm_stars ); ?> out of 5 stars"><?php echo esc_html( str_repeat( '★', $gm_stars ) . str_repeat( '☆', 5 - $gm_stars ) ); ?></p><?php endif; ?>
						<blockquote class="gm-quotes__text"><?php echo wp_kses_post( wpautop( '“' . trim( trim( $gm_t->post_content ), '"“”' ) . '”' ) ); ?></blockquote>
						<figcaption class="gm-quotes__by">— <?php echo esc_html( get_the_title( $gm_t ) ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="gm-lede">Reviews from people who have ordered from Ghar Masala will appear here soon. Ordered from us? We would love to hear what you thought.</p>
		<?php endif; ?>

		<div class="gm-cta-row" data-gm-review-cta>
			<button type="button" class="gm-btn" data-gm-open-review>Leave a review</button>
		</div>
		<div class="gm-review" data-gm-review-box hidden>
			<h2 class="gm-subtitle">Leave a review</h2>
			<p class="gm-soft">Thank you for taking the time. Reviews appear on this page once we have checked them.</p>
			<form class="gm-form gm-form--narrow" data-gm-review novalidate>
				<div class="gm-form__row">
					<div class="field"><label for="gm-rv-name">Your name</label><input class="input" id="gm-rv-name" name="name" type="text" autocomplete="name" required placeholder="e.g. Sarah"></div>
					<div class="field"><label for="gm-rv-area">Area (optional)</label><input class="input" id="gm-rv-area" name="area" type="text" placeholder="e.g. Tividale"></div>
				</div>
				<fieldset class="gm-rating">
					<legend>Your rating</legend>
					<?php for ( $gm_i = 5; $gm_i >= 1; $gm_i-- ) : ?>
						<input type="radio" id="gm-rv-star-<?php echo (int) $gm_i; ?>" name="rating" value="<?php echo (int) $gm_i; ?>" <?php checked( 5, $gm_i ); ?>><label for="gm-rv-star-<?php echo (int) $gm_i; ?>" title="<?php echo (int) $gm_i; ?> stars">★</label>
					<?php endfor; ?>
				</fieldset>
				<div class="field"><label for="gm-rv-text">Your review</label><textarea class="input" id="gm-rv-text" name="review" rows="5" maxlength="1000" required placeholder="What did you order, and how was it?"></textarea></div>
				<div class="field"><label for="gm-rv-email">Email (optional — not shown)</label><input class="input" id="gm-rv-email" name="email" type="email" autocomplete="email" placeholder="In case we want to say thank you"></div>
				<div class="gm-hp" aria-hidden="true"><label for="gm-rv-website">Website</label><input id="gm-rv-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
				<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
				<p class="gm-success" data-gm-auth-ok role="status" hidden></p>
				<button type="submit" class="gm-btn">Send my review</button>
			</form>
		</div>
	</div>
</section>

<?php /* ===================================================== News */ ?>
<section class="gm-view" data-view="news" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">News</h1>
		<p class="gm-lede">The Ghar Masala story so far — milestones and updates from the kitchen.</p>
		<?php $gm_news = gm_news_items(); ?>
		<?php if ( $gm_news ) : ?>
			<ol class="gm-timeline">
				<?php foreach ( $gm_news as $gm_n ) : ?>
					<li class="gm-timeline__item">
						<p class="gm-timeline__date"><?php echo esc_html( $gm_n['date_label'] ); ?></p>
						<div class="gm-timeline__body">
							<h3><?php echo esc_html( $gm_n['title'] ); ?></h3>
							<?php if ( $gm_n['image'] ) : ?><img class="gm-timeline__img" src="<?php echo esc_url( $gm_n['image'] ); ?>" alt="" loading="lazy"><?php endif; ?>
							<div class="gm-timeline__text"><?php echo wp_kses_post( wpautop( $gm_n['text'] ) ); ?></div>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<p class="gm-muted" style="margin-top:28px">News coming soon.</p>
		<?php endif; ?>
	</div>
</section>

<?php /* ===================================================== Contact */ ?>
<section class="gm-view" data-view="contact" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">Contact us</h1>
		<p class="gm-lede">Questions about the menu, allergies, catering or an order? Get in touch — we usually reply the same day.</p>
		<div class="gm-split gm-contact">
			<div>
				<div class="gm-facts gm-contact__facts">
					<div><span>Phone</span><a href="<?php echo esc_attr( $gm_tel ); ?>"><?php echo esc_html( $gm_phone ); ?></a></div>
					<?php if ( gm_setting( 'email' ) ) : ?><div><span>Email</span><a href="mailto:<?php echo esc_attr( gm_setting( 'email' ) ); ?>"><?php echo esc_html( gm_setting( 'email' ) ); ?></a></div><?php endif; ?>
					<div><span>Website</span><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( home_url() ) ) ); ?></a></div>
					<div><span>Deliveries</span><span><?php echo esc_html( $gm_hours . ', ' . $gm_days ); ?></span></div>
					<div><span>Area</span><span>Tividale, Oldbury B69</span></div>
				</div>
				<h2 class="gm-label" style="margin-top:28px">Follow us</h2>
				<?php get_template_part( 'template-parts/social' ); ?>
			</div>
			<div class="gm-panel">
				<h2 class="gm-label">Send us a message</h2>
				<form class="gm-form" data-gm-contact novalidate>
					<div class="field"><label for="gm-ct-name">Name</label><input class="input" id="gm-ct-name" name="name" type="text" autocomplete="name" required></div>
					<div class="field"><label for="gm-ct-phone">Contact number</label><input class="input" id="gm-ct-phone" name="phone" type="tel" autocomplete="tel" required placeholder="07…"></div>
					<div class="field"><label for="gm-ct-email">Email (optional)</label><input class="input" id="gm-ct-email" name="email" type="email" autocomplete="email"></div>
					<div class="field"><label for="gm-ct-msg">Your message (optional)</label><textarea class="input" id="gm-ct-msg" name="message" rows="5" maxlength="2000"></textarea></div>
					<div class="gm-hp" aria-hidden="true"><label for="gm-ct-website">Website</label><input id="gm-ct-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
					<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
					<p class="gm-success" data-gm-auth-ok role="status" hidden></p>
					<button type="submit" class="gm-btn gm-btn--block">Send message</button>
				</form>
			</div>
		</div>
	</div>
</section>

<?php /* ===================================================== FAQs */ ?>
<section class="gm-view" data-view="faq" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">Frequently asked questions</h1>
		<p class="gm-lede">Can't find what you need? Ring the kitchen on <a href="<?php echo esc_attr( $gm_tel ); ?>"><?php echo esc_html( $gm_phone ); ?></a>.</p>
		<div class="gm-faq">
			<?php
			$gm_faqs = array(
				'What makes you different?'                     => 'Fresh ingredients, cooked on the day of delivery — the pre-order method makes that possible. No base sauce, and nothing ultra-processed like others.',
				'How do I place an order?'                      => 'Build your basket on the Menu page, then book a delivery slot — that takes you straight to checkout.',
				'How far ahead can I order?'                    => 'Up to ' . (int) gm_setting( 'days_ahead' ) . ' days ahead. Orders for a given evening must be placed by ' . $gm_cut . '.',
				'Is there a minimum order?'                     => 'Yes, £10. Delivery is ' . lcfirst( gm_delivery_summary() ) . ' Your charge is worked out from your postcode at checkout.',
				'What are your delivery hours?'                 => 'Deliveries run ' . $gm_hours . ', ' . $gm_days . '. Each slot gives you a ' . $gm_win . '-minute delivery window.',
				'Is the food halal?'                            => 'Yes — look for the حلال mark in the header on every page.',
				'Can I choose how spicy my curry is?'           => 'Yes — curries marked “spice to order” have a spice-level choice in your basket. Madras and Vindaloo are 30p extra.',
				'Do you cater for allergies?'                   => 'Check the allergen table before you order and add a note at checkout — we will always talk it through with you.',
				'Can I change or cancel an order after paying?' => 'Ring the kitchen as soon as you can on ' . $gm_phone . ' — we can usually help if your slot has not started yet.',
			);
			foreach ( $gm_faqs as $q => $a ) :
				?>
				<details>
					<summary><?php echo esc_html( $q ); ?><span aria-hidden="true">+</span></summary>
					<p><?php echo esc_html( $a ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php /* ===================================================== Menu + order */ ?>
<section class="gm-view" data-view="menu" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">Menu</h1>
		<p class="gm-lede">Curries marked “spice to order” can be made as hot as you like — choose the spice level in your basket. Madras and Vindaloo are 30p extra.</p>

		<div class="gm-key">
			<p class="gm-key__title">Menu key</p>
			<div class="gm-key__grid">
				<div class="gm-key__cell">
					<h4 class="gm-label">Spice levels</h4>
					<div class="gm-spice">
						<?php foreach ( gm_spice_levels() as $gm_level => $gm_spice ) : ?>
							<div><?php echo gm_spice_dots( $gm_level ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup. ?><?php echo esc_html( $gm_spice['label'] ); ?><?php if ( $gm_spice['extra'] ) : ?><span class="gm-muted">&nbsp;· <?php echo esc_html( $gm_spice['extra'] ); ?></span><?php endif; ?></div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="gm-key__cell">
					<h4 class="gm-label">Dietary</h4>
					<div class="gm-key__diet">
						<span class="gm-veg">V</span><span>Vegetarian</span>
						<span class="gm-rec">Recommended</span><span>Our pick</span>
					</div>
					<p class="gm-small gm-muted">Vegan on request where the dish allows — ask when you order.</p>
				</div>
				<div class="gm-key__cell gm-key__cell--warn">
					<h4 class="gm-label">Allergies — please read</h4>
					<p>Some dishes contain allergens. Check the table before you order and add a note at checkout — we will always talk it through with you.</p>
					<a class="gm-btn gm-btn--warn" href="#allergens">See the allergen table</a>
				</div>
			</div>
		</div>

		<div class="gm-notice" data-gm-notice hidden></div>

		<div class="gm-menu">
			<?php foreach ( gm_menu() as $group ) : ?>
				<div>
					<div class="gm-menu__head">
						<h3><?php echo esc_html( $group['title'] ); ?></h3>
						<p><?php echo esc_html( $group['note'] ); ?></p>
					</div>
					<?php foreach ( $group['items'] as $item ) : ?>
						<div class="gm-dish">
							<span class="gm-dish__info">
								<span class="gm-dish__name"><?php echo esc_html( $item['name'] ); ?></span>
								<?php $gm_dish_spice = gm_spice_levels()[ $item['spice'] ?? '' ] ?? null; ?>
								<?php if ( ! empty( $item['rec'] ) || ! empty( $item['veg'] ) || ! empty( $item['adjustable'] ) || $gm_dish_spice ) : ?>
									<span class="gm-dish__meta">
										<?php if ( $gm_dish_spice ) : ?><span class="gm-dish__spice" title="Standard spice level: <?php echo esc_attr( $gm_dish_spice['label'] ); ?>"><?php echo gm_spice_dots( $item['spice'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup. ?><?php echo esc_html( $gm_dish_spice['short'] ); ?></span><?php endif; ?>
										<?php if ( ! empty( $item['rec'] ) ) : ?><span class="gm-rec">Recommended</span><?php endif; ?>
										<?php if ( ! empty( $item['veg'] ) ) : ?><span class="gm-veg gm-veg--sm" title="Vegetarian">V</span><?php endif; ?>
										<?php if ( ! empty( $item['adjustable'] ) ) : ?><span class="gm-dish__adj">spice to order</span><?php endif; ?>
									</span>
								<?php endif; ?>
								<?php if ( ! empty( $item['desc'] ) ) : ?>
									<span class="gm-dish__desc"><?php echo esc_html( $item['desc'] ); ?></span>
								<?php endif; ?>
							</span>
							<span class="gm-dish__price gm-tnum"><?php echo esc_html( gm_money( (int) round( $item['price'] * 100 ) ) ); ?></span>
							<span class="gm-stepper" data-gm-stepper="<?php echo esc_attr( $item['id'] ); ?>">
								<button type="button" class="gm-stepper__btn" data-gm-dec="<?php echo esc_attr( $item['id'] ); ?>" aria-label="Remove one <?php echo esc_attr( $item['name'] ); ?>" disabled>−</button>
								<span class="gm-stepper__qty gm-tnum" data-gm-qty="<?php echo esc_attr( $item['id'] ); ?>" aria-live="polite">0</span>
								<button type="button" class="gm-stepper__btn" data-gm-inc="<?php echo esc_attr( $item['id'] ); ?>" aria-label="Add one <?php echo esc_attr( $item['name'] ); ?>">+</button>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div id="order" class="gm-order">
		<div class="gm-wrap gm-order__inner">
			<h2 class="gm-subtitle">Your order</h2>
			<div class="gm-order__grid">
				<div>
					<p class="gm-step">01 — Basket</p>
					<div data-gm-basket></div>
					<p class="gm-small gm-muted" style="margin-top:18px">Minimum £10 spend. Pick a spice level for curries above; allergy notes are taken at checkout.</p>
				</div>
				<div>
					<p class="gm-step">02 — Delivery slot</p>
					<p style="margin:0 0 22px">We deliver <?php echo esc_html( $gm_days ); ?>, <?php echo esc_html( $gm_hours ); ?>. Slots run every <?php echo esc_html( $gm_every ); ?> minutes and each gives you a <?php echo esc_html( $gm_win ); ?>-minute delivery window — once a slot is full, it closes to everyone else.</p>
					<div data-gm-slots></div>
				</div>
			</div>
		</div>
	</div>

</section>

<?php /* ===================================================== How it works */ ?>
<section class="gm-view" data-view="how" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title" style="max-width:24ch">How ordering works</h1>
		<p class="gm-lede">Every dish is cooked to order, so the kitchen works to booked slots rather than walk-ins. That means a fixed <?php echo esc_html( $gm_win ); ?>-minute delivery window and no guesswork about when your food arrives.</p>

		<div class="gm-steps">
			<?php
			$gm_pay_copy = gm_stripe_enabled()
				? 'Pay by card, Apple Pay or Google Pay at checkout' . ( gm_cod_enabled() ? ', or cash on delivery' . ( gm_cash_limit() ? ' for orders up to ' . gm_money( gm_cash_limit() ) : '' ) : '' ) . '. Your slot is held while you pay, and confirmed on screen the moment payment clears.'
				: 'Place your order at checkout and pay when the food arrives. Your slot is confirmed on screen straight away.';
			$gm_steps    = array(
				'Fill your basket'       => 'Choose your dishes and tell us the spice level you want. The minimum order is £10, and delivery is worked out from your postcode at checkout — ' . lcfirst( gm_delivery_summary() ),
				'Book a delivery slot'   => 'Slots run every ' . $gm_every . ' minutes, ' . $gm_hours . ', ' . $gm_days . ', and can be booked up to ' . (int) gm_setting( 'days_ahead' ) . ' days ahead. Orders close at ' . $gm_cut . '.',
				'Confirm your order'     => $gm_pay_copy,
				'Cooked, then delivered' => 'Everything is cooked on the day of delivery, packed into insulated bags and driven to you inside your ' . $gm_win . '-minute window.',
			);
			$n           = 0;
			foreach ( $gm_steps as $title => $copy ) :
				++$n;
				?>
				<div class="gm-steps__row">
					<p class="gm-steps__n gm-tnum"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></p>
					<h3><?php echo esc_html( $title ); ?></h3>
					<p><?php echo esc_html( $copy ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="delivery" class="gm-split gm-delivery">
			<div>
				<h2 class="gm-subtitle">Delivery</h2>
				<p class="gm-soft" style="max-width:46ch;margin:0 0 26px">We deliver ourselves, out of Tividale, <?php echo esc_html( $gm_days ); ?>, <?php echo esc_html( $gm_hours ); ?>. Because every order is booked against a slot, your food is timed to land when you asked for it.</p>
				<div class="gm-facts">
					<?php $gm_dr = gm_delivery_rules(); ?>
					<div><span>Within <?php echo esc_html( gm_number( $gm_dr['free_miles'] ) ); ?> miles of <?php echo esc_html( $gm_dr['area'] ); ?></span><span>Free</span></div>
					<div><span>Each extra mile</span><span><?php echo esc_html( gm_money( $gm_dr['per_mile'] ) ); ?></span></div>
					<div><span>Furthest we deliver</span><span><?php echo esc_html( gm_number( $gm_dr['max_miles'] ) ); ?> miles</span></div>
					<div><span>Minimum order</span><span>£10</span></div>
					<div><span>Delivery days</span><span><?php echo esc_html( ucfirst( $gm_days ) ); ?></span></div>
					<div><span>Delivery window</span><span><?php echo esc_html( $gm_win ); ?> minutes</span></div>
					<div><span>Order deadline</span><span><?php echo esc_html( $gm_cut ); ?></span></div>
					<div><span>Payment</span><span><?php echo esc_html( gm_stripe_enabled() ? ( gm_cod_enabled() ? 'Card, Apple Pay, Google Pay or cash' : 'Card, Apple Pay or Google Pay' ) : 'Cash on delivery' ); ?></span></div>
				</div>
				<p class="gm-small gm-muted" style="margin-top:20px">Tividale, Oldbury and the surrounding area. Enter your postcode at checkout and the delivery charge is worked out for you.</p>
				<a class="gm-btn" style="margin-top:30px" href="#menu">Build your order</a>
			</div>
			<figure class="gm-grayscale">
				<img src="<?php echo esc_url( gm_asset( 'images/curry-rice.jpg' ) ); ?>" alt="Curry, basmati rice and whole spices" loading="lazy" width="1400" height="2629" style="aspect-ratio:4/5">
			</figure>
		</div>
	</div>
</section>

<?php /* ===================================================== Allergens */ ?>
<section class="gm-view" data-view="allergens" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title" style="max-width:22ch">Allergen information</h1>
		<p class="gm-lede">The table lists the fourteen declarable allergens against every dish on our menu. If anything is unclear, add a note at checkout or ring the kitchen on <a href="<?php echo esc_attr( $gm_tel ); ?>"><?php echo esc_html( $gm_phone ); ?></a> before you order.</p>
		<div class="gm-legend">
			<span><span class="gm-mark gm-mark--y">Y</span>Contains this allergen</span>
			<span><span class="gm-mark gm-mark--p">P</span>May contain traces</span>
		</div>
		<div class="gm-allergens">
			<table>
				<thead>
					<tr>
						<th scope="col">Dish</th>
						<?php foreach ( gm_allergen_cols() as $label ) : ?><th scope="col"><?php echo esc_html( $label ); ?></th><?php endforeach; ?>
					</tr>
				</thead>
				<?php foreach ( gm_allergen_table() as $group ) : ?>
					<tbody>
						<tr class="gm-allergens__group"><th colspan="<?php echo count( gm_allergen_cols() ) + 1; ?>" scope="rowgroup"><?php echo esc_html( $group['title'] ); ?></th></tr>
						<?php foreach ( $group['rows'] as $row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $row['name'] ); ?></th>
								<?php foreach ( array_keys( gm_allergen_cols() ) as $key ) : ?>
									<td>
										<?php
										$mark = $row['marks'][ $key ] ?? '';
										if ( 'Y' === $mark ) {
											echo '<span class="gm-mark gm-mark--y" title="Contains">Y</span>';
										} elseif ( 'P' === $mark ) {
											echo '<span class="gm-mark gm-mark--p" title="May contain traces">P</span>';
										} else {
											echo '<span class="gm-mark--none" aria-label="Not used"></span>';
										}
										?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				<?php endforeach; ?>
			</table>
		</div>
		<p class="gm-small gm-muted" style="margin-top:14px">Scroll the table sideways to see every allergen. A dash means the allergen is not used in that dish.</p>
		<div class="gm-cta-row gm-cta-row--plain">
			<button type="button" class="gm-btn-outline gm-btn-outline--lg" onclick="window.print()">Print this table</button>
			<a class="gm-btn" href="#menu">Back to the menu</a>
		</div>
		<div class="gm-notice-block">
			<h2 class="gm-label">Allergen notice</h2>
			<p>Whilst we take great care in the preparation of our food, all dishes are prepared in an environment where allergens are present. As a result, we cannot guarantee the absence of allergen traces in any of our products.</p>
		</div>
	</div>
</section>

<?php /* ===================================================== Sign in */ ?>
<?php if ( ! $gm_signed_in ) : ?>
<section class="gm-view" data-view="login" hidden>
	<header class="gm-slimbar">
		<div class="gm-slimbar__inner">
			<a class="gm-logo" href="#"><img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala" width="76" height="46"></a>
			<a class="gm-slimbar__link" href="#">← Back to the site</a>
		</div>
	</header>
	<div class="gm-narrow gm-split gm-login">
		<div>
			<h1 class="gm-title gm-title--md">My account</h1>
			<p class="gm-lede" style="max-width:44ch">Sign in to see your order history, reorder a favourite in one tap, and keep your address and spice preferences on file.</p>
			<div class="gm-perks">
				<p><strong>Order history.</strong> Every order, its slot and what you paid.</p>
				<p><strong>One-tap reorder.</strong> Puts a past order straight back in your basket.</p>
				<p><strong>Saved details.</strong> Address, mobile and spice level, ready at checkout.</p>
			</div>
		</div>
		<div class="gm-panel">
			<?php /* Sign in. Posts to wp-login.php only if JavaScript is off; app.js signs in on-site. */ ?>
			<div data-gm-auth="login">
				<h3 class="gm-label">Sign in</h3>
				<form method="post" action="<?php echo esc_url( wp_login_url() ); ?>" class="gm-form" data-gm-login novalidate>
					<div class="field"><label for="gm-log">Email</label><input class="input" id="gm-log" name="log" type="text" autocomplete="username" placeholder="you@example.com" required></div>
					<div class="field"><label for="gm-pwd">Password</label><input class="input" id="gm-pwd" name="pwd" type="password" autocomplete="current-password" placeholder="••••••••" required></div>
					<label class="gm-check"><input type="checkbox" name="rememberme" value="forever" checked> Keep me signed in</label>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( gm_view_url( 'account' ) ); ?>">
					<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
					<button type="submit" class="gm-btn gm-btn--block">Sign in</button>
					<a class="gm-small" href="#forgot">Forgotten your password?</a>
				</form>
				<div class="gm-panel__foot">
					<p class="gm-small gm-muted">First time ordering? Create an account and your details are saved for next time.</p>
					<a class="gm-btn-outline gm-btn-outline--block" href="#register">Create an account</a>
				</div>
			</div>

			<div data-gm-auth="forgot" hidden>
				<h3 class="gm-label">Forgotten your password?</h3>
				<p class="gm-small gm-muted">Enter the email you signed up with and we'll send you a link to choose a new password.</p>
				<form class="gm-form" data-gm-forgot novalidate>
					<div class="field"><label for="gm-forgot-email">Email</label><input class="input" id="gm-forgot-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required></div>
					<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
					<p class="gm-success" data-gm-auth-ok role="status" hidden></p>
					<button type="submit" class="gm-btn gm-btn--block">Send me a reset link</button>
				</form>
				<div class="gm-panel__foot">
					<a class="gm-btn-outline gm-btn-outline--block" href="#login">Back to sign in</a>
				</div>
			</div>

			<div data-gm-auth="reset" hidden>
				<h3 class="gm-label">Choose a new password</h3>
				<form class="gm-form" data-gm-reset novalidate>
					<div class="field"><label for="gm-new-pass">New password</label><input class="input" id="gm-new-pass" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required></div>
					<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
					<button type="submit" class="gm-btn gm-btn--block">Save and sign in</button>
				</form>
				<div class="gm-panel__foot">
					<a class="gm-btn-outline gm-btn-outline--block" href="#forgot">Send a new link</a>
				</div>
			</div>

			<div data-gm-auth="register" hidden>
				<h3 class="gm-label">Create an account</h3>
				<form class="gm-form" data-gm-register novalidate>
					<div class="field"><label for="gm-reg-name">Name</label><input class="input" id="gm-reg-name" name="name" type="text" autocomplete="name" placeholder="Your name" required></div>
					<div class="field"><label for="gm-reg-email">Email</label><input class="input" id="gm-reg-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" required></div>
					<div class="field"><label for="gm-reg-pass">Password</label><input class="input" id="gm-reg-pass" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required></div>
					<div class="gm-hp" aria-hidden="true"><label for="gm-reg-website">Website</label><input id="gm-reg-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
					<p class="gm-error" data-gm-auth-error role="alert" hidden></p>
					<button type="submit" class="gm-btn gm-btn--block">Create my account</button>
				</form>
				<div class="gm-panel__foot">
					<p class="gm-small gm-muted">Already have an account?</p>
					<a class="gm-btn-outline gm-btn-outline--block" href="#login">Sign in</a>
				</div>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ===================================================== Account */ ?>
<?php
if ( $gm_signed_in ) :
	$gm_orders = gm_user_orders( $gm_user->ID );
	$gm_saved  = gm_saved_details( $gm_user->ID );
	$gm_spent  = 0;
	foreach ( $gm_orders as $gm_order ) {
		$gm_spent += $gm_order['total_pence'];
	}
	?>
<section class="gm-view" data-view="account" hidden>
	<header class="gm-slimbar">
		<div class="gm-slimbar__inner gm-slimbar__inner--wide">
			<a class="gm-logo" href="#"><img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala" width="76" height="46"></a>
			<a class="gm-slimbar__link" href="#">← Back to the site</a>
			<a class="gm-slimbar__link gm-slimbar__link--quiet" href="<?php echo esc_url( wp_logout_url( gm_view_url() ) ); ?>" data-gm-logout>Sign out</a>
		</div>
	</header>
	<div class="gm-narrow gm-narrow--wide gm-page">
		<h1 class="gm-title gm-title--md">Welcome back<?php echo $gm_user->first_name ? ', ' . esc_html( $gm_user->first_name ) : ''; ?></h1>
		<div class="gm-stats">
			<div><p class="gm-stats__n gm-tnum"><?php echo count( $gm_orders ); ?></p><p class="gm-label">Orders with us</p></div>
			<div><p class="gm-stats__n gm-tnum"><?php echo esc_html( gm_money( $gm_spent ) ); ?></p><p class="gm-label">Spent to date</p></div>
			<div><p class="gm-stats__n gm-stats__n--sm"><?php echo esc_html( $gm_saved ? $gm_saved['postcode'] : '—' ); ?></p><p class="gm-label">Saved address</p></div>
		</div>
		<h2 class="gm-subtitle" style="margin:clamp(32px,4vw,56px) 0 20px">Order history</h2>
		<div class="gm-history">
			<?php if ( ! $gm_orders ) : ?>
				<p class="gm-muted" style="padding:20px 0">No orders yet — your orders will appear here.</p>
			<?php endif; ?>
			<?php
			foreach ( $gm_orders as $gm_order ) :
				$gm_reorder = array();
				$gm_summary = array();
				foreach ( $gm_order['lines'] as $line ) {
					$gm_reorder[ $line['id'] ] = $line['qty'];
					$gm_summary[]              = $line['qty'] . ' × ' . $line['name'];
				}
				?>
				<div class="gm-history__row">
					<div class="gm-history__when">
						<p><?php echo esc_html( $gm_order['date'] ); ?></p>
						<p class="gm-small gm-muted gm-tnum"><?php echo esc_html( $gm_order['window'] . ' · ' . $gm_order['ref'] ); ?></p>
					</div>
					<p class="gm-history__what"><?php echo esc_html( implode( ', ', $gm_summary ) ); ?></p>
					<span class="gm-history__status"><?php echo 'card' === $gm_order['payment'] ? 'Paid' : 'Cash on delivery'; ?></span>
					<span class="gm-history__total gm-tnum"><?php echo esc_html( $gm_order['total'] ); ?></span>
					<button type="button" class="gm-btn-outline" data-gm-reorder="<?php echo esc_attr( wp_json_encode( $gm_reorder ) ); ?>">Order again</button>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="gm-split gm-split--tight" style="margin-top:clamp(32px,4vw,52px)">
			<div>
				<h3 class="gm-label">Saved details</h3>
				<?php if ( $gm_saved ) : ?>
					<p><?php echo esc_html( $gm_saved['name'] ); ?><br><?php echo esc_html( $gm_saved['address'] ); ?><br><?php echo esc_html( $gm_saved['postcode'] ); ?><br><?php echo esc_html( $gm_saved['phone'] ); ?></p>
					<?php if ( $gm_saved['notes'] ) : ?><p class="gm-small gm-muted">Last spice &amp; allergy note: <?php echo esc_html( $gm_saved['notes'] ); ?></p><?php endif; ?>
				<?php else : ?>
					<p class="gm-muted">Your address is saved when you place your first order.</p>
				<?php endif; ?>
			</div>
			<div>
				<h3 class="gm-label">Order again</h3>
				<p class="gm-soft">Reordering fills your basket with the same dishes — you just choose a new slot.</p>
				<a class="gm-btn" href="#menu">Browse the menu</a>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php /* ===================================================== Checkout */ ?>
<section class="gm-view" data-view="pay" hidden>
	<header class="gm-slimbar">
		<div class="gm-slimbar__inner">
			<img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala" width="76" height="46">
			<span class="gm-slimbar__note">Secure checkout</span>
		</div>
	</header>
	<div class="gm-narrow gm-page">
		<div class="gm-pay__head">
			<h1 class="gm-title gm-title--md">Checkout</h1>
			<a class="gm-textlink" href="#menu" data-gm-release>← Back to the menu</a>
		</div>
		<p class="gm-lede" style="margin:18px 0 clamp(28px,3.5vw,44px)">Delivery window <strong data-gm-slot-label></strong>.</p>
		<form class="gm-pay" data-gm-pay novalidate>
			<div>
				<h3 class="gm-step">Delivery details</h3>
				<div class="gm-form">
					<div class="field"><label for="gm-name">Name</label><input class="input" id="gm-name" name="name" type="text" autocomplete="name" placeholder="Your name" required value="<?php echo esc_attr( $gm_signed_in ? $gm_user->display_name : '' ); ?>"></div>
					<div class="field"><label for="gm-email">Email</label><input class="input" id="gm-email" name="email" type="email" autocomplete="email" placeholder="For your confirmation" required value="<?php echo esc_attr( $gm_signed_in ? $gm_user->user_email : '' ); ?>"></div>
					<div class="field"><label for="gm-addr">Address</label><input class="input" id="gm-addr" name="address" type="text" autocomplete="street-address" placeholder="House number and street" required></div>
					<div class="field"><label for="gm-post">Postcode</label><input class="input" id="gm-post" name="postcode" type="text" autocomplete="postal-code" placeholder="B69 1NY" required><p class="gm-small gm-muted" data-gm-postcode-msg aria-live="polite" style="margin:6px 0 0"></p></div>
					<div class="field"><label for="gm-phone">Mobile</label><input class="input" id="gm-phone" name="phone" type="tel" autocomplete="tel" placeholder="07…" required></div>
					<div class="field"><label for="gm-notes">Allergy or delivery notes (optional)</label><input class="input" id="gm-notes" name="instructions" type="text" placeholder="e.g. no dairy, side door"></div>
					<div class="field"><label for="gm-discount">Discount code (optional)</label><input class="input" id="gm-discount" name="discount" type="text" placeholder="Enter your code"></div>
					<div class="gm-hp" aria-hidden="true"><label for="gm-website">Website</label><input id="gm-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
				</div>
				<?php if ( gm_stripe_enabled() && gm_cod_enabled() ) : ?>
					<h3 class="gm-step" style="margin-top:34px">Payment</h3>
					<div class="gm-form">
						<label class="radio gm-pay-option"><input type="radio" name="payment" value="card" checked><span class="dot"></span><span>Pay now by card, Apple Pay or Google Pay<?php get_template_part( 'template-parts/pay-badges' ); ?></span></label>
						<label class="radio gm-pay-option" data-gm-cod-option><input type="radio" name="payment" value="cod"><span class="dot"></span><span>Pay on delivery (Cash only)<small class="gm-muted" data-gm-cod-note><?php echo gm_cash_limit() ? esc_html( 'Orders up to ' . gm_money( gm_cash_limit() ) ) : ''; ?></small></span></label>
					</div>
				<?php else : ?>
					<input type="hidden" name="payment" value="<?php echo gm_stripe_enabled() ? 'card' : 'cod'; ?>">
				<?php endif; ?>
			</div>
			<div class="gm-panel">
				<h3 class="gm-label">Order summary</h3>
				<div data-gm-summary></div>
				<div class="gm-panel__slot">
					<p class="gm-label">Delivery slot</p>
					<p data-gm-slot-label></p>
				</div>
				<p class="gm-error" data-gm-error role="alert" hidden></p>
				<button type="submit" class="gm-btn gm-btn--block gm-btn--lg" data-gm-submit>Place order</button>
				<p class="gm-small gm-muted" data-gm-pay-note></p>
			</div>
		</form>
	</div>
</section>

<?php /* ===================================================== Confirmed */ ?>
<section class="gm-view" data-view="done" hidden>
	<header class="gm-slimbar">
		<div class="gm-slimbar__inner">
			<img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala" width="76" height="46">
		</div>
	</header>
	<div class="gm-narrow gm-page">
		<span class="gm-eyebrow" data-gm-done-kicker></span>
		<h1 class="gm-title gm-title--md" style="max-width:22ch" data-gm-done-title>Booked in. Your table at home is set.</h1>
		<p class="gm-lede" data-gm-done-text></p>
		<div class="gm-done__facts">
			<div><span class="gm-muted" data-gm-done-total-label>Total</span><strong class="gm-tnum" data-gm-done-total></strong></div>
			<div><span class="gm-muted">Slot</span><strong data-gm-done-slot></strong></div>
		</div>
		<a class="gm-btn-outline gm-btn-outline--lg" href="#" data-gm-restart style="margin-top:32px">Back to the site</a>
	</div>
</section>

</main>

<?php get_template_part( 'template-parts/site-footer' ); ?>

<?php
get_footer();
