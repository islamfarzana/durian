@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            @include('admin.layout.alerts')
            <div class="row">
                @foreach ([
                    ['Gross sales', $summary['gross'], 'card-tale', $summary['orders'].' orders'],
                    ['Marketplace commission', $summary['commission'], 'card-dark-blue', 'Rate: '.rtrim(rtrim(number_format($vendor->commission, 2), '0'), '.').'%'],
                    ['Cleared earnings', $summary['cleared'], 'card-light-blue', 'From delivered items'],
                    ['Available to withdraw', $summary['available'], 'card-light-danger', formatMoney($summary['paid']).' paid · '.formatMoney($summary['requested']).' requested'],
                ] as [$label, $value, $class, $hint])
                    <div class="col-md-3 mb-4 stretch-card transparent">
                        <div class="card {{ $class }}">
                            <div class="card-body">
                                <p class="mb-3">{{ $label }}</p>
                                <p class="fs-30 mb-2">{{ formatMoney($value) }}</p>
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
                            <h4 class="card-title">Request a payout</h4>
                            @if ($vendor->bankDetails === null || blank($vendor->bankDetails->account_number))
                                <p>Add your bank details first. <a href="{{ url('admin/update-vendor-details/bank') }}">Update bank details</a></p>
                            @else
                                <p class="card-description">Paid to {{ $vendor->bankDetails->bank_name }} · {{ $vendor->bankDetails->account_number }}. Minimum {{ formatMoney($minimumPayout) }}.</p>
                                <form method="post" action="{{ url('admin/payouts') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label for="amount">Amount</label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="{{ $minimumPayout }}" max="{{ $summary['available'] }}" value="{{ old('amount', $summary['available']) }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary" @disabled($summary['available'] < $minimumPayout)>Request payout</button>
                                    @if ($summary['available'] < $minimumPayout)
                                        <p class="text-muted mt-2 mb-0">You can request a payout once {{ formatMoney($minimumPayout) }} has cleared.</p>
                                    @endif
                                </form>
                            @endif

                            <h4 class="card-title mt-4">Payout history</h4>
                            <table class="table table-sm">
                                <tbody>
                                    @forelse ($payouts as $payout)
                                        <tr>
                                            <td>{{ $payout->created_at?->format('j M Y') }}</td>
                                            <td>{{ formatMoney($payout->amount) }}</td>
                                            <td>
                                                <span class="badge {{ $payout->status === 'paid' ? 'badge-success' : ($payout->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($payout->status) }}</span>
                                                @if ($payout->reference)
                                                    <small class="d-block">Ref {{ $payout->reference }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-muted">No payouts yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-7 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Earnings statement</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Order</th>
                                            <th>Item</th>
                                            <th>Sale</th>
                                            <th>Commission</th>
                                            <th>You earn</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($statement as $line)
                                            <tr>
                                                <td><a href="{{ url('admin/orders/'.$line->order_id) }}">#{{ $line->order_id }}</a><br><small class="text-muted">{{ $line->order?->created_at?->format('j M') }}</small></td>
                                                <td>{{ \Illuminate\Support\Str::limit($line->product_name, 34) }} × {{ $line->product_qty }}</td>
                                                <td>{{ formatMoney($line->lineTotal()) }}</td>
                                                <td>{{ formatMoney($line->commissionAmount()) }} <small class="text-muted">({{ rtrim(rtrim(number_format($line->commission, 2), '0'), '.') }}%)</small></td>
                                                <td><strong>{{ formatMoney($line->netAmount()) }}</strong></td>
                                                <td>{{ $line->item_status ?: $line->order?->order_status }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted">No sales yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">{{ $statement->links() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
