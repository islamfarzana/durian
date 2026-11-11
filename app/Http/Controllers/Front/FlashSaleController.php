<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlashSaleController extends Controller
{
    public function index(Request $request, ProductCatalog $catalog): View|JsonResponse
    {
        $current = FlashSale::query()->running()->orderBy('ends_at')->first();
        $catalogData = [];

        if ($current !== null) {
            $scoped = $catalog->scoped(['flash_sale' => $current->id]);
            $filters = CatalogFilters::fromRequest($request);
            $products = $catalog->paginate($scoped, $filters);

            if ($request->boolean('fragment')) {
                return response()->json([
                    'html' => view('front.products.grid_items', ['products' => $products])->render(),
                    'next' => $products->nextPageUrl(),
                ]);
            }

            $catalogData = ['products' => $products, 'filters' => $filters, 'facets' => $catalog->facets($scoped)];
        }

        return view('front.promotions.flash_sale', $catalogData + [
            'flashSale' => $current,
            'upcoming' => FlashSale::query()->upcoming()->withCount('approvedItems')->orderBy('starts_at')->limit(3)->get(),
            'meta_title' => 'Flash Sale',
        ]);
    }
}
