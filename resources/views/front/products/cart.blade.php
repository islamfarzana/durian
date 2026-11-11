@extends('front.layout.layout')

@section('no_cart_panel', '1')

@section('content')
    <div data-reload-on-bag-change hidden></div>
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Your bag', 'url' => null]]])
    <h1>Your bag</h1>

    @if ($bag->isEmpty())
        <div class="empty-state">
            <x-shop.icon name="bag" />
            <h2>Your bag is empty</h2>
            <p>Add groceries, fashion, gadgets and more — everything arrives together.</p>
            <a class="btn btn--primary" href="{{ route('home') }}">Start shopping</a>
        </div>
    @else
        <div class="page-grid cart-page">
            <div>
                @if ($bag->freeDeliveryRemaining !== null)
                    <div class="free-delivery {{ $bag->freeDeliveryRemaining > 0 ? '' : 'free-delivery--done' }}" style="border-radius: 12px; margin-bottom: 14px">
                        {{ $bag->freeDeliveryRemaining > 0 ? 'Add '.formatMoney($bag->freeDeliveryRemaining).' more for free delivery' : 'You have free delivery on this order.' }}
                    </div>
                @endif

                @foreach ($bag->packages as $package)
                    <section class="package" aria-label="Package from {{ $package['name'] }}">
                        <div class="package__head">
                            <span>
                                Package {{ $loop->iteration }} of {{ $bag->packages->count() }} ·
                                @if ($package['url'])
                                    <a href="{{ $package['url'] }}"><x-shop.icon name="store" class="icon--sm" />{{ $package['name'] }}</a>
                                @else
                                    <x-shop.icon name="store" class="icon--sm" />{{ $package['name'] }}
                                @endif
                                @if ($package['is_official'])
                                    <span class="badge badge--official">Official</span>
                                @endif
                            </span>
                            <span>{{ formatMoney($package['subtotal']) }}</span>
                        </div>
                        @foreach ($package['lines'] as $line)
                            @php $problem = $line->problem(); @endphp
                            <div class="bag-line">
                                <a href="{{ $line->product->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $line->product->imageUrl('small') }}" alt=""></a>
                                <div>
                                    <a class="bag-line__name" href="{{ $line->product->url() }}">{{ $line->product->product_name }}</a>
                                    <div class="bag-line__meta">
                                        {{ $line->product->packLabel() ?? $line->row->size }} · {{ formatMoney($line->price->final) }} each
                                        @if ($line->price->flashSale)
                                            · <span class="badge badge--flash">Flash price</span>
                                        @endif
                                    </div>
                                    @if ($problem)
                                        <div class="bag-line__problem">{{ $problem }}</div>
                                    @endif
                                    <button type="button" class="remove-btn" data-bag-remove="{{ route('cart.items.destroy', $line->row->id) }}"><x-shop.icon name="trash" class="icon--sm" />Remove</button>
                                </div>
                                <div class="bag-line__side">
                                    <div class="qty-stepper qty-stepper--sm" style="display: flex">
                                        <button type="button" data-bag-set="{{ $line->quantity - 1 }}" data-product="{{ $line->product->id }}" data-size="{{ $line->row->size }}" aria-label="Remove one"><x-shop.icon name="minus" /></button>
                                        <output>{{ $line->quantity }}</output>
                                        <button type="button" data-bag-set="{{ $line->quantity + 1 }}" data-product="{{ $line->product->id }}" data-size="{{ $line->row->size }}" aria-label="Add one" @disabled($problem !== null || ($line->variant && $line->quantity >= $line->variant->stock))><x-shop.icon name="plus" /></button>
                                    </div>
                                    <span class="bag-line__price">
                                        {{ formatMoney($line->total()) }}
                                        @if ($line->saving() > 0)
                                            <s>{{ formatMoney($line->price->original * $line->quantity) }}</s>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            </div>

            <aside class="card sticky-card cart-summary" aria-labelledby="summary-title">
                <h2 class="card__title" id="summary-title">Order summary</h2>
                @include('front.layout.bag_totals', ['bag' => $bag, 'voucherFieldId' => 'page'])
                <a class="btn btn--primary btn--lg btn--block {{ $bag->hasProblems() ? 'is-disabled' : '' }}" href="{{ route('checkout') }}">Proceed to checkout</a>
                <p class="small muted" style="margin: 12px 0 0">Items from every store arrive together in one delivery. You can pay in cash when it arrives.</p>
            </aside>
        </div>
    @endif
@endsection
