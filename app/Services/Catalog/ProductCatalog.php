<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the product listings: category pages, search, stores and flash sales
 * all share one set of filters and sorts.
 */
class ProductCatalog
{
    public const PER_PAGE = 24;

    /**
     * Listing price after the product or category discount, matching Product::price().
     */
    private const EFFECTIVE_PRICE_SQL = 'products.product_price * (1 - (CASE WHEN products.product_discount > 0 THEN products.product_discount ELSE COALESCE((SELECT categories.category_discount FROM categories WHERE categories.id = products.category_id), 0) END) / 100)';

    private const DISCOUNT_SQL = 'GREATEST(products.product_discount, COALESCE((SELECT categories.category_discount FROM categories WHERE categories.id = products.category_id), 0))';

    /**
     * Visible products narrowed to a listing's scope, before shopper filters.
     *
     * @param  array{categories?: array<int, int>, vendor?: int, search?: string, discounted?: bool, flash_sale?: int, featured?: bool, new?: bool}  $scope
     * @return Builder<Product>
     */
    public function scoped(array $scope): Builder
    {
        $query = Product::query()->visible();

        if (! empty($scope['categories'])) {
            $query->whereIn('products.category_id', $scope['categories']);
        }

        if (isset($scope['vendor'])) {
            $query->where('products.vendor_id', $scope['vendor']);
        }

        if (! empty($scope['discounted'])) {
            $query->whereRaw(self::DISCOUNT_SQL.' > 0');
        }

        if (! empty($scope['featured'])) {
            $query->where('products.is_featured', 'Yes');
        }

        if (isset($scope['flash_sale'])) {
            $query->whereHas('flashSaleItems', fn (Builder $item) => $item->where('flash_sale_id', $scope['flash_sale'])->where('status', 1));
        }

        if (filled($scope['search'] ?? null)) {
            $this->search($query, (string) $scope['search']);
        }

        return $query;
    }

    /**
     * @param  Builder<Product>  $scoped
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(Builder $scoped, CatalogFilters $filters, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = (clone $scoped)->withCardData();
        $this->filter($query, $filters);
        $this->sort($query, $filters->sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Filter options with counts, taken from the listing before shopper filters
     * so choosing one brand does not hide the others.
     *
     * @param  Builder<Product>  $scoped
     * @return array{brands: Collection<int, array{id: int, name: string, count: int}>, stores: Collection<int, array{id: int, name: string, count: int}>, price: array{min: float, max: float}}
     */
    public function facets(Builder $scoped): array
    {
        $brandCounts = (clone $scoped)->reorder()->groupBy('products.brand_id')->selectRaw('products.brand_id, COUNT(*) as total')->pluck('total', 'brand_id');
        $storeCounts = (clone $scoped)->reorder()->where('products.vendor_id', '>', 0)->groupBy('products.vendor_id')->selectRaw('products.vendor_id, COUNT(*) as total')->pluck('total', 'vendor_id');
        $range = (clone $scoped)->reorder()->selectRaw('MIN(products.product_price) as min_price, MAX(products.product_price) as max_price')->first();

        $brands = Brand::query()->whereIn('id', $brandCounts->keys())->where('status', 1)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Brand $brand): array => ['id' => (int) $brand->id, 'name' => (string) $brand->name, 'count' => (int) $brandCounts[$brand->id]]);
        $stores = Vendor::query()->with('businessDetails:id,vendor_id,shop_name')->whereIn('id', $storeCounts->keys())->get(['id', 'name'])
            ->map(fn (Vendor $vendor): array => ['id' => (int) $vendor->id, 'name' => $vendor->store_name, 'count' => (int) $storeCounts[$vendor->id]])
            ->sortBy('name')->values();

        return [
            'brands' => $brands,
            'stores' => $stores,
            'price' => ['min' => floor((float) ($range->min_price ?? 0)), 'max' => ceil((float) ($range->max_price ?? 0))],
        ];
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function filter(Builder $query, CatalogFilters $filters): void
    {
        $query
            ->when($filters->brands !== [], fn (Builder $q) => $q->whereIn('products.brand_id', $filters->brands))
            ->when($filters->stores !== [], fn (Builder $q) => $q->whereIn('products.vendor_id', $filters->stores))
            ->when($filters->minPrice !== null, fn (Builder $q) => $q->whereRaw(self::EFFECTIVE_PRICE_SQL.' >= ?', [$filters->minPrice]))
            ->when($filters->maxPrice !== null, fn (Builder $q) => $q->whereRaw(self::EFFECTIVE_PRICE_SQL.' <= ?', [$filters->maxPrice]))
            ->when($filters->discounted, fn (Builder $q) => $q->whereRaw(self::DISCOUNT_SQL.' > 0'))
            ->when($filters->inStock, fn (Builder $q) => $q->whereHas('variants', fn (Builder $variant) => $variant->where('status', 1)->where('stock', '>', 0)))
            ->when($filters->official, fn (Builder $q) => $q->where(fn (Builder $seller) => $seller
                ->where('products.vendor_id', 0)
                ->orWhereIn('products.vendor_id', Vendor::query()->select('id')->where('is_official', true))))
            ->when($filters->rating !== null, fn (Builder $q) => $q->whereRaw(
                '(SELECT AVG(ratings.rating) FROM ratings WHERE ratings.product_id = products.id AND ratings.status = 1) >= ?',
                [$filters->rating],
            ));
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function sort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->orderByDesc('products.id'),
            'price_asc' => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' asc'),
            'price_desc' => $query->orderByRaw(self::EFFECTIVE_PRICE_SQL.' desc'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'discount' => $query->orderByRaw(self::DISCOUNT_SQL.' desc'),
            default => $query->orderByDesc('products.sold_count')->orderByDesc('products.is_bestseller'),
        };

        $query->orderByDesc('products.id');
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function search(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($term)).'%';

        $query->where(function (Builder $match) use ($like): void {
            $match->where('products.product_name', 'like', $like)
                ->orWhere('products.product_code', 'like', $like)
                ->orWhere('products.product_color', 'like', $like)
                ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like))
                ->orWhereHas('category', fn (Builder $category) => $category->where('category_name', 'like', $like));
        });
    }

    /**
     * Products for a homepage or detail-page rail.
     *
     * @return Collection<int, Product>
     */
    public function rail(string $kind, int $limit = 12, array $scope = []): Collection
    {
        $query = $this->scoped($scope)->withCardData();

        match ($kind) {
            'bestsellers' => $query->orderByDesc('products.sold_count')->orderByDesc('products.id'),
            'deals' => $query->whereRaw(self::DISCOUNT_SQL.' > 0')->orderByRaw(self::DISCOUNT_SQL.' desc')->orderByDesc('products.id'),
            'featured' => $query->where('products.is_featured', 'Yes')->orderByDesc('products.id'),
            default => $query->orderByDesc('products.id'),
        };

        return $query->limit($limit)->get();
    }
}
