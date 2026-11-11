@props(['name' => 'Durian'])
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'brand']) }} aria-label="{{ $name }} home">
    <svg class="brand__mark" viewBox="0 0 40 42" aria-hidden="true" focusable="false">
        <path d="M20.0 3.2 L22.6 6.8 L26.3 4.3 L27.5 8.5 L31.9 7.4 L31.5 11.7 L36.0 12.1 L34.1 16.1 L38.2 17.9 L35.0 21.0 L38.2 24.1 L34.1 25.9 L36.0 29.9 L31.5 30.3 L31.9 34.6 L27.5 33.5 L26.3 37.7 L22.6 35.2 L20.0 38.8 L17.4 35.2 L13.7 37.7 L12.5 33.5 L8.1 34.6 L8.5 30.3 L4.0 29.9 L5.9 25.9 L1.8 24.1 L5.0 21.0 L1.8 17.9 L5.9 16.1 L4.0 12.1 L8.5 11.7 L8.1 7.4 L12.5 8.5 L13.7 4.3 L17.4 6.8Z" fill="var(--dz-primary)"/>
        <circle cx="20" cy="21" r="10.5" fill="#ffd166"/>
        <path d="M20 12.5c-2.6 3.4-2.6 13.6 0 17M14.5 16c1.6 2.8 1.6 7.2 0 10M25.5 16c-1.6 2.8-1.6 7.2 0 10" stroke="#e0a400" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        <path d="M20 3.5c1-2 3-2.8 5-2.5-.6 2-2.4 3.2-5 2.5z" fill="var(--dz-accent)"/>
    </svg>
    <span class="brand__name">{{ $name }}</span>
</a>
