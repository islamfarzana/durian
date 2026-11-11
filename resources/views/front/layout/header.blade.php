<header class="site-header" data-site-header>
    @if ($storeTheme->announcement() !== '')
        <div class="announcement">{{ $storeTheme->announcement() }}</div>
    @endif
    <div class="site-header__bar">
        <button type="button" class="icon-btn sidebar-toggle" data-sidebar-toggle aria-label="Browse categories" aria-controls="sidebar">
            <x-shop.icon name="menu" />
        </button>
        <x-shop.logo :name="$marketplace['name']" />

        <button type="button" class="location-pill location-pill--desktop" data-dialog-open="#location-dialog">
            <x-shop.icon name="pin" />
            <span><small>Deliver to</small>{{ $deliveryCity }}</span>
            <x-shop.icon name="chevron-down" class="icon--sm" />
        </button>

        @unless (request()->routeIs('home'))
            @include('front.layout.search_form', ['formClass' => 'header-search header-search--desktop', 'boxClass' => ''])
        @else
            <div class="header-search header-search--desktop" aria-hidden="true"></div>
        @endunless

        <div class="header-actions">
            <a class="icon-btn hide-mobile" href="{{ route('offers') }}" aria-label="Offers and vouchers"><x-shop.icon name="tag" /></a>
            <a class="icon-btn hide-mobile" href="{{ route('wishlist') }}" aria-label="Favourites">
                <x-shop.icon name="heart" />
                <span class="count" data-wishlist-count data-count="{{ $wishlistCount }}">{{ $wishlistCount }}</span>
            </a>
            <button type="button" class="icon-btn" data-cart-toggle aria-label="Open your bag" aria-controls="cart-panel">
                <x-shop.icon name="bag" />
                <span class="count" data-bag-count data-count="{{ $bag->itemCount() }}">{{ $bag->itemCount() }}</span>
            </button>
            @if ($customer)
                <a class="btn btn--ghost btn--sm btn-login hide-mobile" href="{{ route('account') }}">
                    <x-shop.icon name="user" class="icon--sm" />{{ \Illuminate\Support\Str::of($customer->name)->before(' ')->limit(14) }}
                </a>
            @else
                <a class="btn btn--primary btn--sm btn-login" href="{{ route('login') }}">Login</a>
            @endif
        </div>
    </div>
    @unless (request()->routeIs('home'))
        <div class="header-mobile-search">
            @include('front.layout.search_form', ['formClass' => 'header-search', 'boxClass' => ''])
        </div>
    @endunless
</header>
