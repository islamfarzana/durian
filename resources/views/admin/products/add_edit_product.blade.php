@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-12 grid-margin">
                    <div class="row">
                        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                            <h4 class="card-title">Products</h4>
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
                                @if (empty($product['id'])) action="{{ url('admin/add-edit-product') }}" @else action="{{ url('admin/add-edit-product/' . $product['id']) }}" @endif
                                method="post" enctype="multipart/form-data">
                                <!-- If the id is not passed in from the route, this measn 'Add a new Product', but if the id is passed in from the route, this means 'Edit the Product' -->

                                @csrf



                                <div class="form-group">
                                    <label for="category_id">Select Category</label>

                                    <select name="category_id" id="category_id" class="form-control text-dark">
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $section)
                                            <optgroup label="{{ $section['name'] }}">
                                                @foreach ($section['categories'] as $category)
                                                    <option value="{{ $category['id'] }}"
                                                        @if (!empty($product['category_id'] == $category['id'])) selected @endif>
                                                        {{ $category['category_name'] }}</option>
                                                    @foreach ($category['sub_categories'] as $subcategory)
                                                        <option value="{{ $subcategory['id'] }}"
                                                            @if (!empty($product['category_id'] == $subcategory['id'])) selected @endif>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--&nbsp;{{ $subcategory['category_name'] }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            </optgroup>
                                        @endforeach

                                    </select>
                                </div>




                                <div class="loadFilters">
                                    @include('admin.filters.category_filters')
                                </div>



                                <div class="form-group">
                                    <label for="brand_id">Select Brand</label>
                                    <select name="brand_id" id="brand_id" class="form-control text-dark">
                                        <option value="">Select Brand</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand['id'] }}"
                                                @if (!empty($product['brand_id'] == $brand['id'])) selected @endif>{{ $brand['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="product_name">Product Name</label>
                                    <input type="text" class="form-control" id="product_name"
                                        placeholder="Enter Product Name" name="product_name"
                                        @if (!empty($product['product_name'])) value="{{ $product['product_name'] }}" @else value="{{ old('product_name') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="product_code">Product Code</label>
                                    <input type="text" class="form-control" id="product_code" placeholder="Enter Code"
                                        name="product_code"
                                        @if (!empty($product['product_code'])) value="{{ $product['product_code'] }}" @else value="{{ old('product_code') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="product_color">Product Color</label>
                                    <input type="text" class="form-control" id="product_color"
                                        placeholder="Enter Product Color" name="product_color"
                                        @if (!empty($product['product_color'])) value="{{ $product['product_color'] }}" @else value="{{ old('product_color') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="product_price">Product Price</label>
                                    <input type="text" class="form-control" id="product_price"
                                        placeholder="Enter Product Price" name="product_price"
                                        @if (!empty($product['product_price'])) value="{{ $product['product_price'] }}" @else value="{{ old('product_price') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="product_discount">Product Discount (%)</label>
                                    <input type="text" class="form-control" id="product_discount"
                                        placeholder="Enter Product Discount" name="product_discount"
                                        @if (!empty($product['product_discount'])) value="{{ $product['product_discount'] }}" @else value="{{ old('product_discount') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="product_weight">Product Weight (grams, used for delivery fees)</label>
                                    <input type="text" class="form-control" id="product_weight"
                                        placeholder="Enter Product Weight" name="product_weight"
                                        @if (!empty($product['product_weight'])) value="{{ $product['product_weight'] }}" @else value="{{ old('product_weight') }}" @endif>

                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="unit">Pack size shown to shoppers</label>
                                        <input type="text" class="form-control" id="unit" name="unit" maxlength="50"
                                            placeholder="e.g. 1 kg, 500 gm, 12 pcs, 1 L" value="{{ old('unit', $product['unit'] ?? '') }}">
                                        <small class="text-muted">Leave empty when the product has several sizes or packs; shoppers pick them from the attributes.</small>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="country_of_origin">Country of origin</label>
                                        <input type="text" class="form-control" id="country_of_origin" name="country_of_origin" maxlength="80"
                                            value="{{ old('country_of_origin', $product['country_of_origin'] ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="return_days">Return window (days)</label>
                                        <input type="number" class="form-control" id="return_days" name="return_days" min="0" max="365"
                                            placeholder="0 = no change-of-mind returns" value="{{ old('return_days', $product['return_days'] ?? '') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="warranty">Warranty</label>
                                        <input type="text" class="form-control" id="warranty" name="warranty" maxlength="120"
                                            placeholder="e.g. 1 year brand warranty" value="{{ old('warranty', $product['warranty'] ?? '') }}">
                                    </div>
                                </div>




                                <div class="form-group">
                                    <label for="group_code">Group Code</label>
                                    <input type="text" class="form-control" id="group_code"
                                        placeholder="Enter Group Code" name="group_code"
                                        @if (!empty($product['group_code'])) value="{{ $product['group_code'] }}" @else value="{{ old('group_code') }}" @endif>

                                </div>



                                <div class="form-group">
                                    <label for="product_image">Product Image (Recommended Size: 1000x1000)</label>

                                    <input type="file" class="form-control" id="product_image" name="product_image">

                                    @if (!empty($product['product_image']))
                                        <a target="_blank"
                                            href="{{ url('front/images/product_images/large/' . $product['product_image']) }}">View
                                            Product Image</a>&nbsp;|&nbsp;
                                        <a href="JavaScript:void(0)" class="confirmDelete" module="product-image"
                                            moduleid="{{ $product['id'] }}">Delete Product Image</a>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="product_video">Product Video (Recommended Size: Less than 2 MB)</label>

                                    <input type="file" class="form-control" id="product_video" name="product_video">

                                    @if (!empty($product['product_video']))
                                        <a target="_blank"
                                            href="{{ url('front/videos/product_videos/' . $product['product_video']) }}">View
                                            Product Video</a>&nbsp;|&nbsp;
                                        <a href="JavaScript:void(0)" class="confirmDelete" module="product-video"
                                            moduleid="{{ $product['id'] }}">Delete Product Video</a>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label for="description">Product Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="3">{{ $product['description'] }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="meta_title">Meta Title</label>
                                    <input type="text" class="form-control" id="meta_title"
                                        placeholder="Enter Meta Title" name="meta_title"
                                        @if (!empty($product['meta_title'])) value="{{ $product['meta_title'] }}" @else value="{{ old('meta_title') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="meta_description">Meta Description</label>
                                    <input type="text" class="form-control" id="meta_description"
                                        placeholder="Enter Meta Description" name="meta_description"
                                        @if (!empty($product['meta_description'])) value="{{ $product['meta_description'] }}" @else value="{{ old('meta_description') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="meta_keywords">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords"
                                        placeholder="Enter Meta Keywords" name="meta_keywords"
                                        @if (!empty($product['meta_keywords'])) value="{{ $product['meta_keywords'] }}" @else value="{{ old('meta_keywords') }}" @endif>

                                </div>
                                <div class="form-group">
                                    <label for="is_featured">Featured Item (Yes/No)</label>
                                    <input type="checkbox" name="is_featured" id="is_featured" value="Yes"
                                        @if (!empty($product['is_featured']) && $product['is_featured'] == 'Yes') checked @endif>
                                </div>
                                <div class="form-group">
                                    <label for="is_bestseller">Best Seller Item (Yes/No)</label>
                                    <input type="checkbox" name="is_bestseller" id="is_bestseller" value="Yes"
                                        @if (!empty($product['is_bestseller']) && $product['is_bestseller'] == 'Yes') checked @endif>
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
