<div class="summary-line" style="margin-bottom: 8px">
    <img src="{{ $product->imageUrl('small') }}" alt="">
    <strong class="summary-line__name">{{ $product->product_name }}</strong>
</div>
@foreach ($product->activeVariants() as $variant)
    @php
        $price = $product->price($variant);
        $quantity = (int) ($variantQuantities[$variant->size] ?? 0);
    @endphp
    <div class="option-row">
        <div>
            <strong>{{ $variant->size }}</strong>
            <div class="price{{ $price->hasDiscount() ? ' price--deal' : '' }}">
                <span class="price__now" style="font-size: 15px">{{ formatMoney($price->final) }}</span>
                @if ($price->hasDiscount())
                    <s class="price__was">{{ formatMoney($price->original) }}</s>
                @endif
            </div>
            <span class="small {{ $variant->stock < 1 ? 'stock-note--out' : ($variant->stock <= 5 ? 'stock-note--low' : 'muted') }}">
                {{ $variant->stock < 1 ? 'Out of stock' : ($variant->stock <= 5 ? 'Only '.$variant->stock.' left' : 'In stock') }}
            </span>
        </div>
        @if ($variant->stock > 0)
            <div class="qty-control qty-control--inline" data-qty-control data-variant data-product="{{ $product->id }}" data-size="{{ $variant->size }}" data-qty="{{ $quantity }}">
                <button type="button" class="qty-add" aria-label="Add {{ $variant->size }} to bag"><x-shop.icon name="plus" /></button>
                <div class="qty-stepper">
                    <button type="button" data-step="down" aria-label="Remove one"><x-shop.icon name="minus" /></button>
                    <output data-qty-value>{{ $quantity }}</output>
                    <button type="button" data-step="up" aria-label="Add one more"><x-shop.icon name="plus" /></button>
                </div>
            </div>
        @endif
    </div>
@endforeach
<a class="btn btn--ghost btn--block" style="margin-top: 14px" href="{{ $product->url() }}">View full details</a>
