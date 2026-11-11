@php
    $statusClass = match (true) {
        $order->order_status === 'Delivered' => 'status--delivered',
        $order->order_status === 'Cancelled' => 'status--cancelled',
        in_array($order->order_status, ['Shipped', 'Partially Shipped', 'Partially Delivered'], true) => 'status--shipped',
        default => 'status--processing',
    };
@endphp
<article class="order-card">
    <div class="order-card__head">
        <div>
            <strong>Order #{{ $order->id }}</strong>
            <span class="muted"> · {{ $order->created_at?->timezone(config('marketplace.timezone'))->format('j M Y, g:i A') }}</span>
        </div>
        <span class="status {{ $statusClass }}">{{ $order->order_status }}</span>
    </div>
    <div class="order-card__body">
        <div class="order-card__thumbs">
            @foreach ($order->orders_products->take(4) as $line)
                <img src="{{ \App\Models\Product::imageUrlFor($line->product?->product_image, 'small') }}" alt="{{ $line->product_name }}" title="{{ $line->product_name }}">
            @endforeach
            @if ($order->orders_products->count() > 4)
                <span class="muted small" style="align-self: center">+{{ $order->orders_products->count() - 4 }}</span>
            @endif
        </div>
        <div class="small muted">
            {{ $order->orders_products->sum('product_qty') }} items · {{ $order->orders_products->pluck('vendor_id')->unique()->count() }} {{ \Illuminate\Support\Str::plural('package', $order->orders_products->pluck('vendor_id')->unique()->count()) }}
            @if ($order->delivery_date)
                <br>Delivery {{ $order->delivery_date->format('D, j M') }} · {{ $order->delivery_slot }}
            @endif
        </div>
        <div class="order-card__total">
            {{ $order->payment_gateway === 'COD' ? 'Cash on delivery' : $order->payment_gateway }}
            <strong>{{ formatMoney($order->grand_total) }}</strong>
        </div>
        <a class="btn btn--outline btn--sm" href="{{ route('orders', $order->id) }}">View details</a>
    </div>
</article>
