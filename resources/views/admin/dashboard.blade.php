@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <h3 class="font-weight-bold">Welcome {{ Auth::guard('admin')->user()->name }}</h3>
                    <h6 class="font-weight-normal mb-0">Here's how the marketplace is doing.</h6>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Gross merchandise value', formatMoney($marketplace['gmv']), 'card-tale', $ordersCount.' orders · '.$marketplace['orders_today'].' today'],
                    ['Average order', formatMoney($marketplace['average_order']), 'card-dark-blue', 'Across all open orders'],
                    ['Commission earned', formatMoney($marketplace['commission']), 'card-light-blue', 'On vendor sales'],
                    ['Active stores', $marketplace['active_vendors'], 'card-light-danger', $marketplace['pending_vendors'].' waiting for approval'],
                ] as [$label, $value, $class, $hint])
                    <div class="col-md-3 mb-4 stretch-card transparent">
                        <div class="card {{ $class }}">
                            <div class="card-body">
                                <p class="mb-3">{{ $label }}</p>
                                <p class="fs-30 mb-2">{{ $value }}</p>
                                <p class="mb-0" style="opacity: .85">{{ $hint }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-md-5 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Needs your attention</h4>
                            <ul class="list-unstyled mb-0">
                                @foreach ([
                                    ['Vendors waiting for approval', $marketplace['pending_vendors'], $marketplace['pending_vendors'], url('admin/admins/vendor')],
                                    ['Payout requests', $marketplace['pending_payouts'], $marketplace['pending_payouts'].' · '.formatMoney($marketplace['pending_payout_amount']), url('admin/payouts')],
                                    ['Reviews to approve', $marketplace['pending_reviews'], $marketplace['pending_reviews'], url('admin/ratings')],
                                    ['Flash sale entries to review', $marketplace['pending_flash_items'], $marketplace['pending_flash_items'], url('admin/flash-sales')],
                                    ['Unanswered product questions', $marketplace['unanswered_questions'], $marketplace['unanswered_questions'], url('admin/questions')],
                                ] as [$label, $count, $display, $link])
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <a href="{{ $link }}">{{ $label }}</a>
                                        <span class="badge badge-pill {{ $count > 0 ? 'badge-warning' : 'badge-light' }}">{{ $display }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Top stores</h4>
                            <table class="table table-sm">
                                <thead><tr><th>Store</th><th>Units sold</th><th>Gross sales</th></tr></thead>
                                <tbody>
                                    @forelse ($topStores as $row)
                                        <tr>
                                            <td>{{ $storeNames[$row->vendor_id]?->store_name ?? 'Vendor #'.$row->vendor_id }}</td>
                                            <td>{{ number_format($row->units) }}</td>
                                            <td>{{ formatMoney($row->gross) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-muted">No vendor sales yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Recent orders</h4>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Order</th><th>Customer</th><th>Placed</th><th>Delivery</th><th>Payment</th><th>Status</th><th>Total</th></tr></thead>
                                    <tbody>
                                        @forelse ($recentOrders as $order)
                                            <tr>
                                                <td><a href="{{ url('admin/orders/'.$order->id) }}">#{{ $order->id }}</a></td>
                                                <td>{{ $order->name }}<br><small class="text-muted">{{ $order->city }}</small></td>
                                                <td>{{ $order->created_at?->format('j M, g:i A') }}</td>
                                                <td>{{ $order->delivery_date ? $order->delivery_date->format('j M').' · '.$order->delivery_slot : '—' }}</td>
                                                <td>{{ $order->payment_gateway }}</td>
                                                <td>{{ $order->order_status }}</td>
                                                <td>{{ formatMoney($order->grand_total) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-muted">No orders yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['Sections', $sectionsCount, 'admin/sections'],
                    ['Categories', $categoriesCount, 'admin/categories'],
                    ['Products', $productsCount, 'admin/products'],
                    ['Brands', $brandsCount, 'admin/brands'],
                    ['Coupons', $couponsCount, 'admin/coupons'],
                    ['Customers', $usersCount, 'admin/users'],
                ] as [$label, $count, $link])
                    <div class="col-md-2 col-6 mb-4">
                        <a href="{{ url($link) }}" class="card text-decoration-none">
                            <div class="card-body">
                                <p class="mb-2 text-muted">{{ $label }}</p>
                                <p class="fs-30 mb-0">{{ $count }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        <!-- content-wrapper ends -->
        @include('admin.layout.footer')
        <!-- partial -->
    </div>
@endsection
