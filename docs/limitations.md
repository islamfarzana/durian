# Limitations

Durian is a Laravel monolith for one marketplace. The products below are commerce platforms with a module boundary, an admin product, and years of edge cases already built. This page says where Durian is thinner, and where the comparison is a different kind of software rather than a missing feature.

## Reference products

| Product | What it actually is |
| --- | --- |
| [Bagisto](https://bagisto.com/) | Laravel store and optional marketplace. Packages for catalog, checkout, admin, and a Velocity theme. Multi-locale, multi-currency, and multi-channel are in the core. |
| [Lunar](https://lunarphp.io/) | Laravel commerce engine aimed at headless shops. Cart, pricing, and tax run through pipelines. The storefront is yours to build. The staff panel is included. |
| [Vue Storefront](https://vuestorefront.io/) (Alokai) | Storefront only. Vue and Nuxt sit on someone else's commerce API (commercetools, Magento, Shopware, and others). It does not own catalog or orders. |
| [Aimeos](https://aimeos.org/) | PHP commerce framework used with Laravel, Symfony, or TYPO3. JSON API, multi-vendor, multi-warehouse, and a large set of product and service types. |
| [nopCommerce](https://www.nopcommerce.com/) | ASP.NET Core store. Plugins for payment, shipping, and tax. Multi-store, multi-language, and a vendor marketplace through its own plugin model. |

## Capability comparison

| Capability | Durian | Bagisto | Lunar | Vue Storefront | Aimeos | nopCommerce |
| --- | --- | --- | --- | --- | --- | --- |
| Storefront included | Blade + plain JavaScript | Blade theme | No (headless) | Yes (the whole product) | Yes, plus headless | Yes (Razor) |
| Admin included | Custom Blade panel | Full admin | Staff panel | No | jqadm | Full admin |
| Multi-vendor | Store pages, follows, Seller Center, commission ledger | Marketplace package | Not core | Depends on the backend | Core | Marketplace plugin |
| Headless API | Passport trait only; `routes/api.php` is empty | REST and GraphQL | First-class | Consumes an API | JSON REST | Web API plugin |
| Themes | CSS variables and five layout niches; zip is JSON + CSS | Theme packages that can replace views | Storefront is external | Theme is the app | Theme layer | Theme plugins |
| Plugins / packages | None | Laravel packages | Extenders and pipelines | Integrations | Sub-parts you compose | Plugin system and marketplace |
| Catalog model | Sections, categories, pack-size and variant rows, filters | Attributes, configurable and grouped products | Products, variants, collections, pricing | Whatever the backend exposes | Attribute-based, bundles, vouchers, events | Attributes, specification attributes, downloadable goods |
| Multi-currency / locale | Single store; BDT on the storefront, PayPal charged in USD at a fixed rate | Yes | Yes | Yes, if the backend has it | Yes | Yes |
| Multi-channel / multi-store | One site | Channels | Channels | Multiple storefronts on one backend | Yes | Multi-store |
| Tax | Not a tax engine | Tax categories and rates | Tax zone pipelines | Backend | Tax rules | Tax providers and plugins |
| Shipping | City and country weight brackets, delivery slots | Carriers and rates | Shipping modifiers | Backend | Services and warehouses | Shipping plugins |
| Inventory | Stock integer on a variant row, locked at checkout | Inventory sources | Stock per variant | Backend | Warehouses and stock | Warehouses and inventory methods |
| Promotions | One voucher per bag (marketplace or store), flash sales | Cart rules and catalog rules | Discount pipelines | Backend | Vouchers and rules | Discounts and plugins |
| Vendor payouts | Ledger and manual payout requests | Marketplace settlements | Not applicable | Not applicable | Vendor payments | Vendor commission plugins |
| Search | SQL `LIKE` and suggestion endpoint | Elasticsearch optional | Scout / driver of your choice | Backend search | Indexes | Lucene / external search |
| Tests and extension points | Feature tests for cart, checkout, vouchers, flash sales, stores, reviews, and vendor isolation; no extension points | Package tests, events | Pipelines designed to be extended | Contract tests per integration | PHPUnit around sub-parts | Plugin isolation |

## Where Durian is thinner

### It is one application, not a platform

Bagisto, Aimeos, and nopCommerce split catalog, checkout, and admin into packages or plugins. A payment provider or a tax rule can be added without editing the checkout controller. Durian cart, checkout, and order creation live in `CartService`, `PlaceOrderRequest`, and `PlaceOrder`. A new payment method means editing the request's gateway list, `CheckoutController`, and the checkout view.

Lunar is also Laravel, but cart calculation is a pipeline of modifiers. Durian totals are calculated in `CartService` and again in `PlaceOrder`, not through a pipeline.

### The API is not a product

Vue Storefront and Lunar assume a stable commerce API. Aimeos and Bagisto ship one. Durian documents an API in the README and includes Passport, but `routes/api.php` is empty and there is no API controller namespace. A mobile app or a Vue Storefront-style front cannot sit on this codebase until that API exists.

### Themes cannot change the page structure

Bagisto and nopCommerce themes can replace templates. Aimeos themes can replace the HTML clients. A Durian zip may set colors, fonts, a niche, and a stylesheet. The five niches restyle the same storefront pages. A grocery zip cannot introduce a new product-detail layout, a new checkout, or new sections of HTML. That limit is deliberate: uploaded PHP or Blade would be remote code execution.

### Multi-vendor stops at manual payouts

A vendor has a store page, followers, store vouchers, flash sale entries, and a ledger with payout requests. Missing, relative to Bagisto Marketplace, Aimeos, and nopCommerce vendors:

- No automated settlement. An admin marks a payout paid after moving the money outside the app, and there is no invoice from the marketplace to the vendor.
- No vendor-specific shipping, tax, or payment account
- No vendor subdomain or separate domain
- Most products in `database/durian.sql` use `vendor_id = 0`. The optional demo seeder adds four stores so the marketplace pages have sellers.
- A vendor with no business-profile row still has a public store page; the store name falls back to the vendor's own name

### Catalog, money, and geography are single-store

There is one language, one currency in practice, one warehouse (a stock number), and no tax classes. Bagisto, Aimeos, and nopCommerce treat locale, currency, tax, and channel as data. Lunar treats price and tax as pipeline outputs so a second currency is a modifier, not a rewrite.

Storefront prices go through `formatMoney()` and the currency symbol setting. There is still no currency table or conversion, so a second currency means more than changing that symbol. PayPal converts taka to dollars at a fixed rate in `PaypalController`.

### Admin operations are bespoke

nopCommerce and Bagisto admins cover ACL as data, import and export, and scheduled jobs. Durian's permissions are a PHP config file and a gate. Admin deletes are POST forms with a CSRF token. Category URLs are still registered from the database when routes load, with reserved paths and duplicate URLs excluded. A new category is not reachable until the route file runs again, which `php artisan route:cache` freezes until the cache is rebuilt.

### Search, performance, and scale

Listing pages query MySQL directly and render full HTML. There is no search index, no fragment cache around the catalog, and the queue driver is `sync`. Aimeos and the larger Bagisto and nopCommerce installs expect indexes, queues, and stock reservations. Durian will serve a small catalog. Checkout locks the variant rows, so two orders cannot both take the last unit, but nothing is reserved while it sits in a bag. There is no design for a large filter index.

## What Durian is good at instead

The comparison is not "Durian failed to be nopCommerce." Those products are platforms. Durian is a readable Laravel app:

- One storefront and one admin, both Blade, both easy to trace
- Vendor and customer accounts already separated by guard
- A working path from product to cart to cash-on-delivery order
- A theme switch that a superadmin can operate without a deploy, within the CSS and niche limits above

Use Bagisto, Lunar, Aimeos, or nopCommerce when the requirement is a platform: plugins, channels, a real API, or automated vendor settlement. Use Vue Storefront only after a commerce API exists. Keep Durian when the requirement is this marketplace, this admin, and a small team that can change the controllers.
