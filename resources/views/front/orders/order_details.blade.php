@extends('front.layout.layout')

@section('content')
    @php
        $timezone = config('marketplace.timezone');
        $subtotal = $order->orders_products->sum(fn ($line) => $line->lineTotal());
    @endphp
    <div class="account">
        @include('front.users.account_nav')
        <div>
            @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'My orders', 'url' => route('orders')], ['label' => 'Order #'.$order->id, 'url' => null]]])
            <div class="listing-head">
                <div>
                    <h1>Order #{{ $order->id }}</h1>
                    <p>Placed {{ $order->created_at?->timezone($timezone)->format('j M Y, g:i A') }} · <strong>{{ $order->order_status }}</strong></p>
                </div>
                @if ($order->isCancellable())
                    <form method="post" action="{{ route('orders.cancel', $order->id) }}" data-confirm="Cancel this order? Items go back to the sellers.">
                        @csrf
                        <button type="submit" class="btn btn--danger btn--sm"><x-shop.icon name="x" class="icon--sm" />Cancel order</button>
                    </form>
                @endif
            </div>

            <div class="page-grid">
                <div>
                    @foreach ($packages as $vendorId => $lines)
                        @php $store = $stores[$vendorId] ?? null; @endphp
                        <section class="order-card">
                            <div class="order-card__head">
                                <span>
                                    Package {{ $loop->iteration }} ·
                                    @if ($store)
                                        <a class="link" href="{{ $store->slug ? $store->storeUrl() : '#' }}">{{ $store->store_name }}</a>
                                    @else
                                        {{ $marketplace['name'] }}
                                    @endif
                                </span>
                                <span class="muted">{{ $lines->sum('product_qty') }} {{ \Illuminate\Support\Str::plural('item', $lines->sum('product_qty')) }}</span>
                            </div>
                            @foreach ($lines as $line)
                                <div class="bag-line">
                                    <img src="{{ \App\Models\Product::imageUrlFor($line->product?->product_image, 'small') }}" alt="">
                                    <div>
                                        <a class="bag-line__name" href="{{ route('products.show', $line->product_id) }}">{{ $line->product_name }}</a>
                                        <div class="bag-line__meta">{{ $line->product_size }} · {{ formatMoney($line->product_price) }} × {{ $line->product_qty }}</div>
                                        <div class="small">
                                            <span class="status {{ $line->item_status === 'Delivered' ? 'status--delivered' : ($line->item_status === 'Cancelled' ? 'status--cancelled' : ($line->item_status === 'Shipped' ? 'status--shipped' : 'status--processing')) }}">{{ $line->item_status ?: 'Pending' }}</span>
                                            @if ($line->courier_name && $line->tracking_number)
                                                · {{ $line->courier_name }} #{{ $line->tracking_number }}
                                            @endif
                                        </div>
                                        @if ($order->order_status !== 'Cancelled' && $line->item_status !== 'Cancelled' && ! in_array((int) $line->product_id, $reviewedProductIds, true))
                                            <a class="link small" href="{{ route('products.show', $line->product_id) }}#reviews">Write a review</a>
                                        @endif
                                    </div>
                                    <span class="bag-line__price">{{ formatMoney($line->lineTotal()) }}</span>
                                </div>
                            @endforeach
                        </section>
                    @endforeach

                    <section class="card">
                        <h2 class="card__title">Order history</h2>
                        <ol class="timeline">
                            @forelse ($order->logs as $log)
                                <li>
                                    {{ $log->order_status }}
                                    @if ($log->order_item_id)
                                        <span class="muted">(one item)</span>
                                    @endif
                                    <small>{{ $log->created_at?->timezone($timezone)->format('j M Y, g:i A') }}</small>
                                </li>
                            @empty
                                <li>{{ $order->order_status }}<small>{{ $order->created_at?->timezone($timezone)->format('j M Y, g:i A') }}</small></li>
                            @endforelse
                        </ol>
                    </section>
                </div>

                <aside>
                    <div class="card">
                        <h2 class="card__title">Delivery</h2>
                        <p style="margin: 0 0 8px"><strong>{{ $order->name }}</strong><br>{{ $order->address }}, {{ $order->city }}, {{ $order->state }} {{ $order->pincode }}<br><span class="muted">{{ $order->mobile }}</span></p>
                        @if ($order->delivery_date)
                            <p class="small" style="margin: 0"><x-shop.icon name="clock" class="icon--sm" /> {{ $order->delivery_date->format('D, j M Y') }} · {{ $order->delivery_slot }}</p>
                        @endif
                        @if (filled($order->notes))
                            <p class="small muted" style="margin: 8px 0 0">“{{ $order->notes }}”</p>
                        @endif
                    </div>
                    <div class="card">
                        <h2 class="card__title">Payment</h2>
                        <dl class="totals">
                            <div><dt>Items</dt><dd>{{ formatMoney($subtotal) }}</dd></div>
                            @if ($order->coupon_amount > 0)
                                <div class="is-saving"><dt>Voucher {{ $order->coupon_code }}</dt><dd>-{{ formatMoney($order->coupon_amount) }}</dd></div>
                            @endif
                            <div><dt>Delivery</dt><dd>{{ $order->shipping_charges > 0 ? formatMoney($order->shipping_charges) : 'Free' }}</dd></div>
                            <div class="totals__grand"><dt>Total</dt><dd>{{ formatMoney($order->grand_total) }}</dd></div>
                        </dl>
                        <p class="small muted" style="margin: 0">{{ $order->payment_gateway === 'COD' ? 'Cash on delivery' : $order->payment_gateway }}</p>
                        <a class="btn btn--ghost btn--sm btn--block" style="margin-top: 12px" href="{{ url('orders/invoice/download/'.$order->id) }}">Download invoice</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
