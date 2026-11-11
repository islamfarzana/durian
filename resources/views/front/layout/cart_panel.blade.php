<aside class="cart-panel" id="cart-panel" aria-label="Your bag">
    <div class="cart-panel__head">
        <div class="cart-panel__title">
            <x-shop.icon name="bag" class="icon--lg" />
            <div>Your bag<small><span data-bag-count>{{ $bag->itemCount() }}</span> <span data-bag-noun>{{ \Illuminate\Support\Str::plural('item', $bag->itemCount()) }}</span> · <span data-bag-total>{{ formatMoney($bag->total()) }}</span></small></div>
        </div>
        <button type="button" class="icon-btn" data-cart-close aria-label="Close your bag"><x-shop.icon name="x" /></button>
    </div>
    <div class="cart-panel__body" data-bag-body>
        @include('front.layout.cart_panel_body', ['bag' => $bag])
    </div>
</aside>

<button type="button" class="cart-tab" data-cart-toggle aria-label="Open your bag">
    <x-shop.icon name="bag" />
    <span><span data-bag-count>{{ $bag->itemCount() }}</span> <span data-bag-noun>{{ \Illuminate\Support\Str::plural('item', $bag->itemCount()) }}</span></span>
    <span class="cart-tab__total" data-bag-total>{{ formatMoney($bag->total()) }}</span>
</button>
