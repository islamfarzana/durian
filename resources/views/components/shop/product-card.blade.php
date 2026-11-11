@props(['product'])
@php
    $variant = $product->defaultVariant();
    $variants = $product->activeVariants();
    $price = $product->price($variant);
    $hasOptions = $variants->count() > 1;
    $purchasable = $product->isPurchasable();
    $quantity = (int) ($bagQuantities[$product->id] ?? 0);
    $wished = in_array((int) $product->id, $wishlistIds ?? [], true);
    $flashSale = $price->flashSale;
    $pack = $product->packLabel() ?? ($hasOptions ? $variants->count().' options' : null);
    $store = $product->vendor_id > 0 ? $product->vendor : null;
@endphp
<article class="product-card">
    <div class="product-card__visual">
        <a href="{{ $product->url() }}" class="product-card__media" tabindex="-1" aria-hidden="true">
            <img src="{{ $product->imageUrl('medium') }}" alt="" loading="lazy" width="250" height="250">
        </a>
        <div class="product-card__badges">
            @if ($price->discountPercent() > 0)
                <span class="badge badge--deal">-{{ $price->discountPercent() }}%</span>
            @endif
            @if ($flashSale)
                <span class="badge badge--flash"><x-shop.icon name="bolt" class="icon--sm icon--fill" />Flash</span>
            @endif
        </div>
        <button type="button" class="wish-btn" data-wishlist="{{ route('wishlist.toggle', $product->id) }}" aria-pressed="{{ $wished ? 'true' : 'false' }}" aria-label="Save {{ $product->product_name }} to favourites">
            <x-shop.icon name="heart" />
        </button>
        @if ($purchasable)
            <div class="qty-control" data-qty-control data-product="{{ $product->id }}" data-size="{{ $variant->size }}" data-qty="{{ $quantity }}" @if ($hasOptions) data-options-url="{{ route('products.options', $product->id) }}" @endif>
                <button type="button" class="qty-add" aria-label="Add {{ $product->product_name }} to bag"><x-shop.icon name="plus" /></button>
                <button type="button" class="qty-badge" aria-label="Change quantity in bag" data-qty-value>{{ $quantity }}</button>
                <div class="qty-stepper">
                    <button type="button" data-step="down" aria-label="Remove one"><x-shop.icon name="minus" /></button>
                    <output data-qty-value>{{ $quantity }}</output>
                    <button type="button" data-step="up" aria-label="Add one more"><x-shop.icon name="plus" /></button>
                </div>
            </div>
        @else
            <span class="product-card__soldout">{{ $variant === null || $variant->stock < 1 ? 'Out of stock' : 'Unavailable' }}</span>
        @endif
    </div>
    <div class="product-card__body">
        <div class="price{{ $price->hasDiscount() ? ' price--deal' : '' }}">
            <span class="price__now">{{ formatMoney($price->final) }}</span>
            @if ($price->hasDiscount())
                <s class="price__was">{{ formatMoney($price->original) }}</s>
            @endif
        </div>
        <a href="{{ $product->url() }}" class="product-card__name">{{ $product->product_name }}</a>
        <div class="product-card__meta">
            <span class="pack">{{ $pack }}</span>
            @if ($product->rating_count > 0)
                <x-shop.stars :rating="$product->rating_avg" :count="$product->rating_count" />
            @elseif ($product->sold_count > 0)
                <span>{{ number_format($product->sold_count) }} sold</span>
            @endif
        </div>
        @if ($flashSale && $flashSale->stock_limit)
            <div class="flash-meter">
                {{ $flashSale->sold_count }} / {{ $flashSale->stock_limit }} claimed
                <div class="progress"><span style="width: {{ $flashSale->soldPercent() }}%"></span></div>
            </div>
        @endif
        <div class="product-card__seller">
            @if ($store)
                @if ($store->is_official)
                    <x-shop.icon name="verified" label="Official store" />
                @endif
                <span>{{ $store->store_name }}</span>
            @else
                <x-shop.icon name="verified" label="Sold by the marketplace" />
                <span>{{ $platformSellerName ?? '' }}</span>
            @endif
        </div>
    </div>
</article>
