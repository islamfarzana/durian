<form class="{{ $formClass }}" action="{{ route('search') }}" method="get" role="search" data-search>
    <div class="search-box {{ $boxClass }}">
        <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search for products, brands and stores" autocomplete="off" aria-label="Search products" aria-expanded="false" maxlength="100">
        <button type="submit" aria-label="Search"><x-shop.icon name="search" /></button>
    </div>
    <div class="search-suggest" data-search-suggest hidden></div>
</form>
