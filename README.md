# Laravel E-commerce Application (Mega Project)
Multi-vendor E-commerce is a large-scale project/application built with Laravel framework. The application contains comprehensive and feature-rich modules and functionalities. It is designed to provide a robust platform for businesses to create their online marketplaces, allowing multiple vendors to sell their products and manage their stores within a single platform. Additionally, the application has its own dedicated extensive API, which requires authentication using Laravel Passport package.

The storefront is Blade with one stylesheet (`public/front/css/durian.css`) and one plain JavaScript file (`public/front/js/durian.js`). It does not use jQuery. The admin panel uses jQuery, AJAX, and jQuery plugins.

## Features:
1- Third-pary API Integration (Shiprocket API integration (for shipping and order management services)).

2- PayPal Payment Gateway Integration.

3- Iyzico Payment Gateway Integration.

4- A dedicated extensive API with multiple different endpoints for the application.

5- API authentication using Laravel Passport package.

6- Webhook implemented for inventory/stock update.

7 - Using PHP cURL.

8- Multiple Authentication using Laravel Guards.

9- Multi-level Relationships/Categories.

10- Product Dynamic Filters (using AJAX).

11- Shipping Charges Module (third-party service API integration, product-weight and country-wise shipping charges, etc).

12- Showing Order Shipping Status.

13- Vendor Commissions Module.

14- Coupon Codes Module (single time/multiple times, percentage/fixed).

15- Star Rating and Reviews System.

16- Recently Viewed Products Feature.

17- Order Logs/History.

18- New Arrivals, Discounted Products, Featured Products, Similar Products, and Best-Seller Products Features.

19- Using external libraries and packages such as 'Intervention Image' for image manipulation, 'Dompdf' library for printing PDF order invoices, 'Laravel Excel' package for importing/exporting database tables as Excel files, 'Laravel Barcode/QR Code Generator' to generate barcodes and QR codes for both Product ID and Product Code in order invoices, etc.

20- Using JavaScript libraries and jQuery plugins in the admin panel, such as 'DataTables' for adding interaction controls to HTML tables.

21 - Sending Confirmation Emails (Mailtrap) upon registration, account activation and approval, order shipping status, etc.

22- Sending offline SMSs (upon registration, starting order shipping process, ...).

23- Multiple Delivery Addresses.

24- Website Search Form functionality for products by name, color, and code.

25- User Roles and Permissions (superadmin, admins, vendors, users).

26- User and vendor registration approval by the superadmin.

27- Image & Video Upload Functionality.

28- Dynamically creating and editing Sections and Categories.

29- Dynamic Banner Sliders Module.

30- Dynamic Breadcrumb Navigation.

31- Dynamic SEO/HTML Meta tags.

32- Newsletter Subscription (email).

33- Regular Expression.

34- Database Seeders.

35- Tens of jQuery AJAX requests (update admin password via AJAX, AJAX form validation, ...).

36- A shopping bag docked beside every page that updates without a reload.

37- TinyMCE WYSIWYG Editor Integrated.

38- Using two Favicons for both the Frontend and Admin Panel Sections of the application.

## Marketplace features

The storefront layout follows Chaldal. The marketplace features follow Daraz. Any kind of product can be sold, from groceries to electronics.

Shopping:

- Category sidebar, quick-add product cards with a quantity stepper, and a bag docked beside the page
- Pack sizes and variants on any product (500 g, 1 kg, 128 GB, size M), each with its own price and stock
- Delivery area picker, delivery date and time slot at checkout, and a free-delivery threshold
- Search suggestions, filters and sorting, favourites, and recently viewed products
- Product questions answered by the seller, and reviews only from customers who bought the product
- Orders split into one package per store, with item status and tracking. A customer can cancel before the order is processed, and the stock goes back.

Stores and sellers:

- Store pages at `/store/{slug}` with logo, banner, rating, followers, vouchers, and products
- Customers follow stores. The admin marks official stores. A seller can switch on holiday mode.
- Seller Center (the vendor login at `/admin`): store profile, products, the seller's own order lines, reviews with seller replies, questions, flash sale entries, and earnings
- Earnings ledger: gross sales, commission, delivered sales, and payout requests that an admin marks paid or rejects

Promotions:

- Flash sales with a countdown. Sellers submit products with a discount and a stock limit, and an admin approves them.
- Vouchers for the whole marketplace or for one store, with minimum spend, maximum discount, dates, and usage limits
- An offers page that lists every public voucher

Admin:

- Dashboard with sales, commission, top stores, and the approvals waiting
- Delivery slots and marketplace settings: store name, hotline, delivery fee, free-delivery threshold, cash on delivery, and minimum payout
- Review and question moderation, store approval, and the official-store flag

## Screenshots:

These screenshots show the storefront before the Chaldal-style redesign.
### Frontend Section Homepage:
![frontend-homepage](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/37646610-8c9f-4ac6-8a75-75e83cc469c7)

### Product Listing Page:
![frontend-product-listing-page](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/6a68ba25-ebd0-4b93-b687-487e35bf4912)

### Shopping Cart Page:
![shopping-cart-page](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/64f9cbbf-87d2-4f26-aaf1-5c942d1db85b)

### Checkout Page:
![checkout-page](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/0e4057a8-dd7e-4db5-944d-8d8754b86c32)

### Admin Panel HomePage:
![admin-panel-homepage](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/afda126b-2ab2-4ce8-9f42-2bd6eee36bfa)

### Admin Panel Products Management Page:
![admin-panel-products-management](https://github.com/AhmedYahyaE/laravel-multi-vendor-e-commerce-application/assets/118033266/06d8fd5b-6538-4574-b6f4-c3bf4a6a5c32)

## Application URLs:
1- **Frontend**: The public-facing website can be accessed at http://127.0.0.1:8000/. This is where users/customers/members can view categories and products and interact with the website in general. The frontend URL is typically accessible to all visitors of the website.

2- **Admin Panel**: The Admin Panel for managing the application is available at http://127.0.0.1:8000/admin/login. This secure area is exclusively accessible to authorized administrators where only authenticated superadmin, admins, and vendors can access. It grants access to the administrative functionalities of the application, such as adding new products and their features, orders management, users management, creating and editing website sections and categories, orders shipping management, etc.

## Application Routes and API Endpoints:
All application routes & API endpoints are defined in both the **[web.php](routes/web.php)** file (Frontend and Admin Panel routes) and **[api.php](routes/api.php)** file (API Endpoints).

## API Endpoints:
> ***\*\* Check the application API Collection on my Postman Profile: https://www.postman.com/ahmed-yahya/workspace/my-public-portfolio-postman-workspace/collection/28181483-179adc20-2dcc-426c-a755-5a48da9ca7a4***

> ***\*\* Also, you can test the API Endpoints yourself using Postman. Here is the API's Postman Collection .json file [API Postman Collection file](<Postman Collection of API Endpoints/E-commerce Application API.postman_collection.json>) that you can download and import into your Postman.***

## Local deployment

Requirements: PHP 8.3 or newer, Composer, and MySQL. Storefront and admin assets are already in `public/front` and `public/admin`. `npm install` / `npm run build` are only needed if you change the Vite entrypoints.

From the project root:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

On PowerShell, `copy .env.example .env` is correct. On macOS or Linux, use `cp .env.example .env`.

Set the database values in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=durian_db
DB_USERNAME=root
DB_PASSWORD=root
APP_URL=http://127.0.0.1:8000
```

Create the database and import the dump. PowerShell:

```powershell
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS durian_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
Get-Content database\durian.sql | mysql -u root -proot durian_db
```

bash:

```bash
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS durian_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -proot durian_db < database/durian.sql
```

The dump already creates the shop tables. Apply the later migrations on top of it (themes, vendor theme, homepage category flag):

```bash
php artisan migrate --path=database/migrations/2026_10_11_175544_create_store_themes_table.php --force
php artisan migrate --path=database/migrations/2026_10_11_175545_add_theme_id_to_vendors_table.php --force
php artisan migrate --path=database/migrations/2026_10_11_180121_add_home_category_menu_to_categories_table.php --force
php artisan migrate --path=database/migrations/2026_10_11_183522_make_duplicate_category_urls_unique.php --force
```

Then apply the marketplace migrations (stores, flash sales, vouchers, delivery slots, payouts, settings, and the Durian Market theme). PowerShell:

```powershell
Get-ChildItem database\migrations\2026_10_28_*.php | ForEach-Object { php artisan migrate --path="database/migrations/$($_.Name)" --force }
```

bash:

```bash
for file in database/migrations/2026_10_28_*.php; do php artisan migrate --path="$file" --force; done
```

Do not run a full `php artisan migrate` after the SQL import. The older migration files recreate tables the dump already created. On a database that already has every earlier migration recorded, `php artisan migrate` runs only the new marketplace migrations.

Optional demo data: four stores, 54 grocery and general products with pack sizes, a running flash sale, vouchers, orders, reviews, and questions. It writes its images as `demo-*.svg` files, which git ignores. Running it twice does nothing the second time.

```bash
php artisan db:seed --class=MarketplaceDemoSeeder
```

## Run

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

| Page | URL |
| --- | --- |
| Storefront | http://127.0.0.1:8000 |
| Admin and vendor login | http://127.0.0.1:8000/admin/login |
| Customer login | http://127.0.0.1:8000/user/login-register |
| Storefront themes | http://127.0.0.1:8000/admin/themes |
| All stores | http://127.0.0.1:8000/stores |
| Flash sale | http://127.0.0.1:8000/flash-sale |
| Offers and vouchers | http://127.0.0.1:8000/offers |
| Become a seller | http://127.0.0.1:8000/vendor/login-register |

## Production

On the server, set `APP_ENV=production` and `APP_DEBUG=false`. Put real mail and PayPal values in the server environment, not in git. Then:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `php artisan route:cache` again after a category URL changes. A failed PayPal payment cancels the pending order and puts the stock back. Cash on delivery reduces stock once, inside the order transaction.

## Login credentials

These accounts are in `database/durian.sql`. Every password is `123456`.

| Role | Email | Where to sign in |
| --- | --- | --- |
| Superadmin | superadmin@gmail.com | http://127.0.0.1:8000/admin/login |
| Vendor | gentelpark@mailitrap.com | http://127.0.0.1:8000/admin/login |
| Customer | bella@mailinator.com | http://127.0.0.1:8000/user/login-register |

The demo seeder adds these accounts, also with the password `123456`:

| Role | Email |
| --- | --- |
| Vendor, Green Valley Farms (official store) | greenvalley@durian.test |
| Vendor, Sundarban Fish & Meat | sundarban@durian.test |
| Vendor, Nimbus Electronics (official store) | nimbus@durian.test |
| Vendor, HomeBright Living | homebright@durian.test |
| Customer | ayesha@durian.test, tanvir@durian.test, nusrat@durian.test |

Demo vouchers: `WELCOME100`, `FRESH10`, `NIMBUS200`, and `GREEN5`.

## Tests

```bash
php artisan test --compact
```

The tests run against the database in `.env` (`durian_db`), and each test is rolled back with `DatabaseTransactions`. `tests/TestCase.php` fails any test class that uses `RefreshDatabase`, `LazilyRefreshDatabase`, `DatabaseMigrations`, or `DatabaseTruncation`, because those traits drop every table in that database. The Laravel Breeze tests in `tests/Feature/Auth` use `RefreshDatabase`, so they fail with that message instead of wiping the shop. The storefront login does not use the Breeze controllers.

Architecture, limits against other commerce platforms, and an improvement list are in [docs/README.md](docs/README.md).

## Contribution:
Contributions to my Multi-vendor E-commerce Laravel application are most welcome! If you find any issues or have suggestions for improvements or want to add new features, please open an issue or submit a pull request.
