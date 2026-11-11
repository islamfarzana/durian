@extends('front.layout.layout')

@section('no_cart_panel', '1')

@section('content')
    <div class="thanks">
        <div class="thanks__icon" style="background: var(--dz-primary-soft); color: var(--dz-primary)"><x-shop.icon name="cash" /></div>
        <h1>Complete your payment</h1>
        <p class="muted">Order #{{ Session::get('order_id') }} is reserved for you. Pay {{ formatMoney(Session::get('grand_total')) }} with PayPal to confirm it.</p>
        <form action="{{ route('payment') }}" method="post">
            @csrf
            <button type="submit" class="btn btn--primary btn--lg">Pay with PayPal</button>
        </form>
        <p class="small muted" style="margin-top: 14px">Changed your mind? <a class="link" href="{{ route('paypal.error') }}">Cancel the payment</a> and the items go back to the store.</p>
    </div>
@endsection
