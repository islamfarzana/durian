@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-8 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Store profile</h4>
                            <p class="card-description">
                                This is your public store page{{ $vendor->status === 1 ? '' : ' (visible once your account is approved)' }}:
                                <a href="{{ $vendor->status === 1 ? $vendor->storeUrl() : '#' }}" target="_blank" rel="noopener">{{ url('store/'.$vendor->slug) }}</a>
                                · {{ number_format($vendor->followers_count) }} followers
                            </p>
                            @include('admin.layout.alerts')
                            <form class="forms-sample" method="post" action="{{ url('admin/store-profile') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Store name</label>
                                    <p class="mb-0"><strong>{{ $vendor->store_name }}</strong> <small class="text-muted">— change it in <a href="{{ url('admin/update-vendor-details/business') }}">Business details</a></small></p>
                                </div>
                                <div class="form-group">
                                    <label for="slug">Store address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">{{ url('store') }}/</span></div>
                                        <input type="text" class="form-control" id="slug" name="slug" maxlength="80" required pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug', $vendor->slug) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="tagline">Tagline</label>
                                    <input type="text" class="form-control" id="tagline" name="tagline" maxlength="160" value="{{ old('tagline', $vendor->tagline) }}" placeholder="One line shoppers see under your store name">
                                </div>
                                <div class="form-group">
                                    <label for="about">About the store</label>
                                    <textarea class="form-control" id="about" name="about" rows="5" maxlength="2000">{{ old('about', $vendor->about) }}</textarea>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="logo">Logo (square, JPG/PNG/WebP, up to 2 MB)</label>
                                        @if ($vendor->logoUrl())
                                            <img src="{{ $vendor->logoUrl() }}" alt="" style="display: block; width: 72px; height: 72px; border-radius: 50%; object-fit: cover; margin-bottom: 8px">
                                        @endif
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="banner">Banner (wide, JPG/PNG/WebP, up to 4 MB)</label>
                                        @if ($vendor->bannerUrl())
                                            <img src="{{ $vendor->bannerUrl() }}" alt="" style="display: block; width: 100%; height: 72px; object-fit: cover; border-radius: 6px; margin-bottom: 8px">
                                        @endif
                                        <input type="file" class="form-control" id="banner" name="banner" accept="image/jpeg,image/png,image/webp">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="mb-0"><input type="checkbox" name="holiday_mode" value="1" @checked(old('holiday_mode', $vendor->holiday_mode))> &nbsp;Holiday mode — hide my products and pause new orders</label>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">Save store profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Badges</h4>
                            @if ($vendor->is_official)
                                <p><span class="badge badge-primary">Official Store</span></p>
                                <p class="text-muted">Your store shows the Official Store badge on products and in search.</p>
                            @else
                                <p><span class="badge badge-success">Verified seller</span></p>
                                <p class="text-muted">Brand owners and authorised distributors can ask the marketplace team for the Official Store badge.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
