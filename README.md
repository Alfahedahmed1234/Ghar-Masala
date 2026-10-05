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
4. Leave **Pay on delivery** ticked to offer both options, or untick it so customers can only pay by card.

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
| Dishes, prices, descriptions, badges | `ghar-masala/inc/data.php` → `gm_menu()` |
| Allergen table | `ghar-masala/inc/data.php` → `gm_allergen_table()` |
| Minimum order, slot times, closed days, 7pm cutoff | `ghar-masala/inc/data.php` → `gm_rules()` |
| Page wording (story, FAQs, how it works…) | `ghar-masala/front-page.php` |
| Footer (social links, hours, company details) | `ghar-masala/template-parts/site-footer.php` |
| Colours and fonts | top of `ghar-masala/style.css` |
| Logo | **Appearance → Customize → Site Identity → Logo**, or replace `assets/images/logo.png` |
| News | publish ordinary **Posts**; the News page lists the latest ten |

You can make these edits in **Appearance → Theme File Editor**, or edit them in this repo and upload a new zip. Every dish needs a unique `id`. Do not reuse an old id for a different dish, because past orders and "Order again" refer to dishes by id.

Testimonials are still the placeholders from the design. Replace them in `front-page.php` once real reviews come in.

## Rebuilding the zip

```sh
./build.sh   # writes dist/ghar-masala.zip
```

## How the delivery distance is measured

Distances are measured in a straight line ("as the crow flies") between postcodes. The site looks postcodes up on [postcodes.io](https://postcodes.io), a free service that runs on Royal Mail / Ordnance Survey data and needs no account. Road distances are usually a bit longer than straight-line ones. If you'd rather charge by road distance, that needs a paid Google Maps key.

If postcodes.io is ever down, customers can still order. In that case the order is marked "Delivery: Not checked" in the email and the dashboard, so you can confirm the charge yourself.
