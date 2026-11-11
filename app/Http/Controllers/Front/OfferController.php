<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    /**
     * Vouchers anyone can use, then everything on discount.
     */
    public function index(Request $request, ProductCatalog $catalog): View|JsonResponse
    {
        $request->mergeIfMissing(['sort' => 'discount']);
        $scoped = $catalog->scoped(['discounted' => true]);
        $filters = CatalogFilters::fromRequest($request);
        $deals = $catalog->paginate($scoped, $filters);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('front.products.grid_items', ['products' => $deals])->render(),
                'next' => $deals->nextPageUrl(),
            ]);
        }

        return view('front.promotions.offers', [
            'vouchers' => Coupon::query()
                ->publiclyListed()
                ->with('vendor.businessDetails')
                ->orderBy('vendor_id')
                ->orderBy('min_spend')
                ->get(),
            'deals' => $deals,
            'filters' => $filters,
            'facets' => $catalog->facets($scoped),
            'meta_title' => 'Offers & vouchers',
        ]);
    }
}
