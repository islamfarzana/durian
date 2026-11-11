<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Rating;
use App\Models\Vendor;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use App\Services\Storefront\ShopperContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    /**
     * Directory of approved stores, official stores first.
     */
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q', ''));

        $stores = Vendor::query()
            ->listed()
            ->with('businessDetails:id,vendor_id,shop_name,shop_city')
            ->withCount(['followers', 'products' => fn (Builder $products) => $products->where('status', 1)])
            ->when($request->boolean('official'), fn (Builder $query) => $query->where('is_official', true))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->where('vendors.name', 'like', '%'.$term.'%')
                ->orWhereHas('businessDetails', fn (Builder $details) => $details->where('shop_name', 'like', '%'.$term.'%'))))
            ->orderByDesc('is_official')
            ->orderByDesc('followers_count')
            ->orderBy('id')
            ->paginate(24)
            ->withQueryString();

        return view('front.stores.index', [
            'stores' => $stores,
            'term' => $term,
            'meta_title' => 'Stores',
        ]);
    }

    public function show(Request $request, Vendor $vendor, ProductCatalog $catalog, ShopperContext $shopper): View|JsonResponse
    {
        abort_unless($vendor->status === 1, 404);

        $vendor->load('businessDetails')->loadCount('followers');
        $scoped = $catalog->scoped(['vendor' => $vendor->id]);
        $filters = CatalogFilters::fromRequest($request);
        $products = $catalog->paginate($scoped, $filters);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('front.products.grid_items', ['products' => $products])->render(),
                'next' => $products->nextPageUrl(),
            ]);
        }

        $ratings = Rating::query()
            ->where('status', 1)
            ->whereIn('product_id', Product::query()->select('id')->where('vendor_id', $vendor->id))
            ->selectRaw('COUNT(*) as total, AVG(rating) as average, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive')
            ->first();

        return view('front.stores.show', [
            'vendor' => $vendor,
            'products' => $products,
            'filters' => $filters,
            'facets' => $catalog->facets($scoped),
            'topProducts' => $catalog->rail('bestsellers', 10, ['vendor' => $vendor->id]),
            'vouchers' => Coupon::query()->publiclyListed()->where('vendor_id', $vendor->id)->orderBy('min_spend')->get(),
            'isFollowing' => in_array($vendor->id, $shopper->followedStoreIds(), true),
            'stats' => [
                'products' => (clone $scoped)->count(),
                'ratings' => (int) $ratings->total,
                'average' => $ratings->total > 0 ? round((float) $ratings->average, 1) : null,
                'positive_percent' => $ratings->total > 0 ? (int) round($ratings->positive / $ratings->total * 100) : null,
            ],
            'meta_title' => $vendor->store_name,
            'meta_description' => $vendor->tagline,
        ]);
    }

    public function follow(Request $request, Vendor $vendor): JsonResponse|RedirectResponse
    {
        abort_unless($vendor->status === 1, 404);

        $result = $request->user()->followedStores()->toggle($vendor->id);
        $following = $result['attached'] !== [];
        $message = $following ? 'You are following '.$vendor->store_name.'.' : 'You unfollowed '.$vendor->store_name.'.';

        if ($request->expectsJson()) {
            return response()->json([
                'following' => $following,
                'followers' => $vendor->followers()->count(),
                'message' => $message,
            ]);
        }

        return back()->with('success_message', $message);
    }
}
