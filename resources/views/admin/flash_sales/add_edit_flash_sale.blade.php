@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-8 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $title }}</h4>
                            <p class="card-description">Times are in {{ $timezone }}.</p>
                            @include('admin.layout.alerts')
                            <form class="forms-sample" method="post" action="{{ url('admin/add-edit-flash-sale'.($flashSale->exists ? '/'.$flashSale->id : '')) }}">
                                @csrf
                                <div class="form-group">
                                    <label for="title">Campaign title</label>
                                    <input type="text" class="form-control" id="title" name="title" maxlength="120" required value="{{ old('title', $flashSale->title) }}" placeholder="e.g. 11.11 Mega Flash Sale">
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="starts_at">Starts</label>
                                        <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" required value="{{ old('starts_at', $flashSale->starts_at?->timezone($timezone)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="ends_at">Ends</label>
                                        <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" required value="{{ old('ends_at', $flashSale->ends_at?->timezone($timezone)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="mb-0"><input type="checkbox" name="status" value="1" @checked(old('status', $flashSale->status ?? 1) == 1)> &nbsp;Campaign is active</label>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">Save and add products</button>
                                <a href="{{ url('admin/flash-sales') }}" class="btn btn-light">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.layout.footer')
    </div>
@endsection
