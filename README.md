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
| Minimum order (£10) | `ghar-masala/inc/data.php` → `gm_rules()` |
| Page wording (story, FAQs, how it works…) | `ghar-masala/front-page.php` |
| Footer (social links, hours, company details) | `ghar-masala/template-parts/site-footer.php` |
| Colours and fonts | top of `ghar-masala/style.css` |
| Logo | **Appearance → Customize → Site Identity → Logo**, or replace `assets/images/logo.png` |
| News | publish ordinary **Posts**; the News page lists the latest ten |
| Testimonials | **WP Admin → Testimonials** (see below) |

For the rows marked as files, use **Appearance → Theme File Editor**, or edit them in this repo and upload a new zip.

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
- **Adding your own:** click **Add testimonial**. Put the customer's name in the title box and the quote in the text box below, set **Rating** and **Order** (lowest shows first), then click **Publish**.

### News timeline (WP Admin → News timeline)

The News page shows a timeline of milestones, newest first.
- **Add an entry:** click **Add entry**. Give it a title and some text, set **Date on the timeline** (past dates are fine), and optionally add a **Photo**. Click **Publish**.
- **Edit or remove:** click an entry to edit it. Switch it to **Draft** to hide it, or move it to the **Bin**.

### Contact page & enquiries (WP Admin → Enquiries)

The Contact page shows your phone, email, website, delivery hours and social links, plus a message form. Name and contact number are required. Every message is emailed to the address in **Settings → Ghar Masala → Send new orders to** and is also saved in **WP Admin → Enquiries**, so nothing gets lost.

## Rebuilding the zip

```sh
./build.sh   # writes dist/ghar-masala.zip
```

## How the delivery distance is measured

Distances are measured in a straight line ("as the crow flies") between postcodes. The site looks postcodes up on [postcodes.io](https://postcodes.io), a free service that runs on Royal Mail / Ordnance Survey data and needs no account. Road distances are usually a bit longer than straight-line ones. If you'd rather charge by road distance, that needs a paid Google Maps key.

If postcodes.io is ever down, customers can still order. In that case the order is marked "Delivery: Not checked" in the email and the dashboard, so you can confirm the charge yourself.
