@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Stores', 'url' => route('stores.index')], ['label' => $vendor->store_name, 'url' => null]]])

    <section class="store-hero" aria-label="{{ $vendor->store_name }}">
        <div class="store-hero__banner">
            @if ($vendor->bannerUrl())
                <img src="{{ $vendor->bannerUrl() }}" alt="">
            @endif
        </div>
        <div class="store-hero__body">
            <span class="store-logo store-logo--lg">
                @if ($vendor->logoUrl())
                    <img src="{{ $vendor->logoUrl() }}" alt="">
                @else
                    {{ $vendor->initials() }}
                @endif
            </span>
            <div class="store-hero__info">
                <h1>
                    {{ $vendor->store_name }}
                    @if ($vendor->is_official)
                        <span class="badge badge--official"><x-shop.icon name="verified" class="icon--sm" />Official Store</span>
                    @else
                        <span class="badge badge--verified"><x-shop.icon name="shield" class="icon--sm" />Verified seller</span>
                    @endif
                </h1>
                @if (filled($vendor->tagline))
                    <p>{{ $vendor->tagline }}</p>
                @endif
                <div class="store-hero__stats">
                    <span><strong data-followers>{{ number_format($vendor->followers_count) }}</strong> followers</span>
                    <span><strong>{{ number_format($stats['products']) }}</strong> products</span>
                    @if ($stats['positive_percent'] !== null)
                        <span><strong>{{ $stats['positive_percent'] }}%</strong> positive ratings</span>
                    @endif
                    @if ($stats['average'] !== null)
                        <span><x-shop.stars :rating="$stats['average']" :count="$stats['ratings']" /></span>
                    @endif
                    @if ($vendor->businessDetails?->shop_city)
                        <span><x-shop.icon name="pin" class="icon--sm" /> {{ $vendor->businessDetails->shop_city }}</span>
                    @endif
                </div>
            </div>
            <div class="store-hero__actions">
                <button type="button" class="btn {{ $isFollowing ? 'btn--outline' : 'btn--primary' }}" data-follow="{{ route('stores.follow', $vendor->slug) }}" aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
                    <x-shop.icon name="plus" class="icon--sm" /><span data-follow-label>{{ $isFollowing ? 'Following' : 'Follow' }}</span>
                </button>
                <button type="button" class="btn btn--ghost" data-copy="{{ url()->current() }}" data-copy-message="Store link copied."><x-shop.icon name="share" class="icon--sm" />Share</button>
            </div>
        </div>
    </section>

    @if ($vendor->holiday_mode)
        <div class="alert alert--info holiday-banner"><x-shop.icon name="calendar" />{{ $vendor->store_name }} is on a break and not taking orders right now. Follow the store to hear when it's back.</div>
    @endif

    @if ($vouchers->isNotEmpty())
        <section class="section" aria-labelledby="store-vouchers">
            <div class="section-head"><h2 id="store-vouchers"><x-shop.icon name="tag" />Store vouchers</h2></div>
            <div class="voucher-grid">
                @foreach ($vouchers as $coupon)
                    <x-shop.voucher :coupon="$coupon" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($topProducts->count() > 3)
        @include('front.products.rail', ['id' => 'store-top', 'title' => 'Top sellers in this store', 'lead' => null, 'products' => $topProducts, 'link' => null])
    @endif

    <section class="section" aria-labelledby="store-all">
        <div class="section-head"><h2 id="store-all">All products</h2></div>
        @include('front.products.catalog', ['formAction' => url()->current(), 'keep' => [], 'hideStoreFilter' => true, 'gridId' => 'store-grid'])
    </section>

    @if (filled($vendor->about))
        <section class="section card" aria-labelledby="store-about">
            <h2 class="card__title" id="store-about">About {{ $vendor->store_name }}</h2>
            <div class="prose">{!! nl2br(e($vendor->about)) !!}</div>
            <p class="small muted">Selling on {{ $marketplace['name'] }} since {{ $vendor->created_at?->format('F Y') }}.</p>
        </section>
    @endif
@endsection
