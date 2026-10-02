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
?>

<?php get_template_part( 'template-parts/topbar' ); ?>

<main id="gm-main">

<?php /* ===================================================== Home */ ?>
<section class="gm-view" data-view="home">
	<div class="gm-hero" style="background-image:linear-gradient(180deg,rgba(0,29,22,.8) 0%,rgba(0,29,22,.52) 45%,rgba(0,29,22,.8) 100%),url('<?php echo esc_url( gm_asset( 'images/hero.jpg' ) ); ?>')">
		<header class="gm-hero__bar">
			<a class="gm-logo" href="<?php echo esc_url( gm_view_url() ); ?>"><img src="<?php echo esc_url( $gm_logo ); ?>" alt="Ghar Masala — tradition served with comfort" width="106" height="64"></a>
			<?php get_template_part( 'template-parts/halal' ); ?>
			<nav class="gm-nav gm-nav--hero" aria-label="Main">
				<a href="#menu">Menu</a>
				<a href="#how">How it works</a>
				<a href="#story">My story</a>
				<a href="#account"><?php echo $gm_signed_in ? 'My account' : 'Sign in'; ?></a>
				<a class="gm-nav__order" href="#order">Your order<span class="gm-tnum" data-gm-badge></span></a>
			</nav>
		</header>
		<div class="gm-hero__body">
			<span class="gm-hero__kicker">Tividale · Oldbury · Fresh to order</span>
			<h1 class="gm-hero__title">Good food starts in the Kitchen.</h1>
			<div class="gm-hero__ctas">
				<a class="gm-hero__cta gm-hero__cta--main" href="#menu">Order now</a>
				<a class="gm-hero__cta" href="#how">How it works</a>
			</div>
			<div class="gm-hero__facts">
				<div><p class="gm-hero__fact">15 min</p><p class="gm-hero__fact-label">Delivery window</p></div>
				<div><p class="gm-hero__fact">£10</p><p class="gm-hero__fact-label">Minimum spend, free delivery</p></div>
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
			<p class="gm-muted">Deliveries 6–10pm, Sunday to Thursday · Order by 7pm the day before</p>
		</div>
	</div>
</section>

<?php /* ===================================================== Testimonials */ ?>
<section class="gm-view" data-view="testimonials" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">Testimonials</h1>
		<p class="gm-lede">Reviews from people who have ordered from Ghar Masala. This page is new — real reviews will replace these placeholders as they come in.</p>
		<div class="gm-quotes">
			<?php for ( $i = 0; $i < 3; $i++ ) : ?>
				<div class="gm-quotes__item">
					<p class="gm-quotes__text">"Add a customer quote here."</p>
					<p class="gm-quotes__by">— Customer name</p>
				</div>
			<?php endfor; ?>
		</div>
		<div class="gm-cta-row">
			<a class="gm-btn" href="https://wa.me/<?php echo esc_attr( gm_phone_intl() ); ?>" target="_blank" rel="noopener">Leave us a review</a>
		</div>
	</div>
</section>

<?php /* ===================================================== News */ ?>
<section class="gm-view" data-view="news" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title">News</h1>
		<p class="gm-lede">Updates from the kitchen.</p>
		<div class="gm-news">
			<?php
			$gm_posts = get_posts( array( 'posts_per_page' => 10 ) );
			if ( $gm_posts ) :
				foreach ( $gm_posts as $gm_post ) :
					?>
					<article>
						<p class="gm-news__date"><?php echo esc_html( get_the_date( 'j F Y', $gm_post ) ); ?></p>
						<h3><a href="<?php echo esc_url( get_permalink( $gm_post ) ); ?>"><?php echo esc_html( get_the_title( $gm_post ) ); ?></a></h3>
						<p><?php echo esc_html( get_the_excerpt( $gm_post ) ); ?></p>
					</article>
					<?php
				endforeach;
			else :
				?>
				<article>
					<p class="gm-news__date">20 August 2026</p>
					<h3>Online pre-ordering is here</h3>
					<p>You can now build your order and book a delivery slot up to seven days ahead directly on this site — choose your dishes, pick a 30-minute slot, and pay at checkout. Ringing the kitchen still works too.</p>
				</article>
			<?php endif; ?>
		</div>
		<p class="gm-muted" style="margin-top:clamp(24px,3vw,34px)">More updates soon.</p>
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
				'How far ahead can I order?'                    => 'Up to seven days ahead. Orders for a given evening must be placed by 7pm the day before.',
				'Is there a minimum order?'                     => 'Yes, £10, which also covers free delivery within two miles of Tividale Viewpoint. Beyond that it is £1 per mile.',
				'What are your delivery hours?'                 => 'Deliveries run 6–10pm, Sunday to Thursday. Each slot gives you a fifteen-minute delivery window.',
				'Is the food halal?'                            => 'Yes — look for the حلال mark in the header on every page.',
				'Can I choose how spicy my curry is?'           => 'Yes, tell us your spice level when you order. Madras and Vindaloo are 30p extra.',
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
		<p class="gm-lede">Spice level on any curry is adjusted to how you like it — just say when you order. Madras and Vindaloo are 30p extra.</p>

		<div class="gm-key">
			<p class="gm-key__title">Menu key</p>
			<div class="gm-key__grid">
				<div class="gm-key__cell">
					<h4 class="gm-label">Spice levels</h4>
					<div class="gm-spice">
						<div><span class="gm-dots"><i class="on"></i><i></i><i></i><i></i></span>Slightly hot</div>
						<div><span class="gm-dots"><i class="on"></i><i class="on"></i><i></i><i></i></span>Hot</div>
						<div><span class="gm-dots gm-dots--600"><i class="on"></i><i class="on"></i><i class="on"></i><i></i></span>Madras — hot<span class="gm-muted">&nbsp;· 30p</span></div>
						<div><span class="gm-dots gm-dots--700"><i class="on"></i><i class="on"></i><i class="on"></i><i class="on"></i></span>Vindaloo — hot<span class="gm-muted">&nbsp;· 30p</span></div>
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
								<?php if ( ! empty( $item['rec'] ) || ! empty( $item['veg'] ) || ! empty( $item['adjustable'] ) ) : ?>
									<span class="gm-dish__meta">
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
							<span class="gm-dish__qty gm-tnum" data-gm-qty="<?php echo esc_attr( $item['id'] ); ?>"></span>
							<button type="button" class="gm-btn-outline" data-gm-add="<?php echo esc_attr( $item['id'] ); ?>" aria-label="Add <?php echo esc_attr( $item['name'] ); ?>">Add</button>
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
					<p class="gm-small gm-muted" style="margin-top:18px">Minimum £10 spend for free delivery. Spice level and allergy notes are taken at checkout.</p>
				</div>
				<div>
					<p class="gm-step">02 — Delivery slot</p>
					<p style="margin:0 0 22px">We deliver Sunday to Thursday, 6–10pm. Slots run every 30 minutes and each gives you a 15-minute delivery window — once taken, a slot closes to everyone else.</p>
					<div data-gm-slots></div>
				</div>
			</div>
		</div>
	</div>

	<div class="gm-wrap gm-menu__foot">
		<a class="gm-ulink" href="#allergens">Allergen information</a>
		<span class="gm-muted">All dishes are prepared where allergens are present, so traces cannot be guaranteed.</span>
	</div>
</section>

<?php /* ===================================================== How it works */ ?>
<section class="gm-view" data-view="how" hidden>
	<div class="gm-wrap gm-page">
		<h1 class="gm-title" style="max-width:24ch">How ordering works</h1>
		<p class="gm-lede">Every dish is cooked to order, so the kitchen works to booked slots rather than walk-ins. That means a fixed fifteen-minute delivery window and no guesswork about when your food arrives.</p>

		<div class="gm-steps">
			<?php
			$gm_pay_copy = gm_stripe_enabled()
				? 'Pay by card at checkout. Your slot is held while you pay, and confirmed on screen the moment payment clears.'
				: 'Place your order at checkout and pay when the food arrives. Your slot is confirmed on screen straight away.';
			$gm_steps    = array(
				'Fill your basket'       => 'Choose your dishes and tell us the spice level you want. Orders of £10 or more are delivered free within two miles of Tividale Viewpoint.',
				'Book a delivery slot'   => 'Slots run every thirty minutes between 6pm and 10pm, Sunday to Thursday, and can be booked up to a week ahead. Orders close at 7pm the day before delivery.',
				'Confirm your order'     => $gm_pay_copy,
				'Cooked, then delivered' => 'Everything is cooked on the day of delivery, packed into insulated bags and driven to you inside your fifteen-minute window.',
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
				<p class="gm-soft" style="max-width:46ch;margin:0 0 26px">We deliver ourselves, out of Tividale, from Sunday to Thursday between 6 and 10pm. Because every order is booked against a slot, your food is timed to land when you asked for it.</p>
				<div class="gm-facts">
					<div><span>Within 2 miles of Tividale Viewpoint, £10 or more</span><span>Free</span></div>
					<div><span>Beyond 2 miles</span><span>£1 per mile</span></div>
					<div><span>Delivery days</span><span>Sunday to Thursday</span></div>
					<div><span>Delivery window</span><span>15 minutes</span></div>
					<div><span>Order deadline</span><span>7pm the day before</span></div>
					<div><span>Payment</span><span><?php echo esc_html( gm_stripe_enabled() ? ( gm_cod_enabled() ? 'Card online or on delivery' : 'Card at checkout' ) : 'On delivery' ); ?></span></div>
				</div>
				<p class="gm-small gm-muted" style="margin-top:20px">Tividale, Oldbury and the surrounding area. If you are not sure whether we reach you, ring and ask — we usually can.</p>
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
			<h3 class="gm-label">Sign in</h3>
			<form method="post" action="<?php echo esc_url( wp_login_url() ); ?>" class="gm-form">
				<div class="field"><label for="gm-log">Email or username</label><input class="input" id="gm-log" name="log" type="text" autocomplete="username" placeholder="you@example.com" required></div>
				<div class="field"><label for="gm-pwd">Password</label><input class="input" id="gm-pwd" name="pwd" type="password" autocomplete="current-password" placeholder="••••••••" required></div>
				<label class="gm-check"><input type="checkbox" name="rememberme" value="forever"> Keep me signed in</label>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( gm_view_url( 'account' ) ); ?>">
				<button type="submit" class="gm-btn gm-btn--block">Sign in</button>
				<a class="gm-small" href="<?php echo esc_url( wp_lostpassword_url( gm_view_url( 'login' ) ) ); ?>">Forgotten your password?</a>
			</form>
			<?php if ( get_option( 'users_can_register' ) ) : ?>
				<div class="gm-panel__foot">
					<p class="gm-small gm-muted">First time ordering? Create an account and your details are saved for next time.</p>
					<a class="gm-btn-outline gm-btn-outline--block" href="<?php echo esc_url( wp_registration_url() ); ?>">Create an account</a>
				</div>
			<?php endif; ?>
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
			<a class="gm-slimbar__link gm-slimbar__link--quiet" href="<?php echo esc_url( wp_logout_url( gm_view_url() ) ); ?>">Sign out</a>
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
					<span class="gm-history__status"><?php echo 'card' === $gm_order['payment'] ? 'Paid' : 'Pay on delivery'; ?></span>
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
					<div class="field"><label for="gm-post">Postcode</label><input class="input" id="gm-post" name="postcode" type="text" autocomplete="postal-code" placeholder="B69" required></div>
					<div class="field"><label for="gm-phone">Mobile</label><input class="input" id="gm-phone" name="phone" type="tel" autocomplete="tel" placeholder="07…" required></div>
					<div class="field"><label for="gm-notes">Spice level &amp; allergy notes</label><input class="input" id="gm-notes" name="instructions" type="text" placeholder="e.g. lamb curry madras, no dairy"></div>
					<div class="field"><label for="gm-discount">Discount code (optional)</label><input class="input" id="gm-discount" name="discount" type="text" placeholder="Enter your code"></div>
					<div class="gm-hp" aria-hidden="true"><label for="gm-website">Website</label><input id="gm-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
				</div>
				<?php if ( gm_stripe_enabled() && gm_cod_enabled() ) : ?>
					<h3 class="gm-step" style="margin-top:34px">Payment</h3>
					<div class="gm-form">
						<label class="radio"><input type="radio" name="payment" value="card" checked><span class="dot"></span>Pay now by card</label>
						<label class="radio"><input type="radio" name="payment" value="cod"><span class="dot"></span>Pay on delivery (cash or card)</label>
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
