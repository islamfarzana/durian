@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Flash Sale', 'url' => null]]])

    @if ($flashSale)
        <section class="flash-band section">
            <div class="section-head">
                <div>
                    <h1 style="display: flex; align-items: center; gap: 8px; margin: 0"><x-shop.icon name="bolt" class="icon--lg icon--fill" style="color: var(--dz-accent)" />{{ $flashSale->title }}</h1>
                    <p>Limited stock at flash prices. When the stock or the clock runs out, prices go back to normal.</p>
                </div>
                <span class="countdown" data-countdown="{{ $flashSale->ends_at->toIso8601String() }}">
                    <span data-countdown-label>Ends in</span>
                    <span class="countdown__digits"><span data-unit="h">00</span>:<span data-unit="m">00</span>:<span data-unit="s">00</span></span>
                </span>
            </div>
        </section>

        @include('front.products.catalog', ['formAction' => url()->current(), 'keep' => [], 'gridId' => 'flash-grid'])
    @else
        <div class="empty-state">
            <x-shop.icon name="bolt" />
            <h1>No flash sale right now</h1>
            <p>Flash sales drop prices for a few hours on limited stock. Check back soon, or browse today's deals.</p>
            <a class="btn btn--primary" href="{{ route('offers') }}">See today's deals</a>
        </div>
    @endif

    @if ($upcoming->isNotEmpty())
        <section class="section" aria-labelledby="upcoming-title">
            <h2 id="upcoming-title">Coming up</h2>
            <div class="promo-grid">
                @foreach ($upcoming as $sale)
                    <div class="promo-card">
                        <div>
                            <h3>{{ $sale->title }}</h3>
                            <p>Starts {{ $sale->starts_at->timezone(config('marketplace.timezone'))->format('D, j M · g:i A') }} · {{ $sale->approved_items_count }} {{ \Illuminate\Support\Str::plural('deal', $sale->approved_items_count) }}</p>
                        </div>
                        <span class="countdown" data-countdown="{{ $sale->starts_at->toIso8601String() }}">
                            <span data-countdown-label>Starts in</span>
                            <span class="countdown__digits"><span data-unit="h">00</span>:<span data-unit="m">00</span>:<span data-unit="s">00</span></span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
