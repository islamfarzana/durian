@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Flash Sales</h4>
                            <p class="card-description">
                                @if ($isVendor)
                                    Join a running or upcoming campaign with your products. Each entry goes live once an admin approves it.
                                @else
                                    Campaigns run between their start and end time ({{ $timezone }}). Vendors submit products; approve them on the campaign page.
                                @endif
                            </p>
                            @unless ($isVendor)
                                <a href="{{ url('admin/add-edit-flash-sale') }}" style="max-width: 180px; float: right; display: inline-block" class="btn btn-block btn-primary">Add Flash Sale</a>
                            @endunless

                            @include('admin.layout.alerts')

                            <div class="table-responsive pt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Campaign</th>
                                            <th>Starts</th>
                                            <th>Ends</th>
                                            <th>State</th>
                                            <th>{{ $isVendor ? 'Your products' : 'Products' }}</th>
                                            @unless ($isVendor)
                                                <th>Waiting for review</th>
                                            @endunless
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($flashSales as $flashSale)
                                            <tr>
                                                <td><strong>{{ $flashSale->title }}</strong></td>
                                                <td>{{ $flashSale->starts_at->timezone($timezone)->format('j M Y, g:i A') }}</td>
                                                <td>{{ $flashSale->ends_at->timezone($timezone)->format('j M Y, g:i A') }}</td>
                                                <td>
                                                    @if ($flashSale->status !== 1)
                                                        <span class="badge badge-secondary">Paused</span>
                                                    @elseif ($flashSale->isRunning())
                                                        <span class="badge badge-success">Running</span>
                                                    @elseif ($flashSale->starts_at->isFuture())
                                                        <span class="badge badge-info">Upcoming</span>
                                                    @else
                                                        <span class="badge badge-light">Ended</span>
                                                    @endif
                                                </td>
                                                <td>{{ $isVendor ? $flashSale->items_count : $flashSale->approved_items_count.' live' }}</td>
                                                @unless ($isVendor)
                                                    <td>
                                                        @if ($flashSale->pending_items_count > 0)
                                                            <span class="badge badge-warning">{{ $flashSale->pending_items_count }}</span>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                @endunless
                                                <td>
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ url('admin/flash-sales/'.$flashSale->id.'/items') }}">{{ $isVendor ? 'Join with products' : 'Manage products' }}</a>
                                                    @unless ($isVendor)
                                                        <a class="btn btn-sm btn-outline-secondary" href="{{ url('admin/add-edit-flash-sale/'.$flashSale->id) }}">Edit</a>
                                                    @endunless
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted">{{ $isVendor ? 'No campaigns are open right now.' : 'No flash sales yet.' }}</td></tr>
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
