@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <h3 class="font-weight-bold">{{ $vendor->store_name }}</h3>
                    <h6 class="font-weight-normal mb-0">
                        @if ($vendor->status !== 1)
                            Your store is waiting for approval. Complete your <a href="{{ url('admin/update-vendor-details/business') }}">business</a> and <a href="{{ url('admin/update-vendor-details/bank') }}">bank</a> details.
                        @elseif ($vendor->holiday_mode)
                            Holiday mode is on — shoppers can't order from you. <a href="{{ url('admin/store-profile') }}">Turn it off</a>.
                        @else
                            Your store is live at <a href="{{ $vendor->storeUrl() }}" target="_blank" rel="noopener">{{ url('store/'.$vendor->slug) }}</a>
                        @endif
                    </h6>
                </div>
            </div>

            <div class="row">
                @foreach ([
                    ['To ship', $lineCounts['to_ship'], 'card-tale', 'Items waiting for you', url('admin/orders')],
                    ['Gross sales', formatMoney($summary['gross']), 'card-dark-blue', $summary['orders'].' orders', url('admin/payouts')],
                    ['Available to withdraw', formatMoney($summary['available']), 'card-light-blue', formatMoney($summary['cleared']).' cleared', url('admin/payouts')],
                    ['Rating', $ratingAverage !== null ? $ratingAverage.' / 5' : '—', 'card-light-danger', $ratingTotal.' reviews · '.$vendor->followers_count.' followers', url('admin/ratings')],
                ] as [$label, $value, $class, $hint, $link])
                    <div class="col-md-3 mb-4 stretch-card transparent">
                        <a href="{{ $link }}" class="card {{ $class }} text-decoration-none" style="color: #fff">
                            <div class="card-body">
                                <p class="mb-3">{{ $label }}</p>
                                <p class="fs-30 mb-2">{{ $value }}</p>
                                <p class="mb-0" style="opacity: .85">{{ $hint }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <div class="col-md-7 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Latest order items</h4>
                            <table class="table table-sm">
                                <thead><tr><th>Order</th><th>Item</th><th>Qty</th><th>You earn</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($recentLines as $line)
                                        <tr>
                                            <td><a href="{{ url('admin/orders/'.$line->order_id) }}">#{{ $line->order_id }}</a><br><small class="text-muted">{{ $line->order?->created_at?->format('j M') }}</small></td>
                                            <td>{{ \Illuminate\Support\Str::limit($line->product_name, 36) }}</td>
                                            <td>{{ $line->product_qty }}</td>
                                            <td>{{ formatMoney($line->netAmount()) }}</td>
                                            <td>{{ $line->item_status ?: 'Pending' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">No orders yet. Products that sell well have clear photos, pack sizes, and fair prices.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Running low</h4>
                            <table class="table table-sm">
                                <tbody>
                                    @forelse ($lowStock as $variant)
                                        <tr>
                                            <td>{{ \Illuminate\Support\Str::limit($variant->product?->product_name, 30) }} <small class="text-muted">{{ $variant->size }}</small></td>
                                            <td class="text-right"><span class="badge {{ $variant->stock === 0 ? 'badge-danger' : 'badge-warning' }}">{{ $variant->stock }} left</span></td>
                                            <td class="text-right"><a href="{{ url('admin/add-edit-attributes/'.$variant->product_id) }}">Restock</a></td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted">Every product has more than 5 in stock.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <hr>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between py-1"><a href="{{ url('admin/products') }}">Products</a><span>{{ $productsCount }}</span></li>
                                <li class="d-flex justify-content-between py-1"><a href="{{ url('admin/orders') }}">Shipped, on the way</a><span>{{ $lineCounts['shipped'] }}</span></li>
                                <li class="d-flex justify-content-between py-1"><a href="{{ url('admin/orders') }}">Delivered items</a><span>{{ $lineCounts['delivered'] }}</span></li>
                                <li class="d-flex justify-content-between py-1"><a href="{{ url('admin/questions') }}">Questions to answer</a><span class="badge {{ $unansweredQuestions > 0 ? 'badge-warning' : 'badge-light' }}">{{ $unansweredQuestions }}</span></li>
                                <li class="d-flex justify-content-between py-1"><a href="{{ url('admin/flash-sales') }}">Join a flash sale</a><span>→</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
