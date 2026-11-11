<?php

namespace App\Services\Catalog;

use Illuminate\Http\Request;

/**
 * Shopper-chosen filters for a product listing, read from the query string.
 */
final readonly class CatalogFilters
{
    public const SORTS = [
        'popular' => 'Most popular',
        'newest' => 'Newest arrivals',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'rating' => 'Top rated',
        'discount' => 'Biggest discount',
    ];

    /**
     * @param  array<int, int>  $brands
     * @param  array<int, int>  $stores
     */
    public function __construct(
        public array $brands = [],
        public array $stores = [],
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
        public ?int $rating = null,
        public bool $discounted = false,
        public bool $inStock = false,
        public bool $official = false,
        public string $sort = 'popular',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $ids = fn (string $key): array => array_values(array_unique(array_filter(array_map('intval', (array) $request->input($key, [])), fn (int $id): bool => $id >= 0)));
        $price = fn (string $key): ?float => is_numeric($request->input($key)) && (float) $request->input($key) >= 0 ? (float) $request->input($key) : null;
        $rating = $request->integer('rating');
        $sort = (string) $request->input('sort', 'popular');

        return new self(
            brands: $ids('brand'),
            stores: $ids('store'),
            minPrice: $price('min_price'),
            maxPrice: $price('max_price'),
            rating: $rating >= 1 && $rating <= 5 ? $rating : null,
            discounted: $request->boolean('discount'),
            inStock: $request->boolean('in_stock'),
            official: $request->boolean('official'),
            sort: array_key_exists($sort, self::SORTS) ? $sort : 'popular',
        );
    }

    public function activeCount(): int
    {
        return count($this->brands) + count($this->stores)
            + ($this->minPrice !== null || $this->maxPrice !== null ? 1 : 0)
            + ($this->rating !== null ? 1 : 0)
            + (int) $this->discounted + (int) $this->inStock + (int) $this->official;
    }
}
