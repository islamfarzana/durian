<dialog class="dialog" id="location-dialog" aria-labelledby="location-dialog-title">
    <div class="dialog__head">
        <h2 id="location-dialog-title">Where should we deliver?</h2>
        <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-shop.icon name="x" /></button>
    </div>
    <form class="dialog__body" method="post" action="{{ route('delivery-area.update') }}">
        @csrf
        <p class="muted">Delivery fees and time slots depend on your city.</p>
        <div class="option-grid">
            @forelse ($deliveryCities as $city)
                <label class="option-card">
                    <input type="radio" name="city" value="{{ $city }}" @checked($city === $deliveryCity) required>
                    <strong>{{ $city }}</strong>
                    <small>Scheduled delivery</small>
                </label>
            @empty
                <p class="muted">No delivery cities are set up yet.</p>
            @endforelse
        </div>
        <button type="submit" class="btn btn--primary btn--block" style="margin-top: 16px">Save location</button>
    </form>
</dialog>

<dialog class="dialog" id="options-dialog" aria-labelledby="options-dialog-title">
    <div class="dialog__head">
        <h2 id="options-dialog-title">Choose an option</h2>
        <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-shop.icon name="x" /></button>
    </div>
    <div class="dialog__body" data-options-body></div>
</dialog>

<div class="toasts" id="toasts" aria-live="polite"></div>
