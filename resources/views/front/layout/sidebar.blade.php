@php
    $currentPath = trim(request()->path(), '/');
    $isCurrent = fn (array $category): bool => trim($category['url'], '/') === $currentPath;
    $containsCurrent = fn (array $category): bool => $isCurrent($category) || collect($category['sub_categories'] ?? [])->contains(fn ($sub) => $isCurrent($sub));
@endphp
<aside class="sidebar" id="sidebar" aria-label="Shop by category">
    @if ($marketplace['free_delivery_threshold'] > 0)
        <div class="sidebar__promo">
            <x-shop.icon name="truck" class="icon--lg" />
            <div><strong>Free delivery</strong>on orders over {{ formatMoney($marketplace['free_delivery_threshold']) }}</div>
        </div>
    @endif

    <ul class="sidebar__links">
        <li><a href="{{ route('offers') }}" class="{{ request()->routeIs('offers') ? 'is-active' : '' }}"><x-shop.icon name="tag" />Offers &amp; vouchers</a></li>
        <li><a href="{{ route('flash-sale') }}" class="link-flash {{ request()->routeIs('flash-sale') ? 'is-active' : '' }}"><x-shop.icon name="bolt" />Flash Sale</a></li>
        <li><a href="{{ route('search', ['collection' => 'bestsellers']) }}" class="{{ request('collection') === 'bestsellers' ? 'is-active' : '' }}"><x-shop.icon name="sparkles" />Popular</a></li>
        <li><a href="{{ route('search', ['collection' => 'new']) }}" class="{{ request('collection') === 'new' ? 'is-active' : '' }}"><x-shop.icon name="leaf" />New arrivals</a></li>
        <li><a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'is-active' : '' }}"><x-shop.icon name="store" />Stores</a></li>
        <li><a href="{{ route('wishlist') }}" class="{{ request()->routeIs('wishlist') ? 'is-active' : '' }}"><x-shop.icon name="heart" />Favourites</a></li>
    </ul>

    <div class="sidebar__heading">Categories</div>
    <ul class="tree">
        @foreach ($navSections as $section)
            @if (count($section['categories']) > 0)
                <li class="tree__item">
                    <details @if (collect($section['categories'])->contains(fn ($category) => $containsCurrent($category))) open @endif>
                        <summary class="tree__row">{{ $section['name'] }}<x-shop.icon name="chevron-right" /></summary>
                        <ul>
                            @foreach ($section['categories'] as $category)
                                <li class="tree__item">
                                    @if (! empty($category['sub_categories']))
                                        <details @if ($containsCurrent($category)) open @endif>
                                            <summary class="tree__row">{{ $category['category_name'] }}<x-shop.icon name="chevron-right" /></summary>
                                            <ul>
                                                <li><a class="tree__row tree__leaf {{ $isCurrent($category) ? 'is-active' : '' }}" href="{{ url($category['url']) }}">All {{ $category['category_name'] }}</a></li>
                                                @foreach ($category['sub_categories'] as $subcategory)
                                                    <li><a class="tree__row tree__leaf {{ $isCurrent($subcategory) ? 'is-active' : '' }}" href="{{ url($subcategory['url']) }}">{{ $subcategory['category_name'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @else
                                        <a class="tree__row tree__leaf {{ $isCurrent($category) ? 'is-active' : '' }}" href="{{ url($category['url']) }}">{{ $category['category_name'] }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                </li>
            @endif
        @endforeach
    </ul>

    <div class="sidebar__footer">
        <a href="{{ route('seller.register') }}"><x-shop.icon name="store" class="icon--sm" />Sell on {{ $marketplace['name'] }}</a>
        <a href="{{ url('admin/login') }}"><x-shop.icon name="chart" class="icon--sm" />Seller Center</a>
        <a href="{{ route('contact') }}"><x-shop.icon name="help" class="icon--sm" />Help &amp; contact</a>
    </div>
</aside>
