@if ($bag->voucher->applies())
    <div class="voucher-applied">
        <span><x-shop.icon name="tag" class="icon--sm" /> <b>{{ $bag->voucher->coupon->coupon_code }}</b> — you save {{ formatMoney($bag->voucherDiscount()) }}</span>
        <button type="button" data-voucher-remove>Remove</button>
    </div>
@else
    @if ($bag->voucher->coupon && $bag->voucher->error)
        <p class="voucher-error">{{ $bag->voucher->coupon->coupon_code }}: {{ $bag->voucher->error }} <button type="button" class="remove-btn" data-voucher-remove>Remove</button></p>
    @endif
    <button type="button" class="voucher-toggle" data-voucher-toggle><x-shop.icon name="tag" class="icon--sm" />Have a voucher code?</button>
    <form class="voucher-form" data-voucher-form hidden>
        <label class="sr-only" for="voucher-code-{{ $voucherFieldId ?? 'panel' }}">Voucher code</label>
        <input class="input" id="voucher-code-{{ $voucherFieldId ?? 'panel' }}" name="code" placeholder="Enter voucher code" autocomplete="off" maxlength="64" required>
        <button type="submit" class="btn btn--primary btn--sm">Apply</button>
    </form>
@endif

<dl class="totals">
    <div><dt>Subtotal ({{ $bag->itemCount() }} items)</dt><dd>{{ formatMoney($bag->subtotal) }}</dd></div>
    @if ($bag->savings > 0)
        <div class="is-saving"><dt>You save on deals</dt><dd>{{ formatMoney($bag->savings) }}</dd></div>
    @endif
    @if ($bag->voucherDiscount() > 0)
        <div class="is-saving"><dt>Voucher</dt><dd>-{{ formatMoney($bag->voucherDiscount()) }}</dd></div>
    @endif
    <div><dt>Delivery fee</dt><dd>{{ $bag->deliveryFee > 0 ? formatMoney($bag->deliveryFee) : 'Free' }}</dd></div>
    <div class="totals__grand"><dt>Total</dt><dd>{{ formatMoney($bag->total()) }}</dd></div>
</dl>
