@extends('front.layout.layout')

@section('content')
    <div class="thanks">
        <div class="thanks__icon"><x-shop.icon name="check" /></div>
        <h1>Thank you, your order is placed!</h1>
        <p class="muted">We've emailed a confirmation to {{ $order->email }}. You can follow every package from your account.</p>

        <div class="thanks__facts">
            <div><span class="muted">Order number</span><strong>#{{ $order->id }}</strong></div>
            <div><span class="muted">Total</span><strong>{{ formatMoney($order->grand_total) }}</strong></div>
            <div><span class="muted">Payment</span><strong>{{ $order->payment_gateway === 'COD' ? 'Cash on delivery' : $order->payment_gateway }}</strong></div>
            @if ($order->delivery_date)
                <div><span class="muted">Delivery</span><strong>{{ $order->delivery_date->format('D, j M') }} · {{ $order->delivery_slot }}</strong></div>
            @endif
            <div><span class="muted">Deliver to</span><strong>{{ $order->name }}, {{ $order->city }}</strong></div>
            <div><span class="muted">Items</span><strong>{{ $order->orders_products->sum('product_qty') }}</strong></div>
        </div>

        <div class="cluster" style="justify-content: center">
            <a class="btn btn--primary" href="{{ route('orders', $order->id) }}">Track my order</a>
            <a class="btn btn--ghost" href="{{ route('home') }}">Continue shopping</a>
        </div>
    </div>
@endsection
