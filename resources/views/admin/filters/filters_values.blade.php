@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Filters</h4>




                            <a href="{{ url('admin/filters') }}" style="max-width: 163px; float: right; display: inline-block"
                                class="btn btn-block btn-primary">View Filters</a>
                            <a href="{{ url('admin/add-edit-filter-value') }}"
                                style="max-width: 150px; float: left;  display: inline-block"
                                class="btn btn-block btn-primary">Add Filter Value</a>



                            @if (Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif


                            <div class="table-responsive pt-3">

                                <table id="filters" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Filter ID</th>
                                            <th>Filter Name</th>
                                            <th>Filter Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($filters_values as $filter)
                                            <tr>
                                                <td>{{ $filter['id'] }}</td>
                                                <td>{{ $filter['filter_id'] }}</td>
                                                <td>
                                                    @php
                                                        echo $getFilterName = \App\Models\ProductsFilter::getFilterName(
                                                            $filter['filter_id'],
                                                        );
                                                    @endphp
                                                </td>
                                                <td>{{ $filter['filter_value'] }}</td>
                                                <td>
                                                    @if ($filter['status'] == 1)
                                                        <a class="updateFilterValueStatus" id="filter-{{ $filter['id'] }}"
                                                            filter_id="{{ $filter['id'] }}" href="javascript:void(0)">

                                                            <i style="font-size: 25px" class="mdi mdi-bookmark-check"
                                                                status="Active"></i>
                                                        </a>
                                                    @else
                                                        <a class="updateFilterValueStatus" id="filter-{{ $filter['id'] }}"
                                                            filter_id="{{ $filter['id'] }}" href="javascript:void(0)">

                                                            <i style="font-size: 25px" class="mdi mdi-bookmark-outline"
                                                                status="Inactive"></i>
                                                        </a>
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
