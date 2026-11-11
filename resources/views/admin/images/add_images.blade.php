@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">

                            <h4 class="card-title">Images</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Add Images</h4>



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






                            <form class="forms-sample" action="{{ url('admin/add-images/' . $product['id']) }}"
                                method="post" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group">
                                    <label for="product_name">Product Name:</label>
                                    &nbsp; {{ $product['product_name'] }}
                                </div>
                                <div class="form-group">
                                    <label for="product_code">Product Code:</label>
                                    &nbsp; {{ $product['product_code'] }}
                                </div>
                                <div class="form-group">
                                    <label for="product_color">Product Color:</label>
                                    &nbsp; {{ $product['product_color'] }}
                                </div>
                                <div class="form-group">
                                    <label for="product_price">Product Price:</label>
                                    &nbsp; {{ $product['product_price'] }}
                                </div>
                                <div class="form-group">

                                    @if (!empty($product['product_image']))
                                        <img style="width: 120px"
                                            src="{{ url('front/images/product_images/small/' . $product['product_image']) }}">
                                    @else
                                        <img style="width: 120px"
                                            src="{{ url('front/images/product_images/small/no-image.png') }}">
                                    @endif
                                </div>




                                <div class="form-group">
                                    <div class="field_wrapper">
                                        <input type="file" name="images[]" multiple id="images">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-info mr-2">Submit</button>
                                <button type="reset" class="btn btn-light">Cancel</button>
                            </form>

                            <br><br>

                            <h4 class="card-title">Product Images</h4>


                            <table id="products" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($product['images'] as $image)
                                        <tr>
                                            <td>{{ $image['id'] }}</td>
                                            <td>
                                                <img
                                                    src="{{ url('front/images/product_images/small/' . $image['image']) }}">



                                            </td>
                                            <td>
                                                @if ($image['status'] == 1)
                                                    <a class="updateImageStatus" id="image-{{ $image['id'] }}"
                                                        image_id="{{ $image['id'] }}" href="javascript:void(0)">

                                                        <i style="font-size: 25px" class="mdi mdi-bookmark-check"
                                                            status="Active"></i>
                                                    </a>
                                                @else
                                                    <a class="updateImageStatus" id="image-{{ $image['id'] }}"
                                                        image_id="{{ $image['id'] }}" href="javascript:void(0)">

                                                        <i style="font-size: 25px" class="mdi mdi-bookmark-outline"
                                                            status="Inactive"></i>
                                                    </a>
                                                @endif
                                                &nbsp;
                                                <a href="JavaScript:void(0)" class="confirmDelete" module="image"
                                                    moduleid="{{ $image['id'] }}">
                                                    <i style="font-size: 25px" class="mdi mdi-file-excel-box"></i>

                                                </a>
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
        <!-- content-wrapper ends -->
        @include('admin.layout.footer')
        <!-- partial -->
    </div>
@endsection
