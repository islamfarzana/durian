@extends('front.layout.layout')

@section('content')
    <div class="auth">
        <div class="auth__aside">
            <div>
                <x-shop.icon name="bag" class="icon--lg" />
                <h1 style="margin-top: 12px">Welcome to {{ $marketplace['name'] }}</h1>
                <p>One account for groceries, fashion, gadgets and every store on the marketplace.</p>
            </div>
            <ul>
                <li><x-shop.icon name="truck" />Track every order and package</li>
                <li><x-shop.icon name="heart" />Save favourites and follow stores</li>
                <li><x-shop.icon name="tag" />Use vouchers and flash deals</li>
                <li><x-shop.icon name="return" />Easy cancellations before shipping</li>
            </ul>
        </div>

        <div class="card">
            <div class="auth__tabs" data-tabs role="tablist">
                <button type="button" class="is-active" data-tab="login" role="tab" aria-selected="true">Log in</button>
                <button type="button" data-tab="register" role="tab" aria-selected="false">Create account</button>
            </div>

            <div data-tab-panel="login">
                <form method="post" action="{{ url('user/login') }}" data-json-form data-redirect-on-success>
                    @csrf
                    <div data-form-message hidden></div>
                    <div class="field">
                        <label for="login-email">Email</label>
                        <input class="input" type="email" id="login-email" name="email" required autocomplete="email">
                        <div class="field-error" data-error-for="email"></div>
                    </div>
                    <div class="field">
                        <label for="login-password">Password</label>
                        <input class="input" type="password" id="login-password" name="password" required autocomplete="current-password">
                        <div class="field-error" data-error-for="password"></div>
                    </div>
                    <button type="submit" class="btn btn--primary btn--block btn--lg">Log in</button>
                    <p class="small" style="margin: 12px 0 0; text-align: center"><a class="link" href="{{ route('password.forgot') }}">Forgot your password?</a></p>
                </form>
            </div>

            <div data-tab-panel="register" hidden>
                <form method="post" action="{{ url('user/register') }}" data-json-form data-reset-on-success>
                    @csrf
                    <div data-form-message hidden></div>
                    <div class="field">
                        <label for="register-name">Full name</label>
                        <input class="input" id="register-name" name="name" maxlength="100" required autocomplete="name">
                        <div class="field-error" data-error-for="name"></div>
                    </div>
                    <div class="field">
                        <label for="register-mobile">Mobile number</label>
                        <input class="input" id="register-mobile" name="mobile" inputmode="numeric" maxlength="11" required autocomplete="tel" placeholder="01XXXXXXXXX">
                        <div class="field-error" data-error-for="mobile"></div>
                    </div>
                    <div class="field">
                        <label for="register-email">Email</label>
                        <input class="input" type="email" id="register-email" name="email" maxlength="150" required autocomplete="email">
                        <div class="field-error" data-error-for="email"></div>
                    </div>
                    <div class="field">
                        <label for="register-password">Password</label>
                        <input class="input" type="password" id="register-password" name="password" minlength="6" required autocomplete="new-password">
                        <div class="field-error" data-error-for="password"></div>
                    </div>
                    <label class="check field">
                        <input type="checkbox" name="accept" value="1" required>
                        <span class="small">I agree to the terms &amp; conditions and the privacy policy.</span>
                    </label>
                    <div class="field-error" data-error-for="accept"></div>
                    <button type="submit" class="btn btn--primary btn--block btn--lg">Create account</button>
                    <p class="small muted" style="margin: 12px 0 0; text-align: center">We'll email you a link to activate your account.</p>
                </form>
            </div>

            <hr class="divider">
            <p class="small muted" style="margin: 0; text-align: center">Selling on {{ $marketplace['name'] }}? <a class="link" href="{{ url('admin/login') }}">Seller Center login</a> · <a class="link" href="{{ route('seller.register') }}">Open a store</a></p>
        </div>
    </div>
@endsection
