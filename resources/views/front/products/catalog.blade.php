@php
    $keep = array_filter($keep ?? [], fn ($value) => filled($value));
    $query = request()->except(['page', 'fragment']);
    $without = function (string $key, $value = null) use ($query, $formAction): string {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = array_values(array_diff((array) ($query[$key] ?? []), [(string) $value]));
            if ($query[$key] === []) {
                unset($query[$key]);
            }
        }

        return $formAction.($query === [] ? '' : '?'.http_build_query($query));
    };
    $brandNames = $facets['brands']->pluck('name', 'id');
    $storeNames = $facets['stores']->pluck('name', 'id');
    $gridId = $gridId ?? 'catalog-grid';
@endphp

<form method="get" action="{{ $formAction }}" class="catalog-form">
    @foreach ($keep as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach

    <div class="toolbar">
        <button type="button" class="chip" data-filters-open><x-shop.icon name="filter" class="icon--sm" />Filters{{ $filters->activeCount() > 0 ? ' ('.$filters->activeCount().')' : '' }}</button>
        <label class="chip"><input type="checkbox" name="discount" value="1" data-autosubmit @checked($filters->discounted)><x-shop.icon name="percent" class="icon--sm" />On sale</label>
        <label class="chip"><input type="checkbox" name="in_stock" value="1" data-autosubmit @checked($filters->inStock)>In stock</label>
        <label class="chip"><input type="checkbox" name="official" value="1" data-autosubmit @checked($filters->official)><x-shop.icon name="verified" class="icon--sm" />Official stores</label>
        <div class="toolbar__sort">
            <label for="{{ $gridId }}-sort">Sort by</label>
            <select class="select" id="{{ $gridId }}-sort" name="sort" data-autosubmit>
                @foreach (\App\Services\Catalog\CatalogFilters::SORTS as $value => $label)
                    <option value="{{ $value }}" @selected($filters->sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <aside class="filter-drawer" aria-label="Filters">
        <div class="filter-drawer__head">
            <h2>Filters</h2>
            <button type="button" class="icon-btn" data-filters-close aria-label="Close filters"><x-shop.icon name="x" /></button>
        </div>
        <div class="filter-drawer__body">
            <div class="filter-group">
                <h3>Price ({{ $marketplace['currency'] }})</h3>
                <div class="price-range">
                    <input class="input" type="number" name="min_price" min="0" step="1" inputmode="numeric" placeholder="{{ (int) $facets['price']['min'] }}" value="{{ $filters->minPrice !== null ? (int) $filters->minPrice : '' }}" aria-label="Minimum price">
                    <span class="muted">to</span>
                    <input class="input" type="number" name="max_price" min="0" step="1" inputmode="numeric" placeholder="{{ (int) $facets['price']['max'] }}" value="{{ $filters->maxPrice !== null ? (int) $filters->maxPrice : '' }}" aria-label="Maximum price">
                </div>
            </div>
            <div class="filter-group">
                <h3>Rating</h3>
                <div class="filter-options">
                    @foreach ([4, 3, 2] as $stars)
                        <label class="check"><input type="radio" name="rating" value="{{ $stars }}" @checked($filters->rating === $stars)><x-shop.stars :rating="$stars" /> &amp; up</label>
                    @endforeach
                    <label class="check"><input type="radio" name="rating" value="" @checked($filters->rating === null)>Any rating</label>
                </div>
            </div>
            @if ($facets['brands']->isNotEmpty())
                <div class="filter-group">
                    <h3>Brand</h3>
                    <div class="filter-options">
                        @foreach ($facets['brands'] as $brand)
                            <label class="check"><input type="checkbox" name="brand[]" value="{{ $brand['id'] }}" @checked(in_array($brand['id'], $filters->brands, true))>{{ $brand['name'] }}<small>{{ $brand['count'] }}</small></label>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($facets['stores']->count() > 1 && empty($hideStoreFilter))
                <div class="filter-group">
                    <h3>Store</h3>
                    <div class="filter-options">
                        @foreach ($facets['stores'] as $store)
                            <label class="check"><input type="checkbox" name="store[]" value="{{ $store['id'] }}" @checked(in_array($store['id'], $filters->stores, true))>{{ $store['name'] }}<small>{{ $store['count'] }}</small></label>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="filter-drawer__foot">
            <a class="btn btn--ghost" href="{{ $formAction.($keep === [] ? '' : '?'.http_build_query($keep)) }}">Clear all</a>
            <button type="submit" class="btn btn--primary" style="flex: 1">Show results</button>
        </div>
    </aside>
</form>

@if ($filters->activeCount() > 0)
    <div class="active-filters" aria-label="Active filters">
        @foreach ($filters->brands as $brandId)
            <a class="chip is-active" href="{{ $without('brand', $brandId) }}">{{ $brandNames[$brandId] ?? 'Brand' }}<x-shop.icon name="x" class="icon--sm chip__x" /></a>
        @endforeach
        @foreach ($filters->stores as $storeId)
            <a class="chip is-active" href="{{ $without('store', $storeId) }}">{{ $storeNames[$storeId] ?? 'Store' }}<x-shop.icon name="x" class="icon--sm chip__x" /></a>
        @endforeach
        @if ($filters->minPrice !== null || $filters->maxPrice !== null)
            @php $priceQuery = $query; unset($priceQuery['min_price'], $priceQuery['max_price']); @endphp
            <a class="chip is-active" href="{{ $formAction.($priceQuery === [] ? '' : '?'.http_build_query($priceQuery)) }}">{{ $filters->minPrice !== null ? formatMoney($filters->minPrice) : 'Any' }} – {{ $filters->maxPrice !== null ? formatMoney($filters->maxPrice) : 'any' }}<x-shop.icon name="x" class="icon--sm chip__x" /></a>
        @endif
        @if ($filters->rating !== null)
            <a class="chip is-active" href="{{ $without('rating') }}">{{ $filters->rating }}★ &amp; up<x-shop.icon name="x" class="icon--sm chip__x" /></a>
        @endif
        <a class="link small" href="{{ $formAction.($keep === [] ? '' : '?'.http_build_query($keep)) }}">Clear all</a>
    </div>
@endif

@if ($products->isEmpty())
    <div class="empty-state">
        <x-shop.icon name="search" />
        <h2>No products match</h2>
        <p>Try removing a filter, or search for something else.</p>
        @if ($filters->activeCount() > 0)
            <a class="btn btn--outline" href="{{ $formAction.($keep === [] ? '' : '?'.http_build_query($keep)) }}">Clear filters</a>
        @endif
    </div>
@else
    <div class="product-grid" id="{{ $gridId }}">
        @include('front.products.grid_items', ['products' => $products])
    </div>
    @if ($products->hasMorePages())
        <div class="load-more">
            <button type="button" class="btn btn--outline" data-load-more="{{ $products->nextPageUrl() }}" data-target="#{{ $gridId }}" data-label="Load more products">Load more products</button>
        </div>
    @endif
    <div class="pagination-wrap" data-pagination>{{ $products->links() }}</div>
@endif
