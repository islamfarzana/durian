# Future improvements

These are ordered by how much they change Durian from a single Blade shop into something you can operate and extend. They follow the gaps in [Limitations](limitations.md). They do not try to clone Bagisto, Lunar, Aimeos, or nopCommerce.

## 1. Make money and stock consistent

- One money formatter and one currency code, used by the cart, checkout, invoices, and emails. The storefront now uses `formatMoney()`. Invoices and emails still format money on their own.
- Reserve stock when an order is placed, and release it when an order is cancelled. `PlaceOrder` now locks the variant rows and reduces stock, and a customer cancellation puts it back. A cancellation set from the admin order screen does not restock yet.
- A tax line on the order, even if the first version is one rate per country. Without it, later multi-vendor payouts cannot split tax.

Bagisto and nopCommerce already model this as data. The smallest Durian version is one currency config value, a stock decrement in the order transaction, and a `tax_amount` column.

## 2. Finish vendor commerce

- Require a business profile before a vendor shop is public.
- Assign every product to a real vendor, or mark platform-owned products explicitly instead of `vendor_id = 0`.
- A ledger: order line commission, vendor balance, and a manual "mark as paid" payout. Done: `VendorLedger` and payout requests. Automated gateway splits can wait.
- Vendor order screens should show only that vendor's lines and totals. Done for lines: a vendor sees only their own lines and can change only their status.

This is the difference between "vendors can log in" and a marketplace an operator can settle. Bagisto Marketplace and nopCommerce vendor plugins are the reference behavior, not the code to copy.

## 3. Turn checkout into replaceable steps

Cart math and vouchers now live in `CartService` and `VoucherCalculator`, and order creation in `PlaceOrder`. Payment is still chosen inside `CheckoutController`. Split the rest into steps with a single input and output:

1. Load cart
2. Apply coupon
3. Resolve address and shipping
4. Apply tax
5. Take payment
6. Write the order and decrement stock

A new payment method then implements the payment step. PayPal and cash on delivery become two implementations. Iyzico, already in Composer, can be a third. Lunar's cart pipelines are the model: modifiers in a list, not a longer controller method.

## 4. Publish a real API, or stop claiming one

Passport is on the user model and `routes/api.php` is empty. Either:

- Add versioned read endpoints for catalog, cart, and orders, with policies that match the web guards, or
- Remove the API claim from the README until that exists

Do this before any Vue Storefront or mobile client. Those clients need stable resources (product, cart, order), pagination, and token scopes. A handful of controllers that return the same arrays the Blade pages use will not survive a second client.

## 5. Safer admin and routing

- Change admin delete and status links from GET to POST with CSRF, matching the theme activate and delete forms.
- Stop registering category URLs inside `routes/web.php`. Serve listings from one route such as `/category/{url}` so a duplicate or reserved slug cannot shadow `/cart` or `/checkout`.
- Enforce unique category URLs in validation.
- Point PHPUnit at a database that is not the running shop. The suite currently uses `durian_db`. `tests/TestCase.php` now fails any test class that uses a trait that drops tables, but a separate test database is still the real fix.

## 6. Themes that can change layout without executing uploads

The zip installer correctly refuses PHP and Blade. The next step is still safe:

- More homepage and listing partials chosen by niche, so grocery and electronics differ in structure, not only in CSS
- A documented `theme.json` schema and a validator the admin form and the zip installer share
- Preview a theme on the storefront with a query flag before activating it for everyone

Full template overrides, the way Bagisto and nopCommerce themes work, need a reviewed template sandbox. Do not accept Blade in uploaded zips.

## 7. Search and catalog depth

- Faceted search over the existing filter tables, with an index when the catalog leaves the current size
- Product types beyond "one product, many size rows": at least a simple variant (size and color as one sellable unit) and a clear out-of-stock state. Pack sizes and the out-of-stock state are done. Size and color as one unit are not.
- Import and export for products and vendors. Excel export already exists for newsletter subscribers; the catalog does not have the same path

Aimeos and Bagisto treat attributes as data. Durian can stay simpler: variants as rows, filters as the facet source, and Scout or the database driver when search gets slow.

## 8. Operate the shop

- Queue mail, invoice PDFs, and any future payout job. The queue is `sync` today, so a slow mail server blocks checkout.
- Cache the header category tree. It is loaded on every storefront view.
- Replace the committed third-party mail and payment secrets in `.env.example` with empty placeholders, and rotate any credential that was ever real.
- An admin report for orders per vendor and commission due. That report is the operator view of the ledger in item 2.

## Suggested order of work

| Order | Work | Why first |
| --- | --- | --- |
| 1 | Currency, stock reservation, unique category route | Correctness of the shop that already runs |
| 2 | GET-to-POST admin actions, test database | Safety of the people operating it |
| 3 | Vendor profile requirement and product ownership | The marketplace claim |
| 4 | Checkout steps and payment implementations | Room for PayPal and Iyzico without another large controller |
| 5 | HTTP API | Only after cart and order rules live outside the Blade controllers |
| 6 | Theme partials and preview | Look and layout, after the money path is stable |

Items 5 and 6 are how this app could later sit beside a Vue Storefront-style client or a richer theme. They are wasted if stock, tax, and vendor balances are still inline in one controller.
