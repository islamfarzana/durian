@extends('front.layout.layout')

@section('content')
    <section class="seller-hero">
        <div>
            <span class="badge badge--deal" style="margin-bottom: 12px">Seller Center</span>
            <h1 class="hero__title">Sell anything on <em>{{ $marketplace['name'] }}</em></h1>
            <p class="hero__lead">Groceries, fashion, electronics, home goods — open your store in minutes, list products with sizes and pack options, and we bring the customers and the delivery.</p>
            <div class="cluster">
                <a class="btn btn--primary btn--lg" href="#seller-register">Open your store</a>
                <a class="btn btn--ghost btn--lg" href="{{ url('admin/login') }}">Seller login</a>
            </div>
        </div>
        <div class="card" id="seller-register">
            <h2 class="card__title">Create your seller account</h2>
            @if ($errors->any())
                <div class="alert alert--error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="post" action="{{ url('vendor/register') }}">
                @csrf
                <div class="field">
                    <label for="seller-name">Your name</label>
                    <input class="input" id="seller-name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
                </div>
                <div class="field-row">
                    <div class="field">
                        <label for="seller-mobile">Mobile number</label>
                        <input class="input" id="seller-mobile" name="mobile" value="{{ old('mobile') }}" inputmode="numeric" required minlength="10" maxlength="11" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label for="seller-email">Email</label>
                        <input class="input" type="email" id="seller-email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                </div>
                <div class="field">
                    <label for="seller-password">Password</label>
                    <input class="input" type="password" id="seller-password" name="password" required minlength="6" autocomplete="new-password">
                </div>
                <label class="check field">
                    <input type="checkbox" name="accept" value="1" required>
                    <span class="small">I agree to the seller terms and the marketplace commission.</span>
                </label>
                <button type="submit" class="btn btn--primary btn--block btn--lg">Register as a seller</button>
                <p class="small muted" style="margin: 10px 0 0">After you confirm your email, add your shop and bank details in the Seller Center. We review every store before it goes live.</p>
            </form>
        </div>
    </section>

    <section class="section">
        <h2>Why sell with us</h2>
        <div class="benefits">
            <div class="usp"><span class="usp__icon"><x-shop.icon name="user" /></span><div><strong>Customers every day</strong><span>Shoppers already come here for daily groceries</span></div></div>
            <div class="usp"><span class="usp__icon"><x-shop.icon name="truck" /></span><div><strong>We deliver</strong><span>Scheduled slots and cash on delivery included</span></div></div>
            <div class="usp"><span class="usp__icon"><x-shop.icon name="bolt" /></span><div><strong>Flash sales &amp; vouchers</strong><span>Join campaigns and create store vouchers</span></div></div>
            <div class="usp"><span class="usp__icon"><x-shop.icon name="chart" /></span><div><strong>Clear payouts</strong><span>See every sale, commission, and payout</span></div></div>
        </div>
    </section>

    <section class="section">
        <h2>How it works</h2>
        <div class="steps-row">
            <div><strong>Register</strong><p class="small muted" style="margin: 4px 0 0">Create your seller account and confirm your email.</p></div>
            <div><strong>Set up your store</strong><p class="small muted" style="margin: 4px 0 0">Add shop, bank details, logo and banner in the Seller Center.</p></div>
            <div><strong>List products</strong><p class="small muted" style="margin: 4px 0 0">Add products with pack sizes or sizes, prices and stock.</p></div>
            <div><strong>Sell &amp; get paid</strong><p class="small muted" style="margin: 4px 0 0">Ship your packages and request payouts for delivered orders.</p></div>
        </div>
    </section>
@endsection
