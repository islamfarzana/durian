<form method="post" action="{{ route('addresses.save') }}" data-json-form data-address-form data-address-list="[data-address-list]" @if (! empty($reloadAfterSave)) data-reload-after-save="1" @endif>
    @csrf
    <input type="hidden" name="delivery_id" value="">
    <div class="field-row">
        <div class="field">
            <label for="delivery_name">Full name</label>
            <input class="input" id="delivery_name" name="delivery_name" maxlength="100" required autocomplete="name">
            <div class="field-error" data-error-for="delivery_name"></div>
        </div>
        <div class="field">
            <label for="delivery_mobile">Mobile number</label>
            <input class="input" id="delivery_mobile" name="delivery_mobile" inputmode="numeric" maxlength="11" required autocomplete="tel" placeholder="01XXXXXXXXX">
            <div class="field-error" data-error-for="delivery_mobile"></div>
        </div>
    </div>
    <div class="field">
        <label for="delivery_address">Address</label>
        <input class="input" id="delivery_address" name="delivery_address" maxlength="100" required autocomplete="street-address" placeholder="House, road, area">
        <div class="field-error" data-error-for="delivery_address"></div>
    </div>
    <div class="field-row">
        <div class="field">
            <label for="delivery_city">City</label>
            <input class="input" id="delivery_city" name="delivery_city" maxlength="100" required list="delivery-city-options" value="{{ $deliveryCity ?? '' }}" autocomplete="address-level2">
            <datalist id="delivery-city-options">
                @foreach ($deliveryCities ?? [] as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
            <div class="field-error" data-error-for="delivery_city"></div>
        </div>
        <div class="field">
            <label for="delivery_state">Area / district</label>
            <input class="input" id="delivery_state" name="delivery_state" maxlength="100" required autocomplete="address-level1">
            <div class="field-error" data-error-for="delivery_state"></div>
        </div>
        <div class="field">
            <label for="delivery_pincode">Postcode</label>
            <input class="input" id="delivery_pincode" name="delivery_pincode" inputmode="numeric" maxlength="6" required autocomplete="postal-code">
            <div class="field-error" data-error-for="delivery_pincode"></div>
        </div>
        <div class="field">
            <label for="delivery_country">Country</label>
            <select class="select" id="delivery_country" name="delivery_country" required>
                @foreach ($countries ?? [] as $country)
                    @php $countryName = is_array($country) ? $country['country_name'] : $country->country_name; @endphp
                    <option value="{{ $countryName }}" @selected($countryName === 'Bangladesh')>{{ $countryName }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error-for="delivery_country"></div>
        </div>
    </div>
    <div data-form-message hidden></div>
    <button type="submit" class="btn btn--primary">Save address</button>
</form>
