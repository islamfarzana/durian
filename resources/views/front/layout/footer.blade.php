<footer class="site-footer">
    <div class="site-footer__grid">
        <div class="site-footer__brand">
            <x-shop.logo :name="$marketplace['name']" />
            <p>{{ $marketplace['tagline'] }}. Thousands of products from trusted stores, one bag, and delivery at a time that suits you.</p>
            @if ($marketplace['hotline'] !== '')
                <p><a class="link" href="tel:{{ preg_replace('/[^0-9+]/', '', $marketplace['hotline']) }}"><x-shop.icon name="phone" class="icon--sm" /> {{ $marketplace['hotline'] }}</a></p>
            @endif
            @if ($marketplace['email'] !== '')
                <p><a class="link" href="mailto:{{ $marketplace['email'] }}"><x-shop.icon name="mail" class="icon--sm" /> {{ $marketplace['email'] }}</a></p>
            @endif
        </div>
        <div>
            <h4>Shop</h4>
            <ul>
                <li><a href="{{ route('offers') }}">Offers &amp; vouchers</a></li>
                <li><a href="{{ route('flash-sale') }}">Flash Sale</a></li>
                <li><a href="{{ route('stores.index') }}">All stores</a></li>
                <li><a href="{{ route('search', ['collection' => 'new']) }}">New arrivals</a></li>
            </ul>
        </div>
        <div>
            <h4>Customer service</h4>
            <ul>
                <li><a href="{{ $customer ? route('orders') : route('login') }}">Track my order</a></li>
                <li><a href="{{ route('home') }}#faq">Delivery &amp; returns</a></li>
                <li><a href="{{ route('contact') }}">Contact us</a></li>
                <li><a href="{{ $customer ? route('account') : route('login') }}">My account</a></li>
            </ul>
        </div>
        <div>
            <h4>For business</h4>
            <ul>
                <li><a href="{{ route('seller.register') }}">Sell on {{ $marketplace['name'] }}</a></li>
                <li><a href="{{ url('admin/login') }}">Seller Center login</a></li>
            </ul>
            <h4 style="margin-top: 16px">We accept</h4>
            <div class="pay-methods"><span>Cash on delivery</span><span>Visa</span><span>Mastercard</span><span>PayPal</span></div>
        </div>
    </div>
    <div class="site-footer__bottom">
        <span>&copy; {{ date('Y') }} {{ $marketplace['name'] }}. All rights reserved.</span>
        <span>Delivering in {{ $deliveryCities->take(3)->implode(', ') ?: 'your city' }}</span>
    </div>
</footer>
