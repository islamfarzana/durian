@extends('front.layout.layout')

@section('content')
    @php $user = Auth::user(); @endphp
    <div class="account">
        @include('front.users.account_nav')
        <div>
            <h1>Profile &amp; password</h1>
            <div class="card">
                <h2 class="card__title">Contact details</h2>
                <form method="post" action="{{ route('account.profile') }}" data-json-form>
                    @csrf
                    <div data-form-message hidden></div>
                    <div class="field-row">
                        <div class="field">
                            <label for="profile-name">Full name</label>
                            <input class="input" id="profile-name" name="name" value="{{ $user->name }}" maxlength="100" required>
                            <div class="field-error" data-error-for="name"></div>
                        </div>
                        <div class="field">
                            <label for="profile-mobile">Mobile</label>
                            <input class="input" id="profile-mobile" name="mobile" value="{{ $user->mobile }}" inputmode="numeric" maxlength="11" required>
                            <div class="field-error" data-error-for="mobile"></div>
                        </div>
                        <div class="field">
                            <label for="profile-email">Email</label>
                            <input class="input" id="profile-email" value="{{ $user->email }}" disabled>
                        </div>
                    </div>
                    <div class="field">
                        <label for="profile-address">Address</label>
                        <input class="input" id="profile-address" name="address" value="{{ $user->address }}" maxlength="100" required>
                        <div class="field-error" data-error-for="address"></div>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="profile-city">City</label>
                            <input class="input" id="profile-city" name="city" value="{{ $user->city }}" maxlength="100" required>
                            <div class="field-error" data-error-for="city"></div>
                        </div>
                        <div class="field">
                            <label for="profile-state">Area / district</label>
                            <input class="input" id="profile-state" name="state" value="{{ $user->state }}" maxlength="100" required>
                            <div class="field-error" data-error-for="state"></div>
                        </div>
                        <div class="field">
                            <label for="profile-pincode">Postcode</label>
                            <input class="input" id="profile-pincode" name="pincode" value="{{ $user->pincode }}" inputmode="numeric" maxlength="6" required>
                            <div class="field-error" data-error-for="pincode"></div>
                        </div>
                        <div class="field">
                            <label for="profile-country">Country</label>
                            <select class="select" id="profile-country" name="country" required>
                                @foreach ($countries as $country)
                                    <option value="{{ $country['country_name'] }}" @selected($country['country_name'] === ($user->country ?: 'Bangladesh'))>{{ $country['country_name'] }}</option>
                                @endforeach
                            </select>
                            <div class="field-error" data-error-for="country"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary">Save details</button>
                </form>
            </div>

            <div class="card">
                <h2 class="card__title">Change password</h2>
                <form method="post" action="{{ url('user/update-password') }}" data-json-form data-reset-on-success>
                    @csrf
                    <div data-form-message hidden></div>
                    <div class="field-row">
                        <div class="field">
                            <label for="current-password">Current password</label>
                            <input class="input" type="password" id="current-password" name="current_password" required autocomplete="current-password">
                            <div class="field-error" data-error-for="current_password"></div>
                        </div>
                        <div class="field">
                            <label for="new-password">New password</label>
                            <input class="input" type="password" id="new-password" name="new_password" minlength="6" required autocomplete="new-password">
                            <div class="field-error" data-error-for="new_password"></div>
                        </div>
                        <div class="field">
                            <label for="confirm-password">Confirm new password</label>
                            <input class="input" type="password" id="confirm-password" name="confirm_password" minlength="6" required autocomplete="new-password">
                            <div class="field-error" data-error-for="confirm_password"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
@endsection
