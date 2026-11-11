@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">

                            <h4 class="card-title">Country</h4>
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
                                @if (empty($country['id'])) action="{{ url('admin/add-edit-country') }}" @else action="{{ url('admin/add-edit-country/' . $country['id']) }}" @endif
                                method="post" enctype="multipart/form-data">
                                <!-- If the id is not passed in from the route, this measn 'Add a new Country', but if the id is passed in from the route, this means 'Edit the Country' -->

                                @csrf

                                <div class="form-group">
                                    <label for="country_name">Country Name</label>
                                    <input type="text" class="form-control" id="country_name"
                                        placeholder="Enter Country Name" name="country_name"
                                        @if (!empty($country['country_name'])) value="{{ $country['country_name'] }}" @else value="{{ old('country_name') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="country_code">Country Code <span class="small text-danger">(Two Letters)</span></label>
                                    <input type="text" class="form-control" id="country_code"
                                        placeholder="Enter Country Code" name="country_code"
                                        @if (!empty($country['country_code'])) value="{{ $country['country_code'] }}" @else value="{{ old('country_code') }}" @endif>
                                </div>

                                <div class="form-group">
                                    <label for="language">Language <span class="small text-danger">(Three Letters)</span></label>
                                    <input type="text" class="form-control" id="language"
                                        placeholder="Enter Language Code" name="language"
                                        @if (!empty($country['language'])) value="{{ $country['language'] }}" @else value="{{ old('language') }}" @endif>
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
