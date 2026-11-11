@extends('front.layout.layout')

@section('content')
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>My orders</h1>
            <nav class="tabs" style="margin-top: 0; position: static" aria-label="Order status">
                @foreach ($tabs as $key => $definition)
                    <a href="{{ route('orders', $key === 'all' ? [] : ['tab' => $key]) }}" class="{{ $tab === $key ? 'is-active' : '' }}">{{ $definition['label'] }}</a>
                @endforeach
            </nav>

            @forelse ($orders as $order)
                @include('front.orders.order_card', ['order' => $order])
            @empty
                <div class="empty-state">
                    <x-shop.icon name="package" />
                    <h2>No orders here</h2>
                    <p>{{ $tab === 'all' ? 'When you place an order it will show up here.' : 'Nothing in this tab right now.' }}</p>
                    <a class="btn btn--primary" href="{{ route('home') }}">Start shopping</a>
                </div>
            @endforelse

            <div class="pagination-wrap">{{ $orders->links() }}</div>
        </div>
    </div>
@endsection
