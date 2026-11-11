@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h3 class="font-weight-bold">Shipping Charges</h3>
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
                                @if (empty($shippingCharges['id'])) action="{{ url('admin/add-edit-shipping-charges/') }}" @else action="{{ url('admin/add-edit-shipping-charges/' . $shippingCharges['id']) }}" @endif
                                method="post">

                                @csrf

                                @php $getCountries = \App\Models\Country::all() @endphp
                                <div class="form-group">
                                    <label for="country">Select Country</label>
                                    <select name="country" id="country" class="form-control text-dark">
                                        <option value="">Select Country</option>
                                        @foreach ($getCountries as $country)
                                            <option value="{{ $country['country_name'] }}"
                                                @if (!empty($shippingCharges['country']) && $shippingCharges['country'] == $country['country_name']) selected @endif>
                                                {{ $country['country_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" name="city" id="city" class="form-control"
                                        @if (!empty($shippingCharges['city'])) value="{{ $shippingCharges['city'] }}" @else value="{{ old('city') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="between_0_500g">Rate (0-500g)</label>
                                    <input type="text" class="form-control" id="between_0_500g"
                                        placeholder="Enter Shipping Rate" name="between_0_500g"
                                        @if (!empty($shippingCharges['between_0_500g'])) value="{{ $shippingCharges['between_0_500g'] }}" @else value="{{ old('between_0_500g') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="between_501g_1000g">Rate (501g-1000g)</label>
                                    <input type="text" class="form-control" id="between_501g_1000g"
                                        placeholder="Enter Shipping Rate" name="between_501g_1000g"
                                        @if (!empty($shippingCharges['between_501g_1000g'])) value="{{ $shippingCharges['between_501g_1000g'] }}" @else value="{{ old('between_501g_1000g') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="between_1001_2000g">Rate (1001-2000g)</label>
                                    <input type="text" class="form-control" id="between_1001_2000g"
                                        placeholder="Enter Shipping Rate" name="between_1001_2000g"
                                        @if (!empty($shippingCharges['between_1001_2000g'])) value="{{ $shippingCharges['between_1001_2000g'] }}" @else value="{{ old('between_1001_2000g') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="between_2001g_5000g">Rate (2001g-5000g)</label>
                                    <input type="text" class="form-control" id="between_2001g_5000g"
                                        placeholder="Enter Shipping Rate" name="between_2001g_5000g"
                                        @if (!empty($shippingCharges['between_2001g_5000g'])) value="{{ $shippingCharges['between_2001g_5000g'] }}" @else value="{{ old('between_2001g_5000g') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="above_5000g">Rate (Above 5000g)</label>
                                    <input type="text" class="form-control" id="above_5000g"
                                        placeholder="Enter Shipping Rate" name="above_5000g"
                                        @if (!empty($shippingCharges['above_5000g'])) value="{{ $shippingCharges['above_5000g'] }}" @else value="{{ old('above_5000g') }}" @endif>
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
