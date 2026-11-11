<section class="section" aria-labelledby="rail-{{ $id }}-title">
    <div class="section-head">
        <div>
            <h2 id="rail-{{ $id }}-title">{{ $title }}</h2>
            @if (! empty($lead))
                <p>{{ $lead }}</p>
            @endif
        </div>
        <div class="section-head__actions">
            @if (! empty($link))
                <a class="link" href="{{ $link }}">See all</a>
            @endif
            <button type="button" class="rail-nav" data-rail-scroll="#rail-{{ $id }}" data-direction="prev" aria-label="Scroll {{ $title }} back"><x-shop.icon name="chevron-left" /></button>
            <button type="button" class="rail-nav" data-rail-scroll="#rail-{{ $id }}" data-direction="next" aria-label="Scroll {{ $title }} forward"><x-shop.icon name="chevron-right" /></button>
        </div>
    </div>
    <div class="rail" id="rail-{{ $id }}">
        @foreach ($products as $product)
            <x-shop.product-card :product="$product" />
        @endforeach
    </div>
</section>
