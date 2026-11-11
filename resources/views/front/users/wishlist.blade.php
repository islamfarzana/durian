@extends('front.layout.layout')

@section('content')
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>Favourites</h1>
            @if ($products->isEmpty())
                <div class="empty-state">
                    <x-shop.icon name="heart" />
                    <h2>Nothing saved yet</h2>
                    <p>Tap the heart on any product to keep it here for later.</p>
                    <a class="btn btn--primary" href="{{ route('home') }}">Discover products</a>
                </div>
            @else
                <div class="product-grid">
                    @include('front.products.grid_items', ['products' => $products])
                </div>
            @endif
        </div>
    </div>
@endsection
