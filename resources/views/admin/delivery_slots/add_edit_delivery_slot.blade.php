@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-6 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $title }}</h4>
                            @include('admin.layout.alerts')
                            <form class="forms-sample" method="post" action="{{ url('admin/add-edit-delivery-slot'.($slot->exists ? '/'.$slot->id : '')) }}">
                                @csrf
                                <div class="form-group">
                                    <label for="label">Label shown to customers</label>
                                    <input type="text" class="form-control" id="label" name="label" maxlength="60" required value="{{ old('label', $slot->label) }}" placeholder="e.g. 8 AM – 10 AM">
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="starts_at">From</label>
                                        <input type="time" class="form-control" id="starts_at" name="starts_at" required value="{{ old('starts_at', $slot->starts_at ? substr($slot->starts_at, 0, 5) : '') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="ends_at">To</label>
                                        <input type="time" class="form-control" id="ends_at" name="ends_at" required value="{{ old('ends_at', $slot->ends_at ? substr($slot->ends_at, 0, 5) : '') }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="sort_order">Display order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" max="1000" value="{{ old('sort_order', $slot->sort_order ?? 0) }}">
                                </div>
                                <div class="form-group">
                                    <label class="mb-0"><input type="checkbox" name="status" value="1" @checked(old('status', $slot->status ?? 1) == 1)> &nbsp;Open for orders</label>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">Save slot</button>
                                <a href="{{ url('admin/delivery-slots') }}" class="btn btn-light">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
