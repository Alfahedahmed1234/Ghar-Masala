# Ghar Masala — WordPress theme

This repository holds the Ghar Masala website as a WordPress theme you can install on Hostinger. It is built from the design file `ghar-masala-website.html`.

The site keeps the design's single-page feel. Home, Menu, How it works, My story, Testimonials, News, FAQs, Allergens, Sign in / My account, Checkout and Confirmation all live on the front page. You move between them with links like `yoursite.co.uk/#menu`.

Unlike the design prototype, this version takes real orders:

- **Basket and delivery slots.** The £10 minimum is enforced. Slots run 6–10pm, Sunday to Thursday. Orders close at 7pm the day before. A slot that has been booked closes for everyone, and the server checks this as well as the page.
- **Delivery charge from the postcode.** As soon as a customer types their postcode at checkout, the site works out how far it is from B69 1NY. Up to 2 miles is free, then it's £1 for each extra mile started (2.4 miles = £1). Postcodes more than 3 miles away can't check out. The charge is worked out again on the server when the order is placed, so the page can't be tricked into skipping it. You can change all of these numbers in **Settings → Ghar Masala → Delivery charge**.
- **Checkout.** Customers can pay on delivery, or pay by card through Stripe if you add your Stripe keys. Card numbers are typed on Stripe's own page and never reach your website.
- **Orders in WordPress.** Each order shows up under **Orders** in the dashboard. An email goes to the kitchen and another to the customer.
- **Customer accounts.** Customers create an account, sign in, sign out and reset a forgotten password on the site itself, without ever seeing a WordPress page. The reset email links straight back to Ghar Masala. If a customer lands on a WordPress login, register or password page, or types /wp-admin, they're sent to the matching page on the site. You and other staff still sign in at /wp-login.php as normal. Customers see their order history, can reorder with one tap, and get their address filled in at checkout. New accounts are always ordinary customer accounts ("Subscriber"), and WordPress's own "Anyone can register" setting isn't needed.

## Install on Hostinger

1. Download **`dist/ghar-masala.zip`** from this repository.
2. In WordPress admin, go to **Appearance → Themes → Add New Theme → Upload Theme**. Choose the zip, click **Install Now**, then **Activate**.
3. Go to **Settings → General** and set **Timezone** to **London**.
4. Go to **Settings → Permalinks**, choose **Post name** and click **Save**. The ordering system needs this.
5. Go to **Settings → Ghar Masala** and check:
   - the kitchen phone number and public email
   - **Send new orders to:** the inbox that should receive order emails
   - **Delivery charge:** kitchen postcode (B69 1NY), how the area is described to customers ("Tividale Viewpoint B69"), free radius (2 miles), price per extra mile (£1) and the furthest you deliver (3 miles).
   - **Card payments:** see below. The **Pay by card** option only appears once your Stripe key is in, and the dashboard reminds you until it is.
6. **Emails** are sent from "Ghar Masala". Hostinger's default PHP mail often lands in spam. Create a mailbox in hPanel (for example orders@yourdomain), then install the free **WP Mail SMTP** plugin and connect it to that mailbox.
   If you use WP Mail SMTP, set its **From Name** to "Ghar Masala" too, because its setting overrides the theme's.
7. **Caching:** if you use **LiteSpeed Cache**, the theme already tells it not to cache live slot data or checkout pages. After you change the menu, purge the cache with **LiteSpeed Cache → Purge All**.

### Card payments (Stripe)

1. Create a Stripe account, then go to **Developers → API keys** and copy the **Secret key**.
   - Start with the test key (`sk_test_…`) and pay with the card `4242 4242 4242 4242`.
   - Switch to the live key (`sk_live_…`) when you are ready.
2. Paste the key into **Settings → Ghar Masala → Secret key**.
3. Recommended: add a webhook so an order is still confirmed if a customer closes the tab right after paying.
   - In Stripe, go to **Developers → Webhooks → Add endpoint**.
   - Use the URL shown on the settings page, which ends in `/wp-json/ghar-masala/v1/stripe-webhook`.
   - Choose the event `checkout.session.completed`.
   - Paste the endpoint's signing secret (`whsec_…`) into the settings page.
4. **Apple Pay & Google Pay:** in Stripe, go to **Settings → Payments → Payment methods** and switch on **Apple Pay** and **Google Pay**. They then appear automatically on Stripe's checkout page for customers whose phone or browser supports them, e.g. Safari on iPhone and Chrome on Android. Nothing else needs setting up on the website.
5. Leave **Pay on delivery** ticked to offer both options, or untick it so customers can only pay online.
6. **Cash on delivery** is **cash only**. Orders over **£100** (food + delivery) must be paid online. The checkout greys out the cash option above that amount, and the website rejects it too. You can change the amount in **Settings → Ghar Masala → Cash limit** (0 = no limit).

## Opening times & delivery slots (WP Admin → Orders → Opening times & slots)

- **Regular opening times:** tick your delivery days and set the opening and closing times, how often slots start (every 30 minutes, etc.), the delivery window length, how many orders each slot can take, and the order deadline (e.g. 7pm the day before). All the "Deliveries 6–10pm, Sunday to Thursday" wording across the site updates itself.
- **Closed dates:** list holidays and days off, one per line (`25/12/2026`) or as a range (`31/12/2026 - 02/01/2027`).
- **Slot grid (next 14 days):** each box is how many orders that slot can take on that date.
  - Raise a box to take more orders at that time, or set it to **0** to block the time.
  - Tick **Closed** to shut the whole day.
  - Booked counts appear in each box, so you can see what's taken.
  - These changes only affect that date.

## Customers (WP Admin → Customers)

Every customer account, newest first, with their contact details, address (from their last order), date joined, number of orders, total spent and last order date. You can search by name or email. For each customer:
- **View orders:** opens Orders showing just that customer's orders.
- **Edit details:** change their name or email.
- **Send password reset:** emails them a link to choose a new password on the site.
- **Delete:** removes the account. Their past orders stay in Orders.
- **Loyalty column:** shows stamps (e.g. 7 / 10) with a progress bar, plus a **Next order 50% off** badge (9 stamps) or a **Reward saved** badge. Use **+1 / −1** to add or remove a stamp by hand.

## Loyalty programme (WP Admin → Customers → Loyalty programme)

Signed-in customers get a stamp for every confirmed order whose food total, after other discounts, is at least the minimum (default £20). The order that completes the card gets the reward: with the defaults, the **10th order is 50% off** the food total, as long as it's £20 or more. At checkout a ticked box says **Use my 50% loyalty reward**. If they untick it, that order still earns its stamp and the reward is saved; it doesn't expire and can then be used on any later order, whatever its size. Using the reward starts a new card, and extra stamps carry over. My account shows a progress bar, stamp card and history.

On the same screen you can change the number of orders, minimum spend, reward %, an optional cap on the saving, and the advert ("Order 10 times, get 50% off"). The advert runs full width under the main picture on the home page, and sits next to the delivery checker on the menu page (you can switch each off), and always shows on the sign-in / create-account page.

## Review thank-you codes (WP Admin → Discounts → Settings)

When you publish a customer's **first** review and they left an email address, they're automatically emailed a one-use code (default 10% off, valid 60 days). It works **on top of** any other discount. Each email address only gets one. You can change the % and the number of days, or switch it off. The codes appear in Discounts, and the Reviews list shows whether a code was sent.

## Discounts (WP Admin → Discounts)

Click **Add discount**, give it a name customers will see (e.g. "10% off orders over £15"), then choose:
- **Discount code:** customers type the code (e.g. `WELCOME10`) at checkout and press **Apply**.
- **Automatic:** applies by itself once the food total reaches the **Minimum spend**. The basket and mini basket show "Add £X more to get 10% off your order" with a progress bar, then "✓ 10% off applied".

For either type, set:
- **% off** or **£ off**
- an optional **minimum spend** and **end date**
- for codes, an optional **use limit** ("Used so far" counts confirmed orders)

**Publish** switches a discount on; **Draft** turns it off. Discounts come off the **food total** (delivery is charged as normal). They're worked out by the website itself and passed to Stripe for card payments.

**Discounts → Settings → "Allow a discount code to be used as well as an automatic discount":**
- **Ticked:** both apply.
- **Unticked:** the customer automatically gets whichever saves them more, and is told why.

## Banners & announcements (WP Admin → Banners)

Click **Add banner** and fill in:
- **Message:** one short line, e.g. "20% off this week — use code at checkout".
- **Discount code:** optional. It's shown with a **Copy** button that also fills it into the checkout box. Type a new code with its discount (% or £), minimum spend and use limit, and it's **created in Discounts for you**. Type an existing code and it links to it, so changing the amounts here updates the discount. The code stops working after the banner's "until" date. If the discount is switched off, expires or is used up, the banner hides itself.
- **Button:** optional, linking to the Menu, Your order, Reviews, News, Contact, Allergens, How it works, My account, or any web address.
- **Show it:** the **top of every page** (announcement bar), the **home page** under the main picture, the **menu page** above the dishes, and/or **checkout**.
- **Colour:** saffron, green, dark, or red for urgent notices.
- **Dates:** optional from/until dates. Banners switch themselves on and off.
- **Closing:** whether customers can close it with ×. It stays closed on their device. A new or edited message shows again.

**Publish** shows it; **Draft** hides it. With several banners in the top bar, they take turns every 6 seconds (pausing while someone's mouse is over them). **Order** decides which comes first.

## Managing orders

Go to **Orders** in the dashboard and open an order to see:

- the dishes and the note on each one
- the delivery slot
- the customer's address, phone and email
- spice and allergy notes, and any discount code

To cancel an order, set **Status → Cancelled** and click **Update**. This frees the slot for someone else.

Discount codes are recorded but **not** taken off automatically. Adjust the bill yourself if the code is valid.

## Editing the site

| What | Where |
| --- | --- |
| Dishes, prices, descriptions, badges, allergens | **WP Admin → Menu** (see below) |
| Opening days & times, closed dates, slots | **WP Admin → Orders → Opening times & slots** |
| Page wording & pictures (home headline, my story, how it works, reviews, contact, FAQs) | **Appearance → Customize → Ghar Masala** |
| Colours, fonts and text size | **Appearance → Customize → Ghar Masala → Colours & fonts** |
| Countdown, welcome-back message, delivery checker | **Appearance → Customize → Ghar Masala → Countdown, welcome & delivery checker** |
| Minimum order (£10) | `ghar-masala/inc/data.php` → `gm_rules()` |
| Footer (social links, hours, company details) | `ghar-masala/template-parts/site-footer.php` |
| Logo | **Appearance → Customize → Site Identity → Logo**, or replace `assets/images/logo.png` |
| News | publish ordinary **Posts**; the News page lists the latest ten |
| Testimonials | **WP Admin → Testimonials** (see below) |

For the rows marked as files, use **Appearance → Theme File Editor**, or edit them in this repo and upload a new zip.

### Appearance → Customize → Ghar Masala

Everything here has a live preview. Click **Publish** to save.
- **Home page:** the small line, the headline and the main picture.
- **Countdown, welcome & delivery checker:**
  - **Countdown:** a slim bar at the top, under the announcement banner, shows the next delivery day and a live countdown to its order deadline. It skips closed and fully booked days.
  - **Welcome message:** a small bubble in the bottom-left corner greets returning customers by name, with an **Order it again** button. Customers can close it with ×, and it stays closed until they place another order. It says "hope you enjoyed your…" after a past order, or "…is booked in for…" when an order is still to come. It works for signed-in customers and for anyone who has ordered on that device.
  - **Delivery checker:** a small "Do we deliver to you?" postcode box on the menu page, next to the loyalty advert. If the postcode is in range, it's filled in at checkout. If it's out of range, customers see your note with **call** and **Message us** buttons.
  - Each one can be hidden, and its wording changed.
- **My story / How it works:** the wording and photos. Leave a blank line between paragraphs. Remove a photo to go back to the original.
- **Reviews, contact & FAQs:** the slideshow heading ("What our customers are saying"), the page introductions, and the FAQs (question on the first line, answer underneath, then a blank line).
- **Colours & fonts:** the main and highlight colours, background, text colour, heading and body fonts, and text size.

Text boxes understand `{hours}`, `{days}`, `{cutoff}`, `{window}`, `{phone}`, `{delivery}` and `{days_ahead}`. These fill in from your settings, so they stay right when you change opening times.

### The menu

Manage it in **WP Admin → Menu**. Your original menu was copied in the first time the theme ran.

- **Change a price or description:** go to **Menu → All dishes**, click the dish, edit it, then click **Update**.
- **Add a dish:** click **Add dish**. Fill in the name (top box), the description (box below it), the **Price** and the **Section**. Tick any badges (Vegetarian, Recommended, "Spice to order"), set its **Allergens**, then click **Publish**.
- **Take a dish off the menu:** switch it to **Draft** to hide it but keep it for later, or move it to the **Bin** to remove it. Customers can't order hidden dishes, even from an old basket or "Order again".
- **Customers choosing spice:** dishes with "Spice to order" ticked get a **Spice** dropdown in the customer's basket (Standard, Slightly hot, Hot, Madras, Vindaloo). Madras and Vindaloo add 30p per dish, unless the dish is already Madras or Vindaloo as standard. The chosen level appears on the order, in the kitchen email and on the card payment.
- **Standard spice level:** in the dish's **Standard spice level** box, pick Slightly hot, Hot, Madras or Vindaloo, the same scale as the menu's Spice levels key, or "Not spicy / not shown". The dish then shows the matching coloured squares next to its name. This sets how hot the dish is as standard and doesn't change the price. Customers can still ask for a different level in their notes.
- **Order on the page:** the **Order** box (under "Page Attributes") sets a dish's position within its section, lowest first.
- **Sections:** use **Menu → Sections** to rename one, change the note beside its heading (e.g. "Served with salad & mint sauce"), set its **Order** on the page, or add a new one (e.g. Desserts). Empty sections are hidden.
- **Allergens:** the allergen table on the site is built automatically from each dish's Allergens. New dishes are added to it as soon as you publish them. To see and change every dish at once, use **Menu → Allergen table**: one row per dish, with Contains / May contain for each of the 14 allergens. Tick "Leave this dish out of the allergen table" for things like canned drinks.

Price changes only apply to new orders; past orders keep the price they were placed at. If you use LiteSpeed Cache, the site refreshes itself after each menu change.

### Reviews (WP Admin → Testimonials)

The page is called **Reviews** on the site.
- **Reviews from customers:** customers can click **Leave a review** and fill in their name, area, a star rating and their review. Each one arrives in **WP Admin → Testimonials** marked **Waiting for approval**, and you get an email. A number on the Testimonials menu item shows how many are waiting.
- **Approving:** open the review and click **Publish** to put it on the site, or **Bin** to delete it. Nothing goes live until you publish it.
- **Slideshow:** 5-star reviews (and older ones with no rating set) rotate in the "What our customers are saying" banner at the top of the Reviews page. To leave a review out, set its Rating to something else.
- **Review invitations:** after ordering, customers see "Enjoy your food? Tell us what you thought" with a button straight to the review form. The confirmation email includes the same link.
- **Adding your own:** click **Add testimonial**. Put the customer's name in the title box and the quote in the text box below, set **Rating** and **Order** (lowest shows first), then click **Publish**.

### News timeline (WP Admin → News timeline)

The News page shows a timeline of milestones, newest first.
- **Add an entry:** click **Add entry**. Give it a title and some text, set **Date on the timeline** (past dates are fine), and optionally add a **Photo**. Click **Publish**.
- **Edit or remove:** click an entry to edit it. Switch it to **Draft** to hide it, or move it to the **Bin**.

### Contact page & enquiries (WP Admin → Enquiries)

The Contact page shows your phone, email, website, delivery hours and social links, plus a message form. Name and contact number are required. Every message is emailed to the address in **Settings → Ghar Masala → Send new orders to** and is also saved in **WP Admin → Enquiries**, so nothing gets lost.

## Local SEO (getting found on Google)

The theme now:
- gives each section its own address: `/menu/`, `/my-story/`, `/reviews/`, `/faqs/`, `/allergens/`, `/how-it-works/`, `/contact/` and `/news/`. Each has its own Google title and description, such as "Menu — Indian & Bangladeshi Curry Delivery in Tividale". The site still switches between them instantly.
- adds a short local introduction and the areas you deliver to on the home page.
- tells Google about the business in its own format (schema.org): a halal Indian/Bangladeshi restaurant in Tividale, with phone, delivery area (3 miles), delivery hours (from Opening times), the full menu with prices, the FAQs, and links to Facebook and Instagram.
- adds all those addresses to the sitemap at `/wp-sitemap.xml`.

Change the wording, town, areas and cuisine in **Appearance → Customize → Ghar Masala → Search engines (Google)**. If you install an SEO plugin (Yoast, Rank Math…), the theme leaves the title, description and canonical tags to that plugin.

Outside the website:
1. **Google Business Profile** (business.google.com) matters most for the map results. Set it up as a service-area business (hide your address), category "Indian takeaway" or "Bangladeshi restaurant", list the same areas, and use exactly the same name and phone number as the site.
2. **Google Search Console:** add ghar-masala.co.uk, then submit `https://ghar-masala.co.uk/wp-sitemap.xml`.
3. **Settings → Reading:** make sure "Discourage search engines from indexing this site" is **unticked**.
4. Ask happy customers for **Google reviews**, and list the business with the same name and phone number on Just Eat/Deliveroo, Yell, Facebook and Nextdoor.

## Rebuilding the zip

```sh
./build.sh   # writes dist/ghar-masala.zip
```

## How the delivery distance is measured

Distances are measured in a straight line ("as the crow flies") between postcodes. The site looks postcodes up on [postcodes.io](https://postcodes.io), a free service that runs on Royal Mail / Ordnance Survey data and needs no account. Road distances are usually a bit longer than straight-line ones. If you'd rather charge by road distance, that needs a paid Google Maps key.

If postcodes.io is ever down, customers can still order. In that case the order is marked "Delivery: Not checked" in the email and the dashboard, so you can confirm the charge yourself.
