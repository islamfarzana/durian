@extends('front.layout.layout')

@section('content')
    <div class="thanks">
        <div class="thanks__icon"><x-shop.icon name="check" /></div>
        <h1>Payment received</h1>
        <p class="muted">Thanks! Order #{{ Session::get('order_id') }} is paid and on its way to the sellers for packing.</p>
        <div class="cluster" style="justify-content: center">
            <a class="btn btn--primary" href="{{ route('orders', Session::get('order_id')) }}">Track my order</a>
            <a class="btn btn--ghost" href="{{ route('home') }}">Continue shopping</a>
        </div>
    </div>
@endsection
