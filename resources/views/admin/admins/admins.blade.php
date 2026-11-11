@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $title }}</h4>

                            @if (request()->route('type') == 'admin' || request()->route('type') == '' || request()->route('type') == 'subadmin')
                                @can('manage', 'admin-add')
                                    <a href="{{ url('admin/add-edit-admin') }}"
                                        style="max-width: 150px; float: right; display: inline-block"
                                        class="btn btn-block btn-primary">Add New Admin</a>
                                @endcan
                            @endif

                            @if (Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif

                            <div class="table-responsive pt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Admin ID</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Mobile</th>
                                            <th>Email</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($admins as $admin)
                                            <tr>
                                                <td>{{ $admin['id'] }}</td>
                                                <td>{{ $admin['name'] }}</td>
                                                <td>{{ $admin['type'] }}</td>
                                                <td>{{ $admin['mobile'] }}</td>
                                                <td>{{ $admin['email'] }}</td>
                                                <td>
                                                    @if ($admin['image'] != '')
                                                        <img src="{{ asset('admin/images/photos/' . $admin['image']) }}">
                                                    @else
                                                        <img src="{{ asset('admin/images/photos/no-image.gif') }}">
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $condition_one =
                                                            Auth::guard('admin')->user()->type == 'superadmin' ||
                                                            (Auth::guard('admin')->user()->type == 'admin' &&
                                                                $admin['type'] != 'admin');
                                                    @endphp

                                                    @if ($admin['status'] == 1)
                                                        <a @if ($condition_one) class="updateAdminStatus" id="admin-{{ $admin['id'] }}"
                                                            admin_id="{{ $admin['id'] }}" @endif
                                                            href="javascript:void(0)">

                                                            <i style="font-size:25px;@if (!$condition_one) color:lightgray;cursor:no-drop; @endif"
                                                                class="mdi mdi-bookmark-check" status="Active"></i>
                                                        </a>
                                                    @else
                                                        <a @if ($condition_one) class="updateAdminStatus" id="admin-{{ $admin['id'] }}"
                                                            admin_id="{{ $admin['id'] }}" @endif
                                                            href="javascript:void(0)">

                                                            <i style="font-size: 25px;@if (!$condition_one) color:lightgray;cursor:no-drop; @endif"
                                                                class="mdi mdi-bookmark-outline" status="Inactive"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @can('manage', 'admin-edit')
                                                        <a title="Edit Admin"
                                                            href="{{ url('admin/add-edit-admin/' . $admin['id']) }}">
                                                            <i style="font-size: 25px" class="mdi mdi-pencil-box"></i>

                                                        </a>
                                                    @endcan

                                                    @if ($admin['type'] == 'vendor')
                                                        <a href="{{ url('admin/view-vendor-details/' . $admin['id']) }}">
                                                            <i style="font-size: 25px" class="mdi mdi-file-document"></i>
                                                        </a>
                                                        @can('manage', 'vendor-official')
                                                            @php $isOfficial = in_array((int) $admin['vendor_id'], $officialVendorIds ?? [], true); @endphp
                                                            <form method="post" action="{{ url('admin/update-vendor-official') }}" style="display: inline">
                                                                @csrf
                                                                <input type="hidden" name="vendor_id" value="{{ $admin['vendor_id'] }}">
                                                                <button type="submit" class="btn btn-sm {{ $isOfficial ? 'btn-primary' : 'btn-outline-primary' }}" title="{{ $isOfficial ? 'Remove the Official Store badge' : 'Give the Official Store badge' }}">{{ $isOfficial ? 'Official ✓' : 'Make official' }}</button>
                                                            </form>
                                                        @endcan
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:../../partials/_footer.html -->
        <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
                <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2024. All rights
                    reserved.</span>
            </div>
        </footer>
        <!-- partial -->
    </div>
@endsection
