@extends('front.layout.layout')

@section('content')
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>Address book</h1>
            <div class="option-grid" data-address-list style="margin-bottom: 18px">
                @include('front.products.delivery_addresses', ['deliveryAddresses' => $deliveryAddresses])
            </div>
            <details class="card" @if (count($deliveryAddresses) === 0) open @endif>
                <summary class="card__title" style="cursor: pointer; margin: 0">+ Add or edit an address</summary>
                <div style="margin-top: 16px">
                    @include('front.users.address_form', ['countries' => $countries])
                </div>
            </details>
        </div>
    </div>
@endsection
