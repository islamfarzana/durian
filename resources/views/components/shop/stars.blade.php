@props(['rating' => 0, 'count' => null, 'size' => null])
@php
    $rating = round((float) $rating, 1);
    $filled = (int) round($rating);
@endphp
<span {{ $attributes->merge(['class' => 'stars'.($size ? ' stars--'.$size : '')]) }}>
    <span class="stars__icons" role="img" aria-label="{{ $rating }} out of 5 stars">
        @for ($star = 1; $star <= 5; $star++)
            <x-shop.icon name="star" class="{{ $star <= $filled ? 'icon--fill' : '' }}" />
        @endfor
    </span>
    @if ($count !== null)
        <span>{{ number_format($rating, 1) }} ({{ $count }})</span>
    @endif
</span>
