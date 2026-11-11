@props(['vendor'])
<a href="{{ $vendor->storeUrl() }}" {{ $attributes->merge(['class' => 'store-card']) }}>
    <span class="store-logo">
        @if ($vendor->logoUrl())
            <img src="{{ $vendor->logoUrl() }}" alt="" loading="lazy">
        @else
            {{ $vendor->initials() }}
        @endif
    </span>
    <span class="store-card__name">{{ $vendor->store_name }}</span>
    @if ($vendor->is_official)
        <span class="badge badge--official"><x-shop.icon name="verified" class="icon--sm" />Official Store</span>
    @else
        <span class="badge badge--verified"><x-shop.icon name="shield" class="icon--sm" />Verified seller</span>
    @endif
    <span class="store-card__meta">
        {{ number_format($vendor->followers_count ?? 0) }} followers
        @isset($vendor->products_count)
            · {{ number_format($vendor->products_count) }} products
        @endisset
    </span>
</a>
