@props(['coupon'])
@php
    $isFixed = $coupon->amount_type === 'Fixed';
    $value = $isFixed ? formatMoney($coupon->amount) : rtrim(rtrim(number_format($coupon->amount, 2), '0'), '.').'%';
@endphp
<div class="voucher">
    <div class="voucher__value">{{ $value }}<small>OFF</small></div>
    <div class="voucher__body">
        <strong>{{ $coupon->title ?: $coupon->discountLabel() }}</strong>
        <span class="muted">
            {{ $coupon->isStoreVoucher() ? 'On '.($coupon->vendor?->store_name ?? 'this store').' items' : 'On everything' }}
            @if ($coupon->min_spend > 0)
                · min. spend {{ formatMoney($coupon->min_spend) }}
            @endif
            @if (! $isFixed && $coupon->max_discount)
                · up to {{ formatMoney($coupon->max_discount) }}
            @endif
        </span>
        <span class="muted">Valid until {{ $coupon->expiry_date->format('j M Y') }}{{ $coupon->coupon_type === 'Single Time' ? ' · once per customer' : '' }}</span>
        <div class="voucher__code">
            <code>{{ $coupon->coupon_code }}</code>
            <button type="button" class="btn btn--sm btn--outline" data-copy="{{ $coupon->coupon_code }}"><x-shop.icon name="copy" class="icon--sm" />Copy</button>
        </div>
    </div>
</div>
