@php
    $page = Session::get('page');
    $active = 'background: #188b87 !important; color: #FFF !important';
    $inactive = 'background: #fff !important; color: #188b87 !important';
    $adminType = Auth::guard('admin')->user()->type;
    $links = $menu === 'vendor'
        ? [
            ['store_profile', 'admin/store-profile', 'Store Profile'],
            ['flash_sales', 'admin/flash-sales', 'Flash Sales'],
            ['payouts', 'admin/payouts', 'Earnings & Payouts'],
            ['questions', 'admin/questions', 'Customer Questions'],
            ['ratings', 'admin/ratings', 'Reviews'],
        ]
        : array_values(array_filter([
            ['flash_sales', 'admin/flash-sales', 'Flash Sales'],
            in_array($adminType, ['superadmin', 'admin'], true) ? ['payouts', 'admin/payouts', 'Vendor Payouts'] : null,
            ['questions', 'admin/questions', 'Product Questions'],
            in_array($adminType, ['superadmin', 'admin'], true) ? ['delivery_slots', 'admin/delivery-slots', 'Delivery Slots'] : null,
            in_array($adminType, ['superadmin', 'admin'], true) ? ['marketplace_settings', 'admin/marketplace-settings', 'Marketplace Settings'] : null,
        ]));
    $isOpen = in_array($page, array_column($links, 0), true);
@endphp
<li class="nav-item">
    <a @if ($isOpen) style="{{ $active }}" @endif class="nav-link" data-toggle="collapse" href="#ui-marketplace-{{ $menu }}" aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="ui-marketplace-{{ $menu }}">
        <i class="icon-bag menu-icon"></i>
        <span class="menu-title">{{ $menu === 'vendor' ? 'Seller Center' : 'Marketplace' }}</span>
        <i class="menu-arrow"></i>
    </a>
    <div class="collapse {{ $isOpen ? 'show' : '' }}" id="ui-marketplace-{{ $menu }}">
        <ul class="nav flex-column sub-menu" style="{{ $inactive }}">
            @foreach ($links as [$key, $url, $label])
                <li class="nav-item"><a style="{{ $page === $key ? $active : $inactive }}" class="nav-link" href="{{ url($url) }}">{{ $label }}</a></li>
            @endforeach
        </ul>
    </div>
</li>
