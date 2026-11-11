<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductsAttribute;
use App\Models\Rating;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\Cart\CartService;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ProductsController extends Controller
{
    /**
     * Category page. Category URLs are registered as routes, so the URI names the category.
     */
    public function listing(Request $request, ProductCatalog $catalog): View|JsonResponse
    {
        $category = Category::query()
            ->with(['section', 'subCategories' => fn ($query) => $query->orderBy('id')])
            ->where('url', $request->route()->uri())
            ->where('status', 1)
            ->firstOrFail();
        $parent = $category->parent_id > 0 ? Category::query()->where('status', 1)->find($category->parent_id) : null;
        $scoped = $catalog->scoped(['categories' => $category->idsWithChildren()]);
        $filters = CatalogFilters::fromRequest($request);
        $products = $catalog->paginate($scoped, $filters);

        if ($request->boolean('fragment')) {
            return $this->gridFragment($products);
        }

        return view('front.products.listing', [
            'heading' => $category->category_name,
            'intro' => $category->description,
            'category' => $category,
            'subCategories' => $category->subCategories,
            'products' => $products,
            'filters' => $filters,
            'facets' => $catalog->facets($scoped),
            'breadcrumbs' => array_values(array_filter([
                $category->section ? ['label' => $category->section->name, 'url' => null] : null,
                $parent ? ['label' => $parent->category_name, 'url' => url($parent->url)] : null,
                ['label' => $category->category_name, 'url' => null],
            ])),
            'meta_title' => $category->meta_title ?: $category->category_name,
            'meta_description' => $category->meta_description,
            'meta_keywords' => $category->meta_keywords,
        ]);
    }

    public function detail(int $id, ProductCatalog $catalog, DeliveryOptions $delivery, CartService $cart): View
    {
        $product = Product::query()
            ->visible()
            ->withCardData()
            ->with([
                'section:id,name',
                'images' => fn ($query) => $query->where('status', 1),
                'vendor.businessDetails',
            ])
            ->findOrFail($id);

        $variants = $product->activeVariants();
        $selected = $product->defaultVariant();
        $vendor = $product->vendor_id > 0 ? $product->vendor : null;
        $ratingBreakdown = Rating::query()
            ->where('product_id', $product->id)
            ->where('status', 1)
            ->groupBy('rating')
            ->selectRaw('rating, COUNT(*) as total')
            ->pluck('total', 'rating');
        $user = Auth::user();

        return view('front.products.detail', [
            'product' => $product,
            'variants' => $variants,
            'selectedVariant' => $selected,
            'variantPrices' => $variants->mapWithKeys(function (ProductsAttribute $variant) use ($product, $cart): array {
                $price = $product->price($variant);

                return [$variant->size => [
                    'final' => formatMoney($price->final),
                    'original' => formatMoney($price->original),
                    'discount' => $price->discountPercent(),
                    'stock' => $variant->stock,
                    'inBag' => $cart->quantityOf($product, $variant->size),
                ]];
            })->all(),
            'price' => $product->price($selected),
            'bagQuantity' => $selected ? $cart->quantityOf($product, $selected->size) : 0,
            'ratings' => Rating::query()->with('user:id,name')->where('product_id', $product->id)->where('status', 1)->latest()->limit(20)->get(),
            'ratingBreakdown' => $ratingBreakdown,
            'questions' => ProductQuestion::query()
                ->with('user:id,name')
                ->where('product_id', $product->id)
                ->where('status', 1)
                ->where(fn ($query) => $query->whereNotNull('answer')->when($user, fn ($own) => $own->orWhere('user_id', $user->id)))
                ->latest()
                ->limit(20)
                ->get(),
            'canReview' => $user !== null
                && $user->hasPurchased($product)
                && Rating::query()->where('user_id', $user->id)->where('product_id', $product->id)->doesntExist(),
            'store' => $vendor ? $this->storeSummary($vendor) : null,
            'moreFromStore' => $catalog->scoped(['vendor' => (int) $product->vendor_id])->withCardData()->whereKeyNot($product->id)->orderByDesc('sold_count')->limit(12)->get(),
            'similarProducts' => $catalog->scoped(['categories' => [(int) $product->category_id]])->withCardData()->whereKeyNot($product->id)->orderByDesc('sold_count')->limit(12)->get(),
            'recentlyViewed' => $this->recordAndListRecentlyViewed($product),
            'deliveryCity' => $delivery->selectedCity(),
            'deliveryFee' => $delivery->fee($product->price($selected)->final, (float) $product->product_weight, $delivery->selectedCity(), null),
            'freeDeliveryThreshold' => Setting::number('free_delivery_threshold'),
            'codEnabled' => Setting::enabled('cod_enabled'),
            'breadcrumbs' => array_values(array_filter([
                $product->section ? ['label' => $product->section->name, 'url' => null] : null,
                $product->category ? ['label' => $product->category->category_name, 'url' => url($product->category->url)] : null,
                ['label' => $product->product_name, 'url' => null],
            ])),
            'meta_title' => $product->meta_title ?: $product->product_name,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
        ]);
    }

    /**
     * Variant picker for a quick add from a product card with more than one option.
     */
    public function options(int $id, CartService $cart): View
    {
        $product = Product::query()->visible()->withCardData()->findOrFail($id);

        return view('front.products.options', [
            'product' => $product,
            'variantQuantities' => $product->activeVariants()
                ->mapWithKeys(fn (ProductsAttribute $variant): array => [$variant->size => $cart->quantityOf($product, $variant->size)])
                ->all(),
        ]);
    }

    /**
     * The old numeric vendor page now lives at the store's own URL.
     */
    public function vendorListing(int $vendorid): RedirectResponse
    {
        $vendor = Vendor::query()->listed()->findOrFail($vendorid);

        return redirect()->route('stores.show', $vendor->slug, 301);
    }

    /**
     * @return array{vendor: Vendor, followers: int, products: int, positive_percent: ?int, ratings: int, joined: string}
     */
    private function storeSummary(Vendor $vendor): array
    {
        $ratings = Rating::query()
            ->where('status', 1)
            ->whereIn('product_id', Product::query()->select('id')->where('vendor_id', $vendor->id))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive')
            ->first();
        $total = (int) $ratings->total;

        return [
            'vendor' => $vendor,
            'followers' => $vendor->followers()->count(),
            'products' => Product::query()->visible()->where('vendor_id', $vendor->id)->count(),
            'positive_percent' => $total > 0 ? (int) round($ratings->positive / $total * 100) : null,
            'ratings' => $total,
            'joined' => $vendor->created_at?->format('M Y') ?? '',
        ];
    }

    /**
     * @return Collection<int, Product>
     */
    private function recordAndListRecentlyViewed(Product $product): Collection
    {
        $sessionId = Session::get('session_id');

        if (! is_string($sessionId) || $sessionId === '') {
            $sessionId = Session::getId();
            Session::put('session_id', $sessionId);
        }

        DB::table('recently_viewed_products')->updateOrInsert(
            ['product_id' => $product->id, 'session_id' => $sessionId],
            ['updated_at' => now(), 'created_at' => now()],
        );

        $recentIds = DB::table('recently_viewed_products')
            ->where('session_id', $sessionId)
            ->where('product_id', '!=', $product->id)
            ->orderByDesc('updated_at')
            ->limit(12)
            ->pluck('product_id');

        return Product::query()->visible()->withCardData()->whereIn('products.id', $recentIds)->get()
            ->sortBy(fn (Product $recent): int => (int) $recentIds->search($recent->id))
            ->values();
    }

    private function gridFragment(LengthAwarePaginator $products): JsonResponse
    {
        return response()->json([
            'html' => view('front.products.grid_items', ['products' => $products])->render(),
            'next' => $products->nextPageUrl(),
        ]);
    }
}
