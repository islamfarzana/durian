<?php

use App\Models\Category;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('/admin')->namespace('App\Http\Controllers\Admin')->group(function () {
    Route::match(['get', 'post'], 'login', 'AdminController@login');

    Route::group(['middleware' => ['admin'], 'guard' => 'admin'], function () {
        Route::get('dashboard', 'AdminController@dashboard');
        Route::get('logout', 'AdminController@logout');
        Route::match(['get', 'post'], 'update-admin-password', 'AdminController@updateAdminPassword');
        Route::post('check-admin-password', 'AdminController@checkAdminPassword');
        Route::match(['get', 'post'], 'update-admin-details', 'AdminController@updateAdminDetails');
        Route::post('delete-admin-image/{id}', 'AdminController@deleteAdminImage');
        Route::match(['get', 'post'], 'add-edit-admin/{id?}', 'AdminController@addEditAdmin')->middleware('perform:admin-add-edit');
        Route::get('admins/{type?}', 'AdminController@admins')->middleware('perform:admin-list');
        Route::post('update-admin-status', 'AdminController@updateAdminStatus')->middleware('perform:admin-status');

        Route::match(['get', 'post'], 'update-vendor-details/{slug}', 'AdminController@updateVendorDetails')->middleware('perform:vendor-edit');
        Route::post('update-vendor-commission', 'AdminController@updateVendorCommission')->middleware('perform:vendor-comission');
        Route::post('update-vendor-official', 'AdminController@updateVendorOfficial')->middleware('perform:vendor-official');
        Route::get('view-vendor-details/{id}', 'AdminController@viewVendorDetails')->middleware('perform:vendor-show');

        Route::get('store-profile', 'StoreProfileController@edit')->middleware('perform:store-edit');
        Route::post('store-profile', 'StoreProfileController@update')->middleware('perform:store-edit');

        Route::get('sections', 'SectionController@sections')->middleware('perform:section-list');
        Route::post('update-section-status', 'SectionController@updateSectionStatus')->middleware('perform:section-status');
        Route::post('delete-section/{id}', 'SectionController@deleteSection')->middleware('perform:section-delete');
        Route::match(['get', 'post'], 'add-edit-section/{id?}', 'SectionController@addEditSection')->middleware('perform:section-add-edit');

        Route::get('categories', 'CategoryController@categories')->middleware('perform:category-list');
        Route::post('update-category-status', 'CategoryController@updateCategoryStatus')->middleware('perform:category-status');
        Route::match(['get', 'post'], 'add-edit-category/{id?}', 'CategoryController@addEditCategory')->middleware('perform:category-add-edit');
        Route::get('append-categories-level', 'CategoryController@appendCategoryLevel');
        Route::post('delete-category/{id}', 'CategoryController@deleteCategory')->middleware('perform:category-delete');
        Route::post('delete-category-image/{id}', 'CategoryController@deleteCategoryImage')->middleware('perform:category-edit');

        Route::get('brands', 'BrandController@brands')->middleware('perform:brand-list');
        Route::post('update-brand-status', 'BrandController@updateBrandStatus')->middleware('perform:brand-status');
        Route::post('delete-brand/{id}', 'BrandController@deleteBrand')->middleware('perform:brand-delete');
        Route::match(['get', 'post'], 'add-edit-brand/{id?}', 'BrandController@addEditBrand')->middleware('perform:brand-add-edit');

        Route::get('products', 'ProductsController@products')->middleware('perform:product-list');
        Route::post('update-product-status', 'ProductsController@updateProductStatus')->middleware('perform:product-status');
        Route::post('delete-product/{id}', 'ProductsController@deleteProduct')->middleware('perform:product-delete');
        Route::match(['get', 'post'], 'add-edit-product/{id?}', 'ProductsController@addEditProduct')->middleware('perform:product-add-edit');
        Route::post('delete-product-image/{id}', 'ProductsController@deleteProductImage')->middleware('perform:product-edit');
        Route::post('delete-product-video/{id}', 'ProductsController@deleteProductVideo')->middleware('perform:product-edit');

        Route::match(['get', 'post'], 'add-edit-attributes/{id}', 'ProductsController@addAttributes')->middleware('perform:attribute-add-edit');
        Route::post('update-attribute-status', 'ProductsController@updateAttributeStatus')->middleware('perform:attribute-status');
        Route::post('delete-attribute/{id}', 'ProductsController@deleteAttribute')->middleware('perform:attribute-delete');
        Route::match(['get', 'post'], 'edit-attributes/{id}', 'ProductsController@editAttributes')->middleware('perform:attribute-edit');

        Route::match(['get', 'post'], 'add-images/{id}', 'ProductsController@addImages')->middleware('perform:product-edit');
        Route::post('update-image-status', 'ProductsController@updateImageStatus')->middleware('perform:product-edit');
        Route::post('delete-image/{id}', 'ProductsController@deleteImage')->middleware('perform:product-edit');

        Route::get('banners', 'BannersController@banners')->middleware('perform:banner-list');
        Route::post('update-banner-status', 'BannersController@updateBannerStatus')->middleware('perform:banner-status');
        Route::post('delete-banner/{id}', 'BannersController@deleteBanner')->middleware('perform:banner-delete');
        Route::match(['get', 'post'], 'add-edit-banner/{id?}', 'BannersController@addEditBanner')->middleware('perform:banner-add-edit');

        Route::get('filters', 'FilterController@filters')->middleware('perform:filter-list');
        Route::post('update-filter-status', 'FilterController@updateFilterStatus')->middleware('perform:filter-status');
        Route::post('update-filter-value-status', 'FilterController@updateFilterValueStatus')->middleware('perform:filter_value-status');
        Route::get('filters-values', 'FilterController@filtersValues')->middleware('perform:filter_value-list');
        Route::match(['get', 'post'], 'add-edit-filter/{id?}', 'FilterController@addEditFilter')->middleware('perform:filter-add-edit');
        Route::match(['get', 'post'], 'add-edit-filter-value/{id?}', 'FilterController@addEditFilterValue')->middleware('perform:filter_value-add-edit');
        Route::post('category-filters', 'FilterController@categoryFilters');

        Route::get('coupons', 'CouponsController@coupons')->middleware('perform:coupon-list');
        Route::post('update-coupon-status', 'CouponsController@updateCouponStatus')->middleware('perform:coupon-status');
        Route::post('delete-coupon/{id}', 'CouponsController@deleteCoupon')->middleware('perform:coupon-delete');
        Route::match(['get', 'post'], 'add-edit-coupon/{id?}', 'CouponsController@addEditCoupon')->middleware('perform:coupon-add-edit');

        Route::get('flash-sales', 'FlashSaleController@index')->middleware('perform:flash_sale-list');
        Route::match(['get', 'post'], 'add-edit-flash-sale/{id?}', 'FlashSaleController@addEdit')->whereNumber('id')->middleware('perform:flash_sale-edit');
        Route::get('flash-sales/{id}/items', 'FlashSaleController@items')->whereNumber('id')->middleware('perform:flash_sale-list');
        Route::post('flash-sales/{id}/items', 'FlashSaleController@storeItem')->whereNumber('id')->middleware('perform:flash_sale-join');
        Route::post('flash-sale-items/{itemId}/status', 'FlashSaleController@updateItemStatus')->whereNumber('itemId')->middleware('perform:flash_sale-status');
        Route::post('flash-sale-items/{itemId}/delete', 'FlashSaleController@destroyItem')->whereNumber('itemId')->middleware('perform:flash_sale-join');

        Route::get('payouts', 'PayoutController@index')->middleware('perform:payout-list');
        Route::post('payouts', 'PayoutController@store')->middleware('perform:payout-request');
        Route::post('payouts/{id}', 'PayoutController@update')->whereNumber('id')->middleware('perform:payout-update');

        Route::get('questions', 'ProductQuestionController@index')->middleware('perform:question-list');
        Route::post('questions/{id}/answer', 'ProductQuestionController@answer')->whereNumber('id')->middleware('perform:question-answer');
        Route::post('questions/{id}/status', 'ProductQuestionController@updateStatus')->whereNumber('id')->middleware('perform:question-status');

        Route::get('users', 'UserController@users')->middleware('perform:user-list');
        Route::post('update-user-status', 'UserController@updateUserStatus')->middleware('perform:user-status');

        Route::get('orders', 'OrderController@orders')->middleware('perform:order-list');
        Route::get('orders/{id}', 'OrderController@orderDetails')->middleware('perform:order-show');
        Route::post('update-order-status', 'OrderController@updateOrderStatus')->middleware('perform:order-status');
        Route::post('update-order-item-status', 'OrderController@updateOrderItemStatus')->middleware('perform:order-item_status');
        Route::get('orders/invoice/{id}', 'OrderController@viewOrderInvoice')->middleware('perform:order-invoice');
        Route::get('orders/invoice/pdf/{id}', 'OrderController@viewPDFInvoice')->middleware('perform:order-invoice_pdf');

        Route::get('shipping-charges', 'ShippingController@shippingCharges')->middleware('perform:shipping-list');
        Route::post('update-shipping-status', 'ShippingController@updateShippingStatus')->middleware('perform:shipping-status');
        Route::match(['get', 'post'], 'add-edit-shipping-charges/{id?}', 'ShippingController@addEditShippingCharges')->middleware('perform:shipping-add-edit');

        Route::get('delivery-slots', 'DeliverySlotController@index')->middleware('perform:delivery_slot-list');
        Route::match(['get', 'post'], 'add-edit-delivery-slot/{id?}', 'DeliverySlotController@addEdit')->whereNumber('id')->middleware('perform:delivery_slot-edit');
        Route::post('delivery-slots/{id}/status', 'DeliverySlotController@updateStatus')->whereNumber('id')->middleware('perform:delivery_slot-status');

        Route::get('marketplace-settings', 'MarketplaceSettingsController@edit')->middleware('perform:setting-edit');
        Route::post('marketplace-settings', 'MarketplaceSettingsController@update')->middleware('perform:setting-edit');

        Route::get('subscribers', 'NewsletterController@subscribers')->middleware('perform:newsletter-list');
        Route::post('update-subscriber-status', 'NewsletterController@updateSubscriberStatus')->middleware('perform:newsletter-status');
        Route::post('delete-subscriber/{id}', 'NewsletterController@deleteSubscriber')->middleware('perform:newsletter-delete');
        Route::get('export-subscribers', 'NewsletterController@exportSubscribers')->middleware('perform:newsletter-show');

        Route::get('ratings', 'RatingController@ratings')->middleware('perform:rating-list');
        Route::post('update-rating-status', 'RatingController@updateRatingStatus')->middleware('perform:rating-status');
        Route::post('delete-rating/{id}', 'RatingController@deleteRating')->middleware('perform:rating-delete');
        Route::post('ratings/{id}/reply', 'RatingController@reply')->whereNumber('id')->middleware('perform:rating-reply');

        Route::get('countries', 'SettingsController@countries')->middleware('perform:setting-list');
        Route::match(['get', 'post'], 'add-edit-country/{id?}', 'SettingsController@addEditCountry')->middleware('perform:setting-edit');
        Route::post('update-country-status', 'SettingsController@updateCountryStatus')->middleware('perform:setting-status');
        Route::post('delete-country/{id}', 'SettingsController@deleteCountry')->middleware('perform:setting-delete');

        Route::get('themes', 'ThemeController@index');
        Route::get('themes/create', 'ThemeController@create');
        Route::post('themes', 'ThemeController@store');
        Route::get('themes/example-zip', 'ThemeController@example');
        Route::post('themes/upload', 'ThemeController@upload');
        Route::get('themes/{id}/edit', 'ThemeController@edit')->whereNumber('id');
        Route::post('themes/{id}', 'ThemeController@update')->whereNumber('id');
        Route::post('themes/{id}/activate', 'ThemeController@activate')->whereNumber('id');
        Route::post('themes/{id}/delete', 'ThemeController@destroy')->whereNumber('id');

        Route::get('cities', 'SettingsController@Cities')->middleware('perform:setting-list');
        Route::match(['get', 'post'], 'add-edit-city/{id?}', 'SettingsController@addEditCity')->middleware('perform:setting-edit');
        Route::post('update-city-status', 'SettingsController@updateCityStatus')->middleware('perform:setting-status');
        Route::post('delete-city/{id}', 'SettingsController@deleteCity')->middleware('perform:setting-delete');
    });
});

Route::get('orders/invoice/download/{id}', 'App\Http\Controllers\Admin\OrderController@viewPDFInvoice');

Route::namespace('App\Http\Controllers\Front')->group(function () {
    Route::get('/', 'IndexController@index')->name('home');
    Route::get('just-for-you', 'IndexController@justForYou')->name('home.just-for-you');

    try {
        $catUrls = Category::query()
            ->where('status', 1)
            ->whereNotIn('url', Category::reservedUrls())
            ->distinct()
            ->pluck('url');
    } catch (Throwable) {
        $catUrls = collect();
    }

    foreach ($catUrls as $url) {
        if (! is_string($url) || preg_match('/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/', $url) !== 1) {
            continue;
        }

        Route::get('/'.$url, 'ProductsController@listing');
    }

    Route::get('search', 'SearchController@index')->name('search');
    Route::get('search/suggest', 'SearchController@suggest')->name('search.suggest');
    Route::get('search-products', 'SearchController@legacy');
    Route::get('search-suggestion', 'SearchController@suggest');

    Route::get('product/{id}', 'ProductsController@detail')->whereNumber('id')->name('products.show');
    Route::get('product/{id}/options', 'ProductsController@options')->whereNumber('id')->name('products.options');
    Route::get('products/{vendorid}', 'ProductsController@vendorListing')->whereNumber('vendorid');

    Route::get('stores', 'StoreController@index')->name('stores.index');
    Route::get('store/{vendor:slug}', 'StoreController@show')->name('stores.show');
    Route::get('flash-sale', 'FlashSaleController@index')->name('flash-sale');
    Route::get('offers', 'OfferController@index')->name('offers');

    Route::get('cart', 'CartController@show')->name('cart');
    Route::get('cart/panel', 'CartController@panel')->name('cart.panel');
    Route::post('cart/items', 'CartController@store')->name('cart.items.store');
    Route::post('cart/add', 'CartController@store');
    Route::delete('cart/items/{cart}', 'CartController@destroy')->whereNumber('cart')->name('cart.items.destroy');
    Route::post('cart/voucher', 'CartController@applyVoucher')->name('cart.voucher.store');
    Route::delete('cart/voucher', 'CartController@removeVoucher')->name('cart.voucher.destroy');
    Route::post('delivery-area', 'DeliveryAreaController@update')->name('delivery-area.update');

    Route::get('vendor/login-register', 'VendorController@loginRegister')->name('seller.register');
    Route::post('vendor/register', 'VendorController@vendorRegister');
    Route::get('vendor/confirm/{code}', 'VendorController@confirmVendor');

    Route::get('user/login-register', ['as' => 'login', 'uses' => 'UserController@loginRegister']);
    Route::post('user/register', 'UserController@userRegister');
    Route::post('user/login', 'UserController@userLogin');
    Route::get('user/logout', 'UserController@userLogout')->name('logout');
    Route::match(['get', 'post'], 'user/forgot-password', 'UserController@forgotPassword')->name('password.forgot');

    Route::get('user/confirm/{code}', 'UserController@confirmAccount');
    Route::match(['get', 'post'], 'contact', 'CmsController@contact')->name('contact');
    Route::post('add-subscriber-email', 'NewsletterController@addSubscriber');

    Route::post('add-rating', 'RatingController@addRating')->name('ratings.store');

    Route::group(['middleware' => ['auth']], function () {
        Route::get('user/dashboard', 'UserController@userDashboard')->name('account');
        Route::match(['GET', 'POST'], 'user/account', 'UserController@userAccount')->name('account.profile');
        Route::post('user/update-password', 'UserController@userUpdatePassword');
        Route::get('user/addresses', 'AddressController@index')->name('account.addresses');
        Route::get('user/stores', 'UserController@followedStores')->name('account.stores');
        Route::get('user/orders/{id?}', 'OrderController@orders')->whereNumber('id')->name('orders');
        Route::post('user/orders/{order}/cancel', 'OrderController@cancel')->whereNumber('order')->name('orders.cancel');

        Route::get('wishlist', 'WishlistController@index')->name('wishlist');
        Route::post('wishlist/{product}', 'WishlistController@toggle')->whereNumber('product')->name('wishlist.toggle');
        Route::post('store/{vendor:slug}/follow', 'StoreController@follow')->name('stores.follow');
        Route::post('product/{product}/questions', 'ProductQuestionController@store')->whereNumber('product')->name('products.questions.store');

        Route::get('checkout', 'CheckoutController@show')->name('checkout');
        Route::post('checkout', 'CheckoutController@store')->name('checkout.store');
        Route::get('thanks', 'CheckoutController@thanks')->name('checkout.thanks');
        Route::post('get-delivery-address', 'AddressController@getDeliveryAddress');
        Route::post('save-delivery-address', 'AddressController@saveDeliveryAddress')->name('addresses.save');
        Route::post('remove-delivery-address', 'AddressController@removeDeliveryAddress')->name('addresses.remove');

        Route::get('paypal', 'PaypalController@paypal')->name('paypal');
        Route::post('pay', 'PaypalController@pay')->name('payment');
        Route::get('success', 'PaypalController@success')->name('paypal.success');
        Route::get('error', 'PaypalController@error')->name('paypal.error');
    });
});
