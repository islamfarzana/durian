@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h3 class="font-weight-bold">Admin User</h3>
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
                                @if (empty($admin['id'])) action="{{ url('admin/add-edit-admin') }}" @else action="{{ url('admin/add-edit-admin/' . $admin['id']) }}" @endif
                                method="post" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group">
                                    <label for="email">Admin Username/Email</label>
                                    <input type="email" class="form-control" id="email" placeholder="Enter Email"
                                        name="email"
                                        @if (!empty($admin['email'])) value="{{ $admin['email'] }}" @else value="{{ old('email') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="admin_type">Admin Type</label>
                                    <select name="admin_type" id="admin_type" class="form-control text-dark">
                                        <option value="">Select Admin Type</option>
                                        @if (Auth::guard('admin')->user()->type == 'superadmin')
                                            <option value="admin" @if (!empty($admin['type'] == 'admin')) selected @endif>Admin
                                            </option>
                                        @endif
                                        <option value="subadmin" @if (!empty($admin['type'] == 'subadmin')) selected @endif>Sub
                                            Admin
                                        </option>

                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="admin_name">Name</label>
                                    <input type="text" class="form-control" id="admin_name" placeholder="Enter Name"
                                        name="admin_name"
                                        @if (!empty($admin['name'])) value="{{ $admin['name'] }}" @else value="{{ old('name') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="admin_mobile">Mobile</label>
                                    <input type="text" class="form-control" id="admin_mobile"
                                        placeholder="Enter Mobile Number" name="admin_mobile" maxlength="11" minlength="10"
                                        @if (!empty($admin['mobile'])) value="{{ $admin['mobile'] }}" @else value="{{ old('mobile') }}" @endif>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" placeholder="Enter Password"
                                        name="password" @if (empty($admin['id'])) required @endif>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        placeholder="Confirm Password" name="confirm_password"
                                        @if (empty($admin['id'])) required @endif>
                                </div>
                                <div class="form-group">
                                    <label for="admin_image">Admin Photo</label>
                                    <input type="file" class="form-control" id="admin_image" name="admin_image">


                                    @if (!empty($admin['image']))
                                        <a target="_blank" href="{{ url('admin/images/photos/' . $admin['image']) }}">View
                                            Image</a>&nbsp;|&nbsp;
                                        <a href="JavaScript:void(0)" class="confirmDelete" module="admin-image"
                                            moduleid="{{ $admin['id'] }}">Delete Image</a>
                                    @endif
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
