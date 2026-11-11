@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Offers & vouchers', 'url' => null]]])

    <div class="listing-head">
        <div>
            <h1>Offers &amp; vouchers</h1>
            <p>Copy a code, then apply it in your bag. Store vouchers work on that store's items; marketplace vouchers work on everything.</p>
        </div>
        <a class="btn btn--accent" href="{{ route('flash-sale') }}"><x-shop.icon name="bolt" class="icon--fill" />Flash Sale</a>
    </div>

    <section class="section" aria-labelledby="vouchers-title">
        <div class="section-head"><h2 id="vouchers-title"><x-shop.icon name="tag" />Vouchers</h2></div>
        @if ($vouchers->isEmpty())
            <p class="muted">No public vouchers right now — check back soon, and follow stores to catch their codes first.</p>
        @else
            <div class="voucher-grid">
                @foreach ($vouchers as $coupon)
                    <x-shop.voucher :coupon="$coupon" />
                @endforeach
            </div>
        @endif
    </section>

    <section class="section" aria-labelledby="deals-title">
        <div class="section-head"><h2 id="deals-title"><x-shop.icon name="percent" />Everything on sale</h2></div>
        @include('front.products.catalog', ['products' => $deals, 'formAction' => url()->current(), 'keep' => [], 'gridId' => 'deals-grid'])
    </section>
@endsection
