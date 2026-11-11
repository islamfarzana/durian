@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            @include('admin.layout.alerts')
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Vendor balances</h4>
                            <p class="card-description">Earnings clear when an item is delivered. Available = cleared earnings − paid − requested.</p>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th>Gross sales</th>
                                            <th>Commission</th>
                                            <th>Net earnings</th>
                                            <th>Cleared</th>
                                            <th>Paid out</th>
                                            <th>Requested</th>
                                            <th>Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($balances as $balance)
                                            <tr>
                                                <td>{{ $balance['vendor']->store_name }}</td>
                                                <td>{{ formatMoney($balance['gross']) }}</td>
                                                <td>{{ formatMoney($balance['commission']) }}</td>
                                                <td>{{ formatMoney($balance['net']) }}</td>
                                                <td>{{ formatMoney($balance['cleared']) }}</td>
                                                <td>{{ formatMoney($balance['paid']) }}</td>
                                                <td>{{ formatMoney($balance['requested']) }}</td>
                                                <td><strong>{{ formatMoney($balance['available']) }}</strong></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted">No approved vendors yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Payout requests</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Store</th>
                                            <th>Amount</th>
                                            <th>Pay to</th>
                                            <th>Requested</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($payouts as $payout)
                                            <tr>
                                                <td>{{ $payout->id }}</td>
                                                <td>{{ $payout->vendor?->store_name }}</td>
                                                <td><strong>{{ formatMoney($payout->amount) }}</strong></td>
                                                <td><small>{{ $payout->account_details }}</small></td>
                                                <td>{{ $payout->created_at?->format('j M Y') }}</td>
                                                <td>
                                                    <span class="badge {{ $payout->status === 'paid' ? 'badge-success' : ($payout->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($payout->status) }}</span>
                                                    @if ($payout->reference)
                                                        <br><small>Ref {{ $payout->reference }}</small>
                                                    @endif
                                                    @if ($payout->note)
                                                        <br><small class="text-muted">{{ $payout->note }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($payout->status === 'requested')
                                                        <form method="post" action="{{ url('admin/payouts/'.$payout->id) }}" class="form-inline" style="gap: 6px">
                                                            @csrf
                                                            <input type="text" name="reference" class="form-control form-control-sm" placeholder="Transfer reference" maxlength="120">
                                                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Note (optional)" maxlength="500">
                                                            <button type="submit" name="action" value="paid" class="btn btn-sm btn-success">Mark paid</button>
                                                            <button type="submit" name="action" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                                        </form>
                                                    @else
                                                        <small class="text-muted">{{ $payout->processed_at?->format('j M Y') }}</small>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted">No payout requests yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
