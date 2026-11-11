@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h3 class="font-weight-bold">Vendor Details</h3>
                            <h6 class="font-weight-normal mb-0"><a href="{{ url('admin/admins/vendor') }}">Back to Vendors</a>
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Personal Information</h4>
                            <div class="form-group">
                                <label>Email</label>
                                <input class="form-control" value="{{ $vendorDetails['vendor_personal']['email'] }}"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_name">Name</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['name'] }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_address">Address</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['address'] }}" readonly>

                            </div>
                            <div class="form-group">
                                <label for="vendor_city">City</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['city'] }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_state">State</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['state'] }}" readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_country">Country</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['country'] }}" readonly>

                            </div>
                            <div class="form-group">
                                <label for="vendor_pincode">Pincode</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['pincode'] }}" readonly>

                            </div>
                            <div class="form-group">
                                <label for="vendor_mobile">Mobile</label>
                                <input type="text" class="form-control"
                                    value="{{ $vendorDetails['vendor_personal']['mobile'] }}" readonly>
                            </div>
                            @if (!empty($vendorDetails['image']))
                                <div class="form-group">
                                    <label for="vendor_image">Vendor Photo</label>
                                    <br>
                                    <img style="width: 200px"
                                        src="{{ url('admin/images/photos/' . $vendorDetails['image']) }}">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Business Information</h4>
                            <div class="form-group">
                                <label for="vendor_name">Shop Name</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_name'])) value="{{ $vendorDetails['vendor_business']['shop_name'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_address">Shop Address</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_address'])) value="{{ $vendorDetails['vendor_business']['shop_address'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_city">Shop City</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_city'])) value="{{ $vendorDetails['vendor_business']['shop_city'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_state">Shop State</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_state'])) value="{{ $vendorDetails['vendor_business']['shop_state'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_country">Shop Country</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_country'])) value="{{ $vendorDetails['vendor_business']['shop_country'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_pincode">Shop Pincode</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_pincode'])) value="{{ $vendorDetails['vendor_business']['shop_pincode'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_mobile">Shop Mobile</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_mobile'])) value="{{ $vendorDetails['vendor_business']['shop_mobile'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_mobile">Shop Website</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_website'])) value="{{ $vendorDetails['vendor_business']['shop_website'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Shop Email</label>
                                <input class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['shop_email'])) value="{{ $vendorDetails['vendor_business']['shop_email'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Business License Number</label>
                                <input class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['business_license_number'])) value="{{ $vendorDetails['vendor_business']['business_license_number'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>GST Number</label>
                                <input class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['gst_number'])) value="{{ $vendorDetails['vendor_business']['gst_number'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>PAN Number</label>
                                <input class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['pan_number'])) value="{{ $vendorDetails['vendor_business']['pan_number'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Address Proof</label>
                                <input class="form-control"
                                    @if (isset($vendorDetails['vendor_business']['address_proof'])) value="{{ $vendorDetails['vendor_business']['address_proof'] }}" @endif
                                    readonly>
                            </div>
                            @if (!empty($vendorDetails['vendor_business']['address_proof_image']))
                                <div class="form-group">
                                    <label for="vendor_image">Address Proof Image</label>
                                    <br>
                                    <img style="width: 200px"
                                        src="{{ url('admin/images/proofs/' . $vendorDetails['vendor_business']['address_proof_image']) }}">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Bank Information</h4>
                            <div class="form-group">
                                <label for="vendor_name">Account Holder Name</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_bank']['account_holder_name'])) value="{{ $vendorDetails['vendor_bank']['account_holder_name'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_name">Bank Name</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_bank']['bank_name'])) value="{{ $vendorDetails['vendor_bank']['bank_name'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_address">Account Number</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_bank']['account_number'])) value="{{ $vendorDetails['vendor_bank']['account_number'] }}" @endif
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label for="vendor_city">Bank IFSC Code</label>
                                <input type="text" class="form-control"
                                    @if (isset($vendorDetails['vendor_bank']['bank_ifsc_code'])) value="{{ $vendorDetails['vendor_bank']['bank_ifsc_code'] }}" @endif
                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Commission Information</h4>



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

                            <div class="form-group">
                                <label for="vendor_name">Commission per order item (%)</label>
                                <form method="post" action="{{ url('admin/update-vendor-commission') }}">
                                    @csrf

                                    <input type="hidden" name="vendor_id"
                                        value="{{ $vendorDetails['vendor_personal']['id'] }}">
                                    <input class="form-control" type="text" name="commission"
                                        @if (isset($vendorDetails['vendor_personal']['commission'])) value="{{ $vendorDetails['vendor_personal']['commission'] }}" @endif
                                        required>
                                    <br>
                                    <button type="submit">Update</button>
                                </form>
                            </div>
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
