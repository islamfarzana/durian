<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\Catalog\CatalogFilters;
use App\Services\Catalog\ProductCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Curated collections reachable from the homepage and the old search links.
     *
     * @var array<string, array{heading: string, scope: array<string, bool>, sort: ?string}>
     */
    private const COLLECTIONS = [
        'new' => ['heading' => 'New arrivals', 'scope' => [], 'sort' => 'newest'],
        'bestsellers' => ['heading' => 'Best sellers', 'scope' => [], 'sort' => 'popular'],
        'featured' => ['heading' => 'Featured products', 'scope' => ['featured' => true], 'sort' => null],
        'deals' => ['heading' => 'Top deals', 'scope' => ['discounted' => true], 'sort' => 'discount'],
    ];

    public function index(Request $request, ProductCatalog $catalog): View|JsonResponse
    {
        $term = Str::limit(trim((string) $request->input('q', '')), 100, '');
        $collection = self::COLLECTIONS[(string) $request->input('collection')] ?? null;
        $scope = $collection['scope'] ?? [];

        if ($term !== '') {
            $scope['search'] = $term;
        }

        if ($collection !== null && ! $request->filled('sort')) {
            $request->merge(['sort' => $collection['sort'] ?? 'popular']);
        }

        $scoped = $catalog->scoped($scope);
        $filters = CatalogFilters::fromRequest($request);
        $products = $catalog->paginate($scoped, $filters);

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view('front.products.grid_items', ['products' => $products])->render(),
                'next' => $products->nextPageUrl(),
            ]);
        }

        $heading = $collection['heading'] ?? ($term !== '' ? 'Results for “'.$term.'”' : 'All products');

        return view('front.products.listing', [
            'heading' => $heading,
            'intro' => null,
            'category' => null,
            'subCategories' => collect(),
            'products' => $products,
            'filters' => $filters,
            'facets' => $catalog->facets($scoped),
            'searchTerm' => $term,
            'breadcrumbs' => [['label' => $heading, 'url' => null]],
            'meta_title' => $heading,
        ]);
    }

    /**
     * Old search URLs used ?search=, with a few reserved words for collections.
     */
    public function legacy(Request $request): RedirectResponse
    {
        $term = (string) $request->input('search', '');
        $collections = ['new-arrivals' => 'new', 'best-sellers' => 'bestsellers', 'featured' => 'featured', 'discounted' => 'deals'];

        if (array_key_exists($term, $collections)) {
            return redirect()->route('search', ['collection' => $collections[$term]], 301);
        }

        return redirect()->route('search', array_filter(['q' => $term]), 301);
    }

    /**
     * Live suggestions under the header search box.
     */
    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', $request->input('query', '')));

        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'stores' => []]);
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        $products = Product::query()
            ->visible()
            ->withCardData()
            ->where(fn ($query) => $query->where('product_name', 'like', $like)->orWhere('product_code', 'like', $like))
            ->orderByDesc('sold_count')
            ->limit(6)
            ->get()
            ->map(fn (Product $product): array => [
                'name' => $product->product_name,
                'url' => $product->url(),
                'image' => $product->imageUrl('small'),
                'price' => formatMoney($product->price($product->defaultVariant())->final),
                'pack' => $product->packLabel(),
            ]);

        $categories = Category::query()
            ->where('status', 1)
            ->where('category_name', 'like', $like)
            ->orderBy('category_name')
            ->limit(4)
            ->get(['category_name', 'url'])
            ->map(fn (Category $category): array => ['name' => $category->category_name, 'url' => url($category->url)]);

        $stores = Vendor::query()
            ->listed()
            ->with('businessDetails:id,vendor_id,shop_name')
            ->where(fn ($query) => $query->where('vendors.name', 'like', $like)
                ->orWhereHas('businessDetails', fn ($details) => $details->where('shop_name', 'like', $like)))
            ->limit(3)
            ->get()
            ->map(fn (Vendor $vendor): array => ['name' => $vendor->store_name, 'url' => $vendor->storeUrl(), 'official' => $vendor->is_official]);

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'stores' => $stores,
            'all' => route('search', ['q' => $term]),
        ]);
    }
}
