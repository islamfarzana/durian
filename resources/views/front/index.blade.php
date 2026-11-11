@extends('front.layout.layout')

@section('content')
    @php
        $heroTitles = [
            'grocery' => 'Fresh groceries, delivered <em>in hours</em>',
            'fashion' => 'The new season, <em>from every store</em>',
            'electronics' => 'Gadgets from <em>verified sellers</em>',
        ];
        $heroTitle = $heroTitles[$storeTheme->niche] ?? 'Groceries &amp; everything else, <em>delivered to your door</em>';
        $quickTags = $popularCategories->take(5);
    @endphp

    <section class="hero" aria-label="{{ $storeTheme->name }}">
        <div>
            <h1 class="hero__title">{!! $heroTitle !!}</h1>
            <p class="hero__lead">Shop {{ number_format($stats['products']) }}+ products from {{ $stats['stores'] > 0 ? number_format($stats['stores']).' trusted '.\Illuminate\Support\Str::plural('store', $stats['stores']) : 'trusted sellers' }} — fresh food, fashion, gadgets and home essentials in one bag. Pick a delivery slot and pay when it arrives.</p>
            <div class="hero__search">
                @include('front.layout.search_form', ['formClass' => 'header-search', 'boxClass' => 'search-box--hero'])
            </div>
            @if ($quickTags->isNotEmpty())
                <div class="hero__tags">
                    <span class="muted">Popular:</span>
                    @foreach ($quickTags as $tag)
                        <a href="{{ url($tag->url) }}">{{ $tag->category_name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="hero__media">
            @if ($sliderBanners->isNotEmpty())
                <div class="slider" data-slider>
                    <div class="slider__track">
                        @foreach ($sliderBanners as $banner)
                            <a class="slider__slide" href="{{ $banner->link ? url($banner->link) : route('offers') }}">
                                <img src="{{ asset('front/images/banner_images/'.$banner->image) }}" alt="{{ $banner->alt ?: $banner->title }}" @if (! $loop->first) loading="lazy" @endif>
                                @if ($banner->title)
                                    <span class="slider__caption">{{ $banner->title }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    @if ($sliderBanners->count() > 1)
                        <div class="slider__dots">
                            @foreach ($sliderBanners as $banner)
                                <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" aria-label="Show banner {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="collage">
                    @foreach ($popularCategories->take(6) as $category)
                        <a href="{{ url($category->url) }}">
                            @if ($category->imageUrl())
                                <img src="{{ $category->imageUrl() }}" alt="">
                            @endif
                            {{ $category->category_name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div class="usp-row">
        <div class="usp"><span class="usp__icon"><x-shop.icon name="package" /></span><div><strong>{{ number_format($stats['products']) }}+ products</strong><span>Groceries to gadgets</span></div></div>
        <div class="usp"><span class="usp__icon"><x-shop.icon name="cash" /></span><div><strong>Pay on delivery</strong><span>Cash when it arrives</span></div></div>
        <div class="usp"><span class="usp__icon"><x-shop.icon name="clock" /></span><div><strong>Pick a time slot</strong><span>Delivered when you're home</span></div></div>
        <div class="usp"><span class="usp__icon"><x-shop.icon name="shield" /></span><div><strong>Verified sellers</strong><span>Easy returns on eligible items</span></div></div>
    </div>

    @if ($flashSale && $flashProducts->isNotEmpty())
        <section class="section flash-band" aria-labelledby="flash-title">
            <div class="section-head">
                <div>
                    <h2 id="flash-title"><x-shop.icon name="bolt" class="icon--lg icon--fill" />{{ $flashSale->title }}</h2>
                    <span class="countdown" data-countdown="{{ $flashSale->ends_at->toIso8601String() }}">
                        <span data-countdown-label>Ends in</span>
                        <span class="countdown__digits"><span data-unit="h">00</span>:<span data-unit="m">00</span>:<span data-unit="s">00</span></span>
                    </span>
                </div>
                <div class="section-head__actions">
                    <a class="btn btn--accent btn--sm" href="{{ route('flash-sale') }}">Shop all deals</a>
                </div>
            </div>
            <div class="rail" id="rail-flash">
                @foreach ($flashProducts as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($popularCategories->isNotEmpty())
        <section class="section" aria-labelledby="categories-title">
            <div class="section-head">
                <div>
                    <h2 id="categories-title">Shop by category</h2>
                    <p>Everything from the daily bazaar to your next gadget.</p>
                </div>
            </div>
            <div class="category-tiles">
                @foreach ($popularCategories as $category)
                    <a class="category-tile" href="{{ url($category->url) }}">
                        @if ($category->imageUrl())
                            <img src="{{ $category->imageUrl() }}" alt="" loading="lazy">
                        @else
                            <span class="category-tile__initial">{{ mb_substr($category->category_name, 0, 1) }}</span>
                        @endif
                        {{ $category->category_name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ([
        ['id' => 'bestsellers', 'title' => 'Best sellers', 'lead' => 'What everyone is putting in their bag', 'products' => $bestSellers, 'link' => route('search', ['collection' => 'bestsellers'])],
        ['id' => 'deals', 'title' => 'Deal of the day', 'lead' => 'Biggest discounts right now', 'products' => $deals, 'link' => route('search', ['collection' => 'deals'])],
    ] as $rail)
        @if ($rail['products']->isNotEmpty())
            @include('front.products.rail', $rail)
        @endif
    @endforeach

    @if ($stores->isNotEmpty())
        <section class="section" aria-labelledby="stores-title">
            <div class="section-head">
                <div>
                    <h2 id="stores-title"><x-shop.icon name="store" />Shop by store</h2>
                    <p>Official brand stores and verified sellers, all in one bag.</p>
                </div>
                <div class="section-head__actions"><a class="link" href="{{ route('stores.index') }}">All stores</a></div>
            </div>
            <div class="rail" id="rail-stores" style="grid-auto-columns: minmax(170px, 190px)">
                @foreach ($stores as $vendor)
                    <x-shop.store-card :vendor="$vendor" />
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="promo-grid">
            <div class="promo-card promo-card--accent">
                <div>
                    <h3>Vouchers that save money</h3>
                    <p>Collect store and marketplace vouchers, then apply them in your bag.</p>
                </div>
                <a class="btn btn--accent btn--sm" href="{{ route('offers') }}" style="align-self: flex-start">See offers</a>
            </div>
            <div class="promo-card">
                <div>
                    <h3>Follow your favourite stores</h3>
                    <p>Get their new arrivals and deals first, and keep a list of favourites.</p>
                </div>
                <a class="btn btn--outline btn--sm" href="{{ route('stores.index') }}" style="align-self: flex-start">Browse stores</a>
            </div>
            <div class="promo-card promo-card--primary">
                <div>
                    <h3>Sell on {{ $marketplace['name'] }}</h3>
                    <p>Open your store, list any product, and reach customers across the country.</p>
                </div>
                <a class="btn btn--sm" style="align-self: flex-start; background: #fff; color: var(--dz-primary-strong)" href="{{ route('seller.register') }}">Start selling</a>
            </div>
        </div>
    </section>

    @if ($newArrivals->isNotEmpty())
        @include('front.products.rail', ['id' => 'new', 'title' => 'New arrivals', 'lead' => 'Just added by our sellers', 'products' => $newArrivals, 'link' => route('search', ['collection' => 'new'])])
    @endif

    @if ($brands->isNotEmpty())
        <section class="section" aria-labelledby="brands-title">
            <div class="section-head"><h2 id="brands-title">Popular brands</h2></div>
            <div class="brand-strip">
                @foreach ($brands as $brand)
                    <a href="{{ route('search', ['q' => $brand->name]) }}">{{ $brand->name }}</a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($justForYou->isNotEmpty())
        <section class="section" aria-labelledby="jfy-title">
            <div class="section-head">
                <div>
                    <h2 id="jfy-title">Just for you</h2>
                    <p>Fresh picks from every corner of the market.</p>
                </div>
            </div>
            <div class="product-grid" id="jfy-grid">
                @include('front.products.grid_items', ['products' => $justForYou])
            </div>
            @if ($justForYou->hasMorePages())
                <div class="load-more">
                    <button type="button" class="btn btn--outline" data-load-more="{{ $justForYou->nextPageUrl() }}" data-target="#jfy-grid" data-label="Load more">Load more</button>
                </div>
            @endif
        </section>
    @endif

    <section class="section">
        <div class="stats">
            <div class="stat"><strong>{{ number_format($stats['products']) }}</strong><span>products to shop from</span></div>
            <div class="stat"><strong>{{ number_format($stats['stores']) }}</strong><span>stores selling here</span></div>
            <div class="stat"><strong>{{ number_format($stats['cities']) }}</strong><span>{{ \Illuminate\Support\Str::plural('city', $stats['cities']) }} with delivery</span></div>
            <div class="stat"><strong>{{ number_format($stats['orders']) }}</strong><span>orders delivered</span></div>
        </div>
    </section>

    @if ($deliveryCities->isNotEmpty())
        <section class="section" style="text-align: center">
            <h2>Currently delivering in</h2>
            <div class="city-pills">
                @foreach ($deliveryCities as $city)
                    <span>{{ $city }}</span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section faq" id="faq" aria-labelledby="faq-title">
        <h2 id="faq-title" style="text-align: center">Common questions</h2>
        <details open>
            <summary>How much does delivery cost?</summary>
            <div>
                Delivery is {{ formatMoney($deliveryFee) }} in most areas and depends on your city and the weight of your order.
                @if ($marketplace['free_delivery_threshold'] > 0)
                    Orders above {{ formatMoney($marketplace['free_delivery_threshold']) }} are delivered free.
                @endif
            </div>
        </details>
        <details>
            <summary>When will my order arrive?</summary>
            <div>Choose a delivery slot at checkout — today when there is still time, or on the next days. Items from different stores travel together in one delivery.</div>
        </details>
        <details>
            <summary>Can I pay when my order arrives?</summary>
            <div>Yes. Cash on delivery is available on every order. You can also pay online with a card or PayPal where it is offered.</div>
        </details>
        <details>
            <summary>What is your return policy?</summary>
            <div>Each product page shows its return window and warranty. You can cancel a cash-on-delivery order from your account until a seller ships it.</div>
        </details>
        <details>
            <summary>Who are the sellers?</summary>
            <div>Every store is reviewed before it can sell. Official stores are run by the brands themselves. Ratings and questions on each product come from real customers.</div>
        </details>
    </section>
@endsection
