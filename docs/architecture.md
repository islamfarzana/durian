# Architecture

Durian is a single Laravel 13 application on PHP 8.4 and MySQL. The storefront and the admin panel are server-rendered Blade pages. There is no separate storefront app, no module loader, and no public API surface.

## Shape

```text
Browser
  │
  ├─ /                  storefront (web guard, session cart)
  ├─ /admin             admin, vendor (Seller Center), and superadmin (admin guard)
  └─ /api               route file exists; no endpoints are registered

public/index.php
  └─ HTTP Kernel
       ├─ routes/web.php
       └─ routes/api.php   (empty)
```

Controllers live in two groups:

- `App\Http\Controllers\Front` — home, catalog, search, cart, checkout, stores, flash sale, offers, wishlist, product questions, customer account, vendor registration, ratings, contact, newsletter
- `App\Http\Controllers\Admin` — catalog, orders, vendors, store profile, banners, shipping, delivery slots, coupons, flash sales, payouts, product questions, ratings, marketplace settings, and storefront themes

Views follow the same split: `resources/views/front` and `resources/views/admin`. The storefront loads one stylesheet, `public/front/css/durian.css`, and one script, `public/front/js/durian.js`. Neither uses jQuery. Shared storefront pieces are anonymous Blade components in `resources/views/components/shop`: product card, store card, voucher, stars, logo, and icons. The old Porto files are still in `public/front`, but no storefront page loads them. The admin UI is a separate Blade layout under `public/admin`.

Storefront rules live in `App\Services`. Admin controllers still hold most admin rules.

| Folder | What it holds |
| --- | --- |
| `Catalog` | `ProductCatalog` (listing queries), `CatalogFilters` (filters and sort from the query string), and `Price` (variant price, product discount, flash sale price) |
| `Cart` | `CartService` (the bag), `VoucherCalculator`, and the line and summary value objects |
| `Delivery` | `DeliveryOptions`: delivery days, slots, the delivery fee, and the free-delivery threshold |
| `Checkout` | `PlaceOrder`: locks stock, re-prices the bag, and writes the order |
| `Orders` | `CancelOrder`: cancels an order and puts the stock back |
| `Vendors` | `VendorLedger`: a vendor's sales, commission, and payout balance |
| `Storefront` | `ShopperContext`: the customer, bag, favourites, and followed stores for one request |
| `Themes` | Theme resolution, CSS cleanup, and zip install |

## Accounts

Two session guards share one browser session.

| Guard | Table | Who |
| --- | --- | --- |
| `web` | `users` | Customers |
| `admin` | `admins` | `superadmin`, `admin`, `subadmin`, `vendor` |

A vendor is an `admins` row with `type = vendor` plus a `vendors` row. The `vendors` row also holds the public store profile: slug, logo, banner, tagline, about text, the official-store flag, and holiday mode. Shop details and bank details are separate tables (`vendors_business_details`, `vendors_bank_details`). Customers never use the admin guard.

The storefront always asks for the customer through the `web` guard. An admin who is signed in to `/admin` browses the shop as a guest.

`/admin` routes require the `admin` middleware. Admin actions also pass `perform:{model}-{action}`, which calls the `manage` gate. A superadmin passes every check. Other admin types are limited by `config/permission.php`. A vendor's controllers also scope every query to that vendor, so a vendor cannot open another vendor's product, coupon, review, or order line by changing an id in the URL.

Customer login, registration, and password reset are custom front controllers. Laravel Breeze controllers also exist under `App\Http\Controllers\Auth` and are not the storefront login.

`User` uses the Passport `HasApiTokens` trait. `routes/api.php` registers no routes, so tokens have nothing to call.

## Catalog

The catalog is a fixed tree, not an attribute engine.

```text
Section
  └─ Category (parent_id = 0)
       └─ Category (child)
            └─ Product
                 ├─ products_attributes   pack size or variant: size, price, stock, SKU
                 ├─ products_images
                 └─ products_filters / products_filters_values
```

A product is sold through its `products_attributes` rows. Each row is one pack size or variant (500 g, 1 kg, 128 GB, size M) with its own price and stock. A product with no active row cannot be added to the bag. Products also carry a unit, country of origin, return days, warranty, and a sold count.

Products point at a brand and, when sold by a seller, a `vendor_id`. Platform-owned products use `vendor_id = 0` and show the marketplace name as the seller.

Category URLs are registered while `routes/web.php` loads: every active category `url` becomes a storefront route. Two categories with the same URL cannot both be reachable, and reserved paths such as `cart`, `store`, and `flash-sale` are skipped.

Category pages, store pages, the flash sale, offers, and search build their product lists with `ProductCatalog`. Filters (brand, store, price, rating, discount, in stock, official store) and sorting are query-string parameters, so a filtered page has its own URL. Search suggestions come from `GET /search/suggest`.

## Cart and checkout

The cart is rows in `carts`, keyed by session id for guests and by user id after login. Login copies the session cart onto the customer. `CartService` adds, sets, and removes lines and returns the bag as packages, one per store. The storefront calls it through JSON endpoints (`POST /cart/items`, `DELETE /cart/items/{id}`, `POST /cart/voucher`), and each response carries the new bag panel HTML.

A voucher is a `coupons` row. A platform voucher (`vendor_id = 0`) applies to the whole bag. A store voucher applies only to that store's lines. A voucher can have a minimum spend, a maximum discount, start and end dates, a usage limit, and category or customer restrictions. `VoucherCalculator` checks all of these when the voucher is applied and again when the order is placed.

Checkout is `POST /checkout` behind the `web` guard. `PlaceOrderRequest` checks that the address belongs to the customer, the payment method is enabled, the terms are accepted, and the chosen delivery slot is still open. `PlaceOrder` then works in one transaction. It locks each variant and flash sale row, re-prices the bag, re-checks the voucher, writes the order, and reduces stock.

Delivery:

- The customer picks a day within the next few days and a slot from `delivery_slots` that starts after the lead time. Both numbers are marketplace settings.
- The delivery fee is zero once the bag reaches the free-delivery threshold. Otherwise it is the weight bracket in `shipping_charges` for the address city, then the country, then the default fee in settings.
- Delivery cities come from the `cities` table. The `cod_pincodes` and `prepaid_pincodes` tables are no longer read.

A successful order writes:

- `orders` — customer snapshot, totals, status, payment method, delivery date and slot, and notes
- `orders_products` — line items, including `vendor_id` and commission
- `orders_logs` and status tables for later updates

Cash on delivery completes inside the app and can be switched off in settings. PayPal is offered only when `PAYPAL_CLIENT_ID` is set. It is a separate front controller (`PaypalController`) using Omnipay. Iyzico is a Composer dependency and is not a checkout step.

A customer can cancel an order while it is still `New`, `Order Placed`, or `Pending`. `CancelOrder` marks every line cancelled and puts the stock back.

Invoices are rendered in the admin order controller (HTML and Dompdf). Download is allowed for the owning customer, a non-vendor admin, or a vendor who has a line on that order.

## Vendors

Vendor registration is public (`/vendor/login-register`) and stays unconfirmed until an admin approves it. A confirmed vendor signs in at `/admin`, which shows the Seller Center to vendors. A vendor manages their own store profile, products, vouchers, order lines, review replies, product answers, flash sale entries, and payout requests. They do not manage sections, categories, brands, delivery slots, or global shipping.

Each approved vendor with a slug has a store page at `/store/{slug}`. The old `/products/{vendorid}` URL redirects there. Customers can follow a store. An admin marks official stores. Holiday mode hides the store's products and stops new orders.

Commission is a percentage on the vendor row, copied onto order lines when an order is placed. `VendorLedger` adds up a vendor's lines:

| Figure | Meaning |
| --- | --- |
| Gross | Every open line the vendor sold |
| Commission | The marketplace's share of gross |
| Cleared | Net sales on delivered lines |
| Paid / requested | Payouts already paid, and payouts waiting for an admin |
| Available | Cleared minus paid minus requested |

A vendor requests a payout of at least the minimum payout amount, up to the available balance. An admin marks it paid or rejects it. The money itself still moves outside the app, using the stored bank details.

Flash sales are admin-created time windows. A vendor submits their products with a discount and an optional stock limit. An admin approves or rejects each entry. While a sale runs, `Price` uses the approved discount, and `PlaceOrder` stops selling once the stock limit is reached.

A vendor may set `vendors.theme_id`. Their store page uses that theme. Every other page uses the one active marketplace theme.

## Themes

Themes are rows in `store_themes`, not separate codebases.

| Piece | Role |
| --- | --- |
| `ThemeCatalog` | Five niches: classic, fashion, grocery, electronics, marketplace. Each niche has default colors, fonts, header style, and card style. |
| `ThemeManager` | Picks the vendor theme on a store page, otherwise the active theme. |
| `durian.css` | The storefront stylesheet. It reads colors, fonts, and radius from the theme's `--store-*` CSS variables. |
| `ThemeZipInstaller` | Installs a zip of `theme.json`, optional `assets/theme.css`, and an optional preview image. |

A view composer shares the resolved theme with every `front.*` view. The layout prints CSS variables, a Google font link, uploaded CSS, and sanitized custom CSS. The `<body>` carries `store-theme`, `store-theme-{niche}`, `header-style-{style}`, and `card-style-{style}` classes for theme CSS to target. The marketplace migrations add the built-in Durian Market theme and make it active, unless a theme other than Porto Classic was already active.

Uploaded zips cannot contain PHP, Blade, or paths outside the theme folder. A zip changes settings and CSS. It does not replace the storefront templates.

Built-in themes are locked. They can be edited and activated. They cannot be deleted or overwritten by a zip.

## Request path for a storefront page

1. `routes/web.php` matches the URL. Category routes were already built from the database.
2. The front controller loads products through `ProductCatalog`, or the bag through `CartService`.
3. View composers resolve `StoreTheme` once per request and share the navigation, the bag, favourites, followed stores, the delivery city, and marketplace settings.
4. Blade extends `front.layout.layout`, which emits the theme and includes the header, category sidebar, content, footer, and bag panel.
5. `durian.js` handles the bag and quantity steppers, search suggestions, favourites, follows, filters, countdowns, and the checkout slot picker.

Admin pages skip the storefront theme. They use `admin.layout.layout` and the admin session page name to highlight the sidebar.

## Data and background work

The schema was imported from `database/durian.sql` and then extended by migrations: `store_themes`, `vendors.theme_id`, and `categories.home_category_menu`, then the marketplace migrations dated 2026-10-28. Those add store profiles, product details, wishlists, store follows, flash sales, product questions, voucher rules, delivery slots, order delivery columns, vendor payouts, settings, seller replies on ratings, default order statuses, and the Durian Market theme. Older migrations were marked as already applied on this database so `php artisan migrate` does not try to recreate existing tables.

Marketplace settings (store name, hotline, delivery fee, free-delivery threshold, cash on delivery, delivery days, slot lead time, minimum payout) are rows in `settings`. `Setting::get()` reads them with defaults and caches them. Times are stored in UTC and shown in `MARKETPLACE_TIMEZONE` (Asia/Dhaka by default).

Queues use the `sync` driver. Mail is configured for SMTP. There is no scheduler workload for settlements, search indexes, or abandoned carts. Images are written under `public/` (product images, store logos and banners, proofs, theme files), not through a storage disk abstraction.

## What this architecture is for

One team can run one marketplace: catalog, stores, promotions, cart, delivery slots, cash on delivery, a vendor ledger, and a swappable look. The boundaries are the two guards, the product tables, the services, and the theme row. Anything that needs another channel, another currency, a headless client, or a third-party plugin has to be built into these controllers and services.
