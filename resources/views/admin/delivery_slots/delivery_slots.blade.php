@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-10 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Delivery slots</h4>
                            <p class="card-description">Customers pick one of the open slots at checkout. Today's slots close <a href="{{ url('admin/marketplace-settings') }}">a set number of minutes</a> before they start.</p>
                            <a href="{{ url('admin/add-edit-delivery-slot') }}" style="max-width: 160px; float: right; display: inline-block" class="btn btn-block btn-primary">Add slot</a>
                            @include('admin.layout.alerts')
                            <div class="table-responsive pt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr><th>Order</th><th>Label</th><th>From</th><th>To</th><th>Status</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($slots as $slot)
                                            <tr>
                                                <td>{{ $slot->sort_order }}</td>
                                                <td>{{ $slot->label }}</td>
                                                <td>{{ \Illuminate\Support\Str::substr($slot->starts_at, 0, 5) }}</td>
                                                <td>{{ \Illuminate\Support\Str::substr($slot->ends_at, 0, 5) }}</td>
                                                <td><span class="badge {{ $slot->status === 1 ? 'badge-success' : 'badge-secondary' }}">{{ $slot->status === 1 ? 'Open' : 'Closed' }}</span></td>
                                                <td>
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ url('admin/add-edit-delivery-slot/'.$slot->id) }}">Edit</a>
                                                    <form method="post" action="{{ url('admin/delivery-slots/'.$slot->id.'/status') }}" style="display: inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-light">{{ $slot->status === 1 ? 'Close' : 'Open' }}</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted">No delivery slots. Without slots, orders are placed without a delivery time.</td></tr>
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
