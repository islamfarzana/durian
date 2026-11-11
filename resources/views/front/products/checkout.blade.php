@extends('front.layout.layout')

@section('no_cart_panel', '1')

@section('content')
    @php
        $firstOpenDay = collect($deliveryDays)->first(fn ($day) => collect($day['slots'])->contains('available', true));
        $firstOpenSlot = $firstOpenDay ? collect($firstOpenDay['slots'])->firstWhere('available', true) : null;
        $selectedSlot = (int) old('delivery_slot', $firstOpenSlot['id'] ?? 0);
        $selectedDate = old('delivery_date', $firstOpenDay['date'] ?? '');
        $selectedAddress = (int) old('address_id', $addresses->first()?->id);
        $afterVoucher = max(0, $bag->subtotal - $bag->voucherDiscount());
        $initialFee = (float) ($deliveryFees[$selectedAddress] ?? $bag->deliveryFee);
    @endphp

    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Your bag', 'url' => route('cart')], ['label' => 'Checkout', 'url' => null]]])
    <h1>Checkout</h1>

    @if ($errors->any())
        <div class="alert alert--error" role="alert">
            <x-shop.icon name="info" />
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="page-grid" data-checkout data-subtotal-after-voucher="{{ $afterVoucher }}" data-currency="{{ $marketplace['currency'] }}">
        <div>
            <section class="step" aria-labelledby="step-address">
                <div class="step__head"><span class="step__num">1</span><h2 id="step-address">Delivery address</h2></div>
                @if ($addresses->isEmpty())
                    <p class="muted">Add the address we should deliver to.</p>
                @endif
                <div class="option-grid">
                    @foreach ($addresses as $address)
                        <label class="option-card">
                            <input type="radio" name="address_id" value="{{ $address->id }}" form="checkout-form" data-fee="{{ $deliveryFees[$address->id] }}" @checked($selectedAddress === (int) $address->id) required>
                            <strong>{{ $address->name }}</strong>
                            <small>{{ $address->address }}, {{ $address->city }} {{ $address->pincode }}<br>{{ $address->mobile }}</small>
                        </label>
                    @endforeach
                </div>
                <details style="margin-top: 14px" @if ($addresses->isEmpty()) open @endif>
                    <summary class="link" style="cursor: pointer">+ Add a new address</summary>
                    <div style="margin-top: 14px">
                        @include('front.users.address_form', ['reloadAfterSave' => true])
                    </div>
                </details>
            </section>

            <form id="checkout-form" method="post" action="{{ route('checkout.store') }}">
                @csrf
                @if ($firstOpenDay)
                    <section class="step" aria-labelledby="step-slot">
                        <div class="step__head"><span class="step__num">2</span><h2 id="step-slot">Delivery time</h2></div>
                        <div class="day-tabs" role="tablist" aria-label="Delivery day">
                            @foreach ($deliveryDays as $day)
                                <button type="button" role="tab" class="day-tab {{ $day['date'] === $selectedDate ? 'is-active' : '' }}" data-day-tab="{{ $day['date'] }}" aria-selected="{{ $day['date'] === $selectedDate ? 'true' : 'false' }}">{{ $day['label'] }}</button>
                            @endforeach
                        </div>
                        @foreach ($deliveryDays as $day)
                            <div class="slot-grid" data-day-panel="{{ $day['date'] }}" @if ($day['date'] !== $selectedDate) hidden @endif>
                                @foreach ($day['slots'] as $slot)
                                    <label class="slot">
                                        <input type="radio" name="delivery_slot" value="{{ $slot['id'] }}" data-date="{{ $day['date'] }}" @disabled(! $slot['available']) @checked($day['date'] === $selectedDate && $slot['id'] === $selectedSlot)>
                                        <span>{{ $slot['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        <input type="hidden" name="delivery_date" value="{{ $selectedDate }}" data-delivery-date>
                    </section>
                @endif

                <section class="step" aria-labelledby="step-payment">
                    <div class="step__head"><span class="step__num">{{ $firstOpenDay ? 3 : 2 }}</span><h2 id="step-payment">Payment</h2></div>
                    <div class="option-grid">
                        @foreach ($paymentGateways as $gateway => $label)
                            <label class="option-card">
                                <input type="radio" name="payment_gateway" value="{{ $gateway }}" @checked(old('payment_gateway', array_key_first($paymentGateways)) === $gateway) required>
                                <strong>{{ $label }}</strong>
                                <small>{{ $gateway === 'COD' ? 'Pay in cash when your order arrives.' : 'You will be sent to PayPal to pay securely.' }}</small>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="step" aria-labelledby="step-notes">
                    <div class="step__head"><span class="step__num">{{ $firstOpenDay ? 4 : 3 }}</span><h2 id="step-notes">Delivery instructions</h2></div>
                    <label class="sr-only" for="notes">Delivery instructions</label>
                    <textarea class="textarea" id="notes" name="notes" maxlength="500" placeholder="Gate code, landmark, or call before arriving (optional)">{{ old('notes') }}</textarea>
                </section>
            </form>
        </div>

        <aside class="card sticky-card" aria-labelledby="checkout-summary">
            <h2 class="card__title" id="checkout-summary">Order summary</h2>
            @foreach ($bag->packages as $package)
                <p class="small muted" style="margin: 8px 0 4px"><x-shop.icon name="store" class="icon--sm" /> {{ $package['name'] }}</p>
                @foreach ($package['lines'] as $line)
                    <div class="summary-line">
                        <img src="{{ $line->product->imageUrl('small') }}" alt="">
                        <span class="summary-line__name">{{ $line->product->product_name }} × {{ $line->quantity }}</span>
                        <strong>{{ formatMoney($line->total()) }}</strong>
                    </div>
                @endforeach
            @endforeach
            <hr class="divider">
            <dl class="totals">
                <div><dt>Subtotal</dt><dd>{{ formatMoney($bag->subtotal) }}</dd></div>
                @if ($bag->savings > 0)
                    <div class="is-saving"><dt>Deals</dt><dd>{{ formatMoney($bag->savings) }}</dd></div>
                @endif
                @if ($bag->voucherDiscount() > 0)
                    <div class="is-saving"><dt>Voucher {{ $bag->voucher->coupon->coupon_code }}</dt><dd>-{{ formatMoney($bag->voucherDiscount()) }}</dd></div>
                @endif
                <div><dt>Delivery fee</dt><dd data-delivery-fee>{{ $initialFee > 0 ? formatMoney($initialFee) : 'Free' }}</dd></div>
                <div class="totals__grand"><dt>Total</dt><dd data-checkout-total>{{ formatMoney($afterVoucher + $initialFee) }}</dd></div>
            </dl>
            <label class="check" style="margin-bottom: 14px">
                <input type="checkbox" name="accept" value="yes" form="checkout-form" required>
                <span class="small">I agree to the terms of sale and the return policy.</span>
            </label>
            <button type="submit" form="checkout-form" class="btn btn--primary btn--lg btn--block" @disabled($addresses->isEmpty())>Place order</button>
            @if ($addresses->isEmpty())
                <p class="small muted" style="margin: 10px 0 0">Add a delivery address to place your order.</p>
            @endif
        </aside>
    </div>
@endsection
