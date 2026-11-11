@extends('front.layout.layout')

@section('content')
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>Followed stores</h1>
            @if ($stores->isEmpty())
                <div class="empty-state">
                    <x-shop.icon name="store" />
                    <h2>You're not following any stores</h2>
                    <p>Follow stores to find their new arrivals and vouchers quickly.</p>
                    <a class="btn btn--primary" href="{{ route('stores.index') }}">Browse stores</a>
                </div>
            @else
                <div class="store-grid">
                    @foreach ($stores as $vendor)
                        <x-shop.store-card :vendor="$vendor" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
