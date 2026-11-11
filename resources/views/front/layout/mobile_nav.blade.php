<nav class="bottom-nav" aria-label="Quick navigation">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}"><x-shop.icon name="home" />Home</a>
    <button type="button" data-sidebar-toggle><x-shop.icon name="grid" />Categories</button>
    <a href="{{ route('offers') }}" class="{{ request()->routeIs('offers', 'flash-sale') ? 'is-active' : '' }}"><x-shop.icon name="tag" />Offers</a>
    <button type="button" data-cart-toggle><x-shop.icon name="bag" /><span class="count" data-bag-count data-count="{{ $bag->itemCount() }}">{{ $bag->itemCount() }}</span>Bag</button>
    <a href="{{ $customer ? route('account') : route('login') }}" class="{{ request()->routeIs('account*', 'orders*', 'login') ? 'is-active' : '' }}"><x-shop.icon name="user" />Account</a>
</nav>

<div class="mobile-cartbar">
    <a class="checkout-btn" href="{{ route('checkout') }}">
        <span>Place order · <span data-bag-count>{{ $bag->itemCount() }}</span> <span data-bag-noun>{{ \Illuminate\Support\Str::plural('item', $bag->itemCount()) }}</span></span>
        <span data-bag-total>{{ formatMoney($bag->total()) }}</span>
    </a>
</div>
