@forelse ($deliveryAddresses as $address)
    <div class="address-card">
        <strong>{{ $address['name'] }}</strong>
        {{ $address['address'] }}, {{ $address['city'] }}, {{ $address['state'] }} {{ $address['pincode'] }}, {{ $address['country'] }}<br>
        <span class="muted">{{ $address['mobile'] }}</span>
        <div class="address-card__actions">
            <button type="button" class="btn btn--ghost btn--sm" data-address-edit="{{ $address['id'] }}"><x-shop.icon name="edit" class="icon--sm" />Edit</button>
            <button type="button" class="btn btn--danger btn--sm" data-address-remove="{{ $address['id'] }}" data-list="[data-address-list]"><x-shop.icon name="trash" class="icon--sm" />Remove</button>
        </div>
    </div>
@empty
    <p class="muted">No saved addresses yet.</p>
@endforelse
