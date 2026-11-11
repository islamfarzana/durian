@extends('admin.layout.layout')


@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Ratings</h4>





                            @if (Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        {{-- <span aria-hidden="true">&times;</span> --}}
                                    </button>
                                </div>
                            @endif


                            <div class="table-responsive pt-3">

                                <table id="ratings" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>User Email</th>
                                            <th>Review</th>
                                            <th>Rating</th>
                                            <th>Seller reply</th>
                                            <th>Status</th>
                                            @unless ($isVendor)
                                                <th>Actions</th>
                                            @endunless
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($ratings as $rating)
                                            <tr>
                                                <td>{{ $rating['id'] }}</td>
                                                <td>
                                                    <a target="_blank"
                                                        href="{{ url('product/' . $rating['product']['id']) }}">
                                                        {{ $rating['product']['product_name'] }}
                                                    </a>
                                                </td>
                                                <td>{{ $rating['user']['email'] }}</td>
                                                <td style="white-space: normal">{{ $rating['review'] }}</td>
                                                <td>{{ $rating['rating'] }}</td>
                                                <td style="white-space: normal; min-width: 240px">
                                                    <form method="post" action="{{ url('admin/ratings/'.$rating['id'].'/reply') }}">
                                                        @csrf
                                                        <textarea name="seller_reply" class="form-control mb-1" rows="2" maxlength="1000" required placeholder="Reply publicly">{{ $rating['seller_reply'] }}</textarea>
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ $rating['seller_reply'] ? 'Update reply' : 'Reply' }}</button>
                                                    </form>
                                                </td>
                                                <td>
                                                    @if ($isVendor)
                                                        {{ $rating['status'] == 1 ? 'Published' : 'Awaiting approval' }}
                                                    @elseif ($rating['status'] == 1)
                                                        <a class="updateRatingStatus" id="rating-{{ $rating['id'] }}"
                                                            rating_id="{{ $rating['id'] }}" href="javascript:void(0)">

                                                            <i style="font-size: 25px" class="mdi mdi-bookmark-check"
                                                                status="Active"></i>
                                                        </a>
                                                    @else
                                                        <a class="updateRatingStatus" id="rating-{{ $rating['id'] }}"
                                                            rating_id="{{ $rating['id'] }}" href="javascript:void(0)">

                                                            <i style="font-size: 25px" class="mdi mdi-bookmark-outline"
                                                                status="Inactive"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                                @unless ($isVendor)
                                                    <td>
                                                        <a href="JavaScript:void(0)" class="confirmDelete" module="rating"
                                                            moduleid="{{ $rating['id'] }}">
                                                            <i style="font-size: 25px" class="mdi mdi-file-excel-box"></i>
                                                        </a>
                                                    </td>
                                                @endunless
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
