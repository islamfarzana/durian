@extends('front.layout.layout')

@section('content')
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>Hi, {{ \Illuminate\Support\Str::of(Auth::user()->name)->before(' ') }}</h1>
            <div class="kpis">
                <a class="kpi" href="{{ route('orders') }}"><strong>{{ $counts['orders'] }}</strong><span>Orders</span></a>
                <a class="kpi" href="{{ route('orders', ['tab' => 'processing']) }}"><strong>{{ $counts['processing'] }}</strong><span>To be shipped</span></a>
                <a class="kpi" href="{{ route('wishlist') }}"><strong>{{ $counts['wishlist'] }}</strong><span>Favourites</span></a>
                <a class="kpi" href="{{ route('account.stores') }}"><strong>{{ $counts['stores'] }}</strong><span>Followed stores</span></a>
            </div>

            <section class="section">
                <div class="section-head">
                    <h2>Recent orders</h2>
                    <a class="link" href="{{ route('orders') }}">All orders</a>
                </div>
                @forelse ($recentOrders as $order)
                    @include('front.orders.order_card', ['order' => $order])
                @empty
                    <div class="empty-state" style="padding: 24px">
                        <x-shop.icon name="package" />
                        <p>No orders yet. Your first order is a few taps away.</p>
                        <a class="btn btn--primary" href="{{ route('home') }}">Start shopping</a>
                    </div>
                @endforelse
            </section>

            @if ($followedStores->isNotEmpty())
                <section class="section">
                    <div class="section-head"><h2>Stores you follow</h2><a class="link" href="{{ route('account.stores') }}">See all</a></div>
                    <div class="store-grid">
                        @foreach ($followedStores as $vendor)
                            <x-shop.store-card :vendor="$vendor" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
