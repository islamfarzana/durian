@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $flashSale->title }}</h4>
                            <p class="card-description">
                                {{ $flashSale->starts_at->timezone($timezone)->format('j M Y, g:i A') }} – {{ $flashSale->ends_at->timezone($timezone)->format('j M Y, g:i A') }} ({{ $timezone }}).
                                The flash discount replaces the product's normal discount while the sale runs and stock lasts.
                            </p>
                            @include('admin.layout.alerts')

                            @if ($flashSale->ends_at->isFuture())
                                <form class="form-inline mb-4" method="post" action="{{ url('admin/flash-sales/'.$flashSale->id.'/items') }}" style="gap: 10px">
                                    @csrf
                                    <select name="product_id" class="form-control text-dark" required style="min-width: 320px">
                                        <option value="">{{ $isVendor ? 'Choose one of your products' : 'Choose a product' }}</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->product_name }} ({{ $product->product_code }}) — {{ formatMoney($product->product_price) }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="discount_percent" class="form-control" min="1" max="90" step="0.5" placeholder="Discount %" required value="{{ old('discount_percent') }}" style="width: 130px">
                                    <input type="number" name="stock_limit" class="form-control" min="1" placeholder="Flash stock (optional)" value="{{ old('stock_limit') }}" style="width: 190px">
                                    <button type="submit" class="btn btn-primary">{{ $isVendor ? 'Submit for review' : 'Add product' }}</button>
                                </form>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Normal price</th>
                                            <th>Flash discount</th>
                                            <th>Flash stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $item)
                                            <tr>
                                                <td>{{ $item->product?->product_name }}<br><small class="text-muted">{{ $item->product?->product_code }}</small></td>
                                                <td>{{ formatMoney($item->product?->product_price) }}</td>
                                                <td>{{ rtrim(rtrim(number_format($item->discount_percent, 2), '0'), '.') }}%</td>
                                                <td>{{ $item->stock_limit ? $item->sold_count.' / '.$item->stock_limit.' sold' : 'Unlimited ('.$item->sold_count.' sold)' }}</td>
                                                <td>
                                                    <span class="badge {{ $item->status === 1 ? 'badge-success' : ($item->status === 2 ? 'badge-danger' : 'badge-warning') }}">{{ $item->statusLabel() }}</span>
                                                </td>
                                                <td>
                                                    @unless ($isVendor)
                                                        @if ($item->status !== 1)
                                                            <form method="post" action="{{ url('admin/flash-sale-items/'.$item->id.'/status') }}" style="display: inline">
                                                                @csrf
                                                                <input type="hidden" name="status" value="1">
                                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                            </form>
                                                        @endif
                                                        @if ($item->status !== 2)
                                                            <form method="post" action="{{ url('admin/flash-sale-items/'.$item->id.'/status') }}" style="display: inline">
                                                                @csrf
                                                                <input type="hidden" name="status" value="2">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                            </form>
                                                        @endif
                                                    @endunless
                                                    <form method="post" action="{{ url('admin/flash-sale-items/'.$item->id.'/delete') }}" style="display: inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-light">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted">No products in this flash sale yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <a href="{{ url('admin/flash-sales') }}" class="btn btn-light mt-3">Back to flash sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
