<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IndexController extends Controller
{
    public function index(ProductCatalog $catalog): View
    {
        $flashSale = FlashSale::query()->running()->orderBy('ends_at')->first();

        return view('front.index', [
            'sliderBanners' => Banner::query()->where('type', 'Slider')->where('status', 1)->orderBy('id')->get(),
            'fixBanners' => Banner::query()->where('type', 'Fix')->where('status', 1)->orderBy('id')->get(),
            'popularCategories' => Category::query()
                ->where('status', 1)
                ->where('parent_id', 0)
                ->orderByDesc('home_category_menu')
                ->orderBy('id')
                ->limit(16)
                ->get(),
            'flashSale' => $flashSale,
            'flashProducts' => $flashSale
                ? $catalog->scoped(['flash_sale' => $flashSale->id])->withCardData()->orderByDesc('products.sold_count')->limit(12)->get()
                : collect(),
            'stores' => Vendor::query()
                ->listed()
                ->with('businessDetails:id,vendor_id,shop_name')
                ->withCount(['followers', 'products' => fn ($products) => $products->where('status', 1)])
                ->orderByDesc('is_official')
                ->orderByDesc('followers_count')
                ->orderBy('id')
                ->limit(12)
                ->get(),
            'bestSellers' => $catalog->rail('bestsellers'),
            'deals' => $catalog->rail('deals'),
            'newArrivals' => $catalog->rail('new'),
            'justForYou' => $catalog->paginate($catalog->scoped([]), new CatalogFilters(sort: 'newest'), 18)
                ->withPath(route('home.just-for-you')),
            'brands' => Brand::query()->where('status', 1)->orderBy('name')->limit(12)->get(),
            'stats' => [
                'products' => Product::query()->visible()->count(),
                'stores' => Vendor::query()->listed()->count(),
                'cities' => City::query()->where('status', 1)->count(),
                'orders' => Order::query()->where('order_status', 'Delivered')->count(),
            ],
            'deliveryFee' => Setting::number('default_delivery_fee'),
            'meta_description' => 'Shop groceries, fresh food, fashion, electronics and everyday essentials from trusted sellers, with cash on delivery and scheduled delivery slots.',
            'meta_keywords' => 'online grocery, online shopping bangladesh, marketplace, cash on delivery',
        ]);
    }

    /**
     * Next page of the homepage "Just for you" grid.
     */
    public function justForYou(ProductCatalog $catalog): JsonResponse
    {
        $products = $catalog->paginate($catalog->scoped([]), new CatalogFilters(sort: 'newest'), 18);

        return response()->json([
            'html' => view('front.products.grid_items', ['products' => $products])->render(),
            'next' => $products->nextPageUrl(),
        ]);
    }
}
