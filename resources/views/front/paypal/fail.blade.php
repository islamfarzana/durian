@extends('front.layout.layout')

@section('content')
    <div class="thanks">
        <div class="thanks__icon" style="background: var(--dz-danger-soft); color: var(--dz-danger)"><x-shop.icon name="x" /></div>
        <h1>Payment not completed</h1>
        <p class="muted">Your PayPal payment was cancelled or failed, so the order was cancelled and the items went back to the store. Nothing was charged.</p>
        <div class="cluster" style="justify-content: center">
            <a class="btn btn--primary" href="{{ route('cart') }}">Back to your bag</a>
            <a class="btn btn--ghost" href="{{ route('home') }}">Continue shopping</a>
        </div>
    </div>
@endsection
