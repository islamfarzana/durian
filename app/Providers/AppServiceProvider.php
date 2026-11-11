<?php

namespace App\Providers;

use App\Models\Section;
use App\Models\Setting;
use App\Services\Cart\CartService;
use App\Services\Delivery\DeliveryOptions;
use App\Services\Storefront\ShopperContext;
use App\Services\Themes\ThemeManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ThemeManager::class);
        $this->app->scoped(CartService::class);
        $this->app->scoped(ShopperContext::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Paginator::useBootstrap();

        View::composer('front.*', function ($view): void {
            $data = $view->getData();

            if (! array_key_exists('storeTheme', $data)) {
                $view->with('storeTheme', app(ThemeManager::class)->resolveForRequest());
            }

            // Included partials and the layout inherit these from the page view.
            if (array_key_exists('marketplace', $data)) {
                return;
            }

            $delivery = app(DeliveryOptions::class);
            $shopper = app(ShopperContext::class);

            $view->with([
                'navSections' => Section::sections(),
                'customer' => $shopper->customer(),
                'bag' => $shopper->cart(),
                'wishlistCount' => count($shopper->wishlistIds()),
                'wishlistIds' => $shopper->wishlistIds(),
                'followedStoreIds' => $shopper->followedStoreIds(),
                'deliveryCity' => $delivery->selectedCity(),
                'deliveryCities' => $delivery->cities(),
                'marketplace' => [
                    'name' => Setting::get('store_name'),
                    'tagline' => Setting::get('tagline'),
                    'hotline' => Setting::get('hotline'),
                    'email' => Setting::get('support_email'),
                    'free_delivery_threshold' => Setting::number('free_delivery_threshold'),
                    'currency' => Setting::get('currency_symbol'),
                ],
            ]);
        });

        View::composer(['components.shop.product-card', 'front.products.options'], function ($view): void {
            $shopper = app(ShopperContext::class);

            $view->with([
                'bagQuantities' => $shopper->cart()->quantitiesByProduct(),
                'wishlistIds' => $shopper->wishlistIds(),
                'platformSellerName' => Setting::get('store_name'),
            ]);
        });
    }
}
