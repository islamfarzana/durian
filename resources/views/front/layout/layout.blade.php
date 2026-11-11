<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="store-theme" content="{{ $storeTheme->name }}">
    @if (!empty($meta_description))
        <meta name="description" content="{{ $meta_description }}">
    @endif
    @if (!empty($meta_keywords))
        <meta name="keywords" content="{{ $meta_keywords }}">
    @endif
    <title>{{ !empty($meta_title) ? $meta_title.' | '.$marketplace['name'] : $marketplace['name'].' — '.$marketplace['tagline'] }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('front/images/icons/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $storeTheme->googleFontHref() }}">
    <link rel="stylesheet" href="{{ asset('front/css/durian.css') }}?v={{ @filemtime(public_path('front/css/durian.css')) }}">
    @if ($storeTheme->stylesheetUrl())
        <link rel="stylesheet" href="{{ $storeTheme->stylesheetUrl() }}">
    @endif
    <style id="store-theme-vars">
        :root { {!! $storeTheme->cssVariables() !!} }
    </style>
    @if ($storeTheme->safeCustomCss() !== '')
        <style id="store-theme-custom">{!! $storeTheme->safeCustomCss() !!}</style>
    @endif
    @stack('head')
</head>

<body class="dz dz-preload store-theme store-theme-{{ $storeTheme->niche }} header-style-{{ $storeTheme->setting('header_style') }} card-style-{{ $storeTheme->setting('product_card') }}{{ $bag->itemCount() > 0 ? ' has-cartbar' : '' }} @yield('body_class')"
    data-bag-state="{{ $bag->itemCount() }}" @hasSection('no_cart_panel') data-no-cart-panel @endif>
    <a class="sr-only" href="#main-content">Skip to content</a>

    @include('front.layout.header')

    <div class="shell">
        @include('front.layout.sidebar')

        <main class="main" id="main-content">
            <div class="main__content">
                @include('front.layout.flash')
                @yield('content')
            </div>
            @include('front.layout.footer')
        </main>
    </div>

    @include('front.layout.cart_panel')
    @include('front.layout.mobile_nav')
    @include('front.layout.modals')
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <script>
        window.DurianRoutes = {{ Js::from([
            'cartItems' => route('cart.items.store'),
            'voucher' => route('cart.voucher.store'),
            'suggest' => route('search.suggest'),
            'checkout' => route('checkout'),
            'login' => route('login'),
            'addressGet' => url('get-delivery-address'),
            'addressRemove' => route('addresses.remove'),
        ]) }};
    </script>
    <script src="{{ asset('front/js/durian.js') }}?v={{ @filemtime(public_path('front/js/durian.js')) }}" defer></script>
    @stack('scripts')
</body>

</html>
