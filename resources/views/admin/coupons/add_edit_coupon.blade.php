@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h4 class="card-title">Coupons</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $title }}</h4>



                            @if (Session::has('error_message'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Error:</strong> {{ Session::get('error_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif


                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">


                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach

                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif





                            @if (Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif



                            <form class="forms-sample"
                                @if (empty($coupon['id'])) action="{{ url('admin/add-edit-coupon') }}" @else action="{{ url('admin/add-edit-coupon/' . $coupon['id']) }}" @endif
                                method="post" enctype="multipart/form-data">
                                @csrf

                                @if (empty($coupon['coupon_code']))
                                    <div class="form-group">
                                        <label for="coupon_option">Coupon Option:</label><br>
                                        <span><input type="radio" id="AutomaticCoupon" name="coupon_option"
                                                value="Automatic" checked>&nbsp;Automatic&nbsp;&nbsp;</span>
                                        <span><input type="radio" id="ManualCoupon" name="coupon_option"
                                                value="Manual">&nbsp;Manual&nbsp;&nbsp;</span>
                                    </div>
                                    <div class="form-group" style="display: none" id="couponField">
                                        <label for="coupon_code">Coupon Code:</label>
                                        <input type="text" class="form-control" placeholder="Enter Coupon Code"
                                            name="coupon_code">
                                    </div>
                                @else
                                    <input type="hidden" name="coupon_option" value="{{ $coupon['coupon_option'] }}">
                                    <input type="hidden" name="coupon_code" value="{{ $coupon['coupon_code'] }}">

                                    <div class="form-group">
                                        <label for="coupon_code">Coupon Code:</label>
                                        <span style="color: green; font-weight: bold">{{ $coupon['coupon_code'] }}</span>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label for="coupon_type">Coupon Type:</label><br>
                                    <span><input type="radio" name="coupon_type" value="Multiple Times"
                                            @if (isset($coupon['coupon_type']) && $coupon['coupon_type'] == 'Multiple Times') checked @endif>&nbsp;Multiple
                                        Times&nbsp;&nbsp;</span>
                                    <span><input type="radio" name="coupon_type" value="Single Time"
                                            @if (isset($coupon['coupon_type']) && $coupon['coupon_type'] == 'Single Time') checked @endif>&nbsp;Single
                                        Time&nbsp;&nbsp;</span>
                                </div>
                                <div class="form-group">
                                    <label for="amount_type">Amount Type:</label><br>
                                    <span><input type="radio" name="amount_type" value="Percentage"
                                            @if (isset($coupon['amount_type']) && $coupon['amount_type'] == 'Percentage') checked @endif>&nbsp;Percentage&nbsp;(in
                                        %)&nbsp;</span>
                                    <span><input type="radio" name="amount_type" value="Fixed"
                                            @if (isset($coupon['amount_type']) && $coupon['amount_type'] == 'Fixed') checked @endif>&nbsp;Fixed&nbsp;(in BDT or
                                        USD)</span>
                                </div>
                                <div class="form-group">
                                    <label for="amount">Amount:</label>
                                    <input type="text" class="form-control" id="amount"
                                        placeholder="Enter Coupon Amount" name="amount"
                                        @if (isset($coupon['amount'])) value="{{ $coupon['amount'] }}" @else value="{{ old('amount') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="categories">Select Category:</label>
                                    <select name="categories[]" class="form-control text-dark" multiple>

                                        @foreach ($categories as $section)
                                            <optgroup label="{{ $section['name'] }}">
                                                @foreach ($section['categories'] as $category)
                                                    <option value="{{ $category['id'] }}"
                                                        @if (in_array($category['id'], $selCats)) selected @endif>
                                                        &nbsp;&nbsp;&nbsp;--&nbsp;{{ $category['category_name'] }}</option>

                                                    @foreach ($category['sub_categories'] as $subcategory)
                                                        <option value="{{ $subcategory['id'] }}"
                                                            @if (in_array($subcategory['id'], $selCats)) selected @endif>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--&nbsp;{{ $subcategory['category_name'] }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="brands">Select Brand:</label>
                                    <select name="brands[]" class="form-control text-dark" multiple>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand['id'] }}"
                                                @if (in_array($brand['id'], $selBrands)) selected @endif>{{ $brand['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="users">Select User (by email):</label>
                                    <select name="users[]" class="form-control text-dark" multiple>
                                        @foreach ($users as $user)
                                            <option value="{{ $user['email'] }}"
                                                @if (in_array($user['email'], $selUsers)) selected @endif>{{ $user['email'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <p class="text-muted small mb-3">Leave category, brand, and user empty to let the voucher apply to everything{{ Auth::guard('admin')->user()->type === 'vendor' ? ' in your store' : '' }}.</p>

                                <div class="form-group">
                                    <label for="title">Voucher title (shown to shoppers):</label>
                                    <input type="text" class="form-control" id="title" name="title" maxlength="120"
                                        placeholder="e.g. Weekend grocery saver" value="{{ old('title', $coupon->title) }}">
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="min_spend">Minimum spend:</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="min_spend" name="min_spend"
                                            value="{{ old('min_spend', $coupon->min_spend ?? 0) }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="max_discount">Maximum discount (percentage vouchers):</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="max_discount" name="max_discount"
                                            placeholder="No cap" value="{{ old('max_discount', $coupon->max_discount) }}">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="usage_limit">Total redemptions allowed:</label>
                                        <input type="number" min="1" class="form-control" id="usage_limit" name="usage_limit"
                                            placeholder="Unlimited" value="{{ old('usage_limit', $coupon->usage_limit) }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="starts_at">Start Date:</label>
                                        <input type="date" class="form-control" id="starts_at" name="starts_at"
                                            value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="expiry_date">Expiry Date:</label>
                                        <input type="date" class="form-control" id="expiry_date"
                                            placeholder="Enter Expiry Date" name="expiry_date"
                                            value="{{ old('expiry_date', $coupon->expiry_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="mb-0">
                                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $coupon->is_public))>
                                        &nbsp;List this voucher publicly on the Offers page{{ Auth::guard('admin')->user()->type === 'vendor' ? ' and your store page' : '' }}
                                    </label>
                                </div>


                                <button type="submit" class="btn btn-info mr-2">Submit</button>
                                <button type="reset" class="btn btn-light">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- content-wrapper ends -->
        @include('admin.layout.footer')
        <!-- partial -->
    </div>
@endsection
