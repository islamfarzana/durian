<nav class="account-nav" aria-label="My account">
    <div class="account-nav__user">
        <strong>{{ Auth::user()->name }}</strong>
        <span>{{ Auth::user()->email }}</span>
    </div>
    <a href="{{ route('account') }}" class="{{ request()->routeIs('account') ? 'is-active' : '' }}"><x-shop.icon name="home" />Overview</a>
    <a href="{{ route('orders') }}" class="{{ request()->routeIs('orders') ? 'is-active' : '' }}"><x-shop.icon name="package" />My orders</a>
    <a href="{{ route('wishlist') }}" class="{{ request()->routeIs('wishlist') ? 'is-active' : '' }}"><x-shop.icon name="heart" />Favourites</a>
    <a href="{{ route('account.stores') }}" class="{{ request()->routeIs('account.stores') ? 'is-active' : '' }}"><x-shop.icon name="store" />Followed stores</a>
    <a href="{{ route('account.addresses') }}" class="{{ request()->routeIs('account.addresses') ? 'is-active' : '' }}"><x-shop.icon name="pin" />Address book</a>
    <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'is-active' : '' }}"><x-shop.icon name="user" />Profile &amp; password</a>
    <a href="{{ route('logout') }}"><x-shop.icon name="logout" />Log out</a>
</nav>
