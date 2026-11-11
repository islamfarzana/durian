@if ($bag->isEmpty())
    <div class="bag-empty">
        <x-shop.icon name="bag" />
        <strong>Your bag is empty</strong>
        <span>Tap <b>+</b> on any product to add it here.</span>
        <a class="btn btn--outline btn--sm" href="{{ route('search', ['collection' => 'deals']) }}">Browse today's deals</a>
    </div>
@else
    <div class="cart-panel__scroll">
        @if ($bag->freeDeliveryRemaining !== null)
            @if ($bag->freeDeliveryRemaining > 0)
                @php $threshold = $bag->freeDeliveryRemaining + max(0, $bag->subtotal - $bag->voucherDiscount()); @endphp
                <div class="free-delivery">
                    Shop {{ formatMoney($bag->freeDeliveryRemaining) }} more for free delivery
                    <div class="progress"><span style="width: {{ $threshold > 0 ? min(100, round((1 - $bag->freeDeliveryRemaining / $threshold) * 100)) : 0 }}%"></span></div>
                </div>
            @else
                <div class="free-delivery free-delivery--done">You've unlocked free delivery on this order.</div>
            @endif
        @endif

        @foreach ($bag->packages as $package)
            <section class="package" aria-label="Items from {{ $package['name'] }}">
                <div class="package__head">
                    @if ($package['url'])
                        <a href="{{ $package['url'] }}"><x-shop.icon name="store" class="icon--sm" />{{ $package['name'] }}</a>
                    @else
                        <span><x-shop.icon name="store" class="icon--sm" />{{ $package['name'] }}</span>
                    @endif
                    @if ($package['is_official'])
                        <span class="badge badge--official">Official</span>
                    @endif
                </div>
                @foreach ($package['lines'] as $line)
                    @php
                        $problem = $line->problem();
                        $label = $line->product->packLabel() ?? (in_array(mb_strtolower($line->row->size), ['normal', 'standard', 'default'], true) ? null : $line->row->size);
                    @endphp
                    <div class="bag-line">
                        <a href="{{ $line->product->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $line->product->imageUrl('small') }}" alt="" loading="lazy"></a>
                        <div>
                            <a class="bag-line__name" href="{{ $line->product->url() }}">{{ $line->product->product_name }}</a>
                            <div class="bag-line__meta">{{ $label ? $label.' · ' : '' }}{{ formatMoney($line->price->final) }} each</div>
                            @if ($problem)
                                <div class="bag-line__problem">{{ $problem }}</div>
                            @endif
                        </div>
                        <div class="bag-line__side">
                            <div class="qty-stepper qty-stepper--sm" style="display:flex">
                                <button type="button" data-bag-set="{{ $line->quantity - 1 }}" data-product="{{ $line->product->id }}" data-size="{{ $line->row->size }}" aria-label="Remove one {{ $line->product->product_name }}"><x-shop.icon name="{{ $line->quantity > 1 ? 'minus' : 'trash' }}" /></button>
                                <output>{{ $line->quantity }}</output>
                                <button type="button" data-bag-set="{{ $line->quantity + 1 }}" data-product="{{ $line->product->id }}" data-size="{{ $line->row->size }}" aria-label="Add one {{ $line->product->product_name }}" @disabled($problem !== null || ($line->variant && $line->quantity >= $line->variant->stock))><x-shop.icon name="plus" /></button>
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

    <div class="cart-panel__foot">
        @include('front.layout.bag_totals', ['bag' => $bag])
        <a class="checkout-btn" href="{{ route('checkout') }}" @if ($bag->hasProblems()) aria-disabled="true" @endif>
            <span>{{ $bag->hasProblems() ? 'Fix your bag to check out' : 'Checkout' }}</span>
            <span>{{ formatMoney($bag->total()) }}</span>
        </a>
    </div>
@endif
