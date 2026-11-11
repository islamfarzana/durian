@extends('admin.layout.layout')

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Storefront Themes</h4>
                            <p>Create a theme for a kind of shop, or install one from a zip. One theme is live for the marketplace. A vendor can still choose a different theme for their own shop page.</p>

                            <a href="{{ url('admin/themes/create') }}" style="max-width: 180px; float: right; display: inline-block" class="btn btn-block btn-primary">Create Theme</a>

                            @if (Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if (Session::has('error_message'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong class="mr-1">Error:</strong> {{ Session::get('error_message') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <form action="{{ url('admin/themes/upload') }}" method="post" enctype="multipart/form-data" class="mt-4 mb-4">
                                @csrf
                                <div class="form-row align-items-end">
                                    <div class="col-md-6">
                                        <label for="theme_zip">Upload theme zip</label>
                                        <input type="file" name="theme_zip" id="theme_zip" class="form-control" accept=".zip,application/zip" required>
                                        <small class="form-text text-muted">
                                            Zip root must contain <code>theme.json</code>. Optional files: <code>assets/theme.css</code> and <code>assets/preview.jpg</code>.
                                            <a href="{{ url('admin/themes/example-zip') }}">Download an example zip</a>.
                                        </small>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary">Install zip</button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive pt-3">
                                <table id="themes" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Theme</th>
                                            <th>Shop type</th>
                                            <th>Source</th>
                                            <th>Live</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($themes as $theme)
                                            <tr>
                                                <td>
                                                    <strong>{{ $theme->name }}</strong>
                                                    <div class="text-muted">{{ $theme->slug }}</div>
                                                </td>
                                                <td>{{ \App\Services\Themes\ThemeCatalog::niches()[$theme->niche] ?? $theme->niche }}</td>
                                                <td>{{ ucfirst($theme->source) }}</td>
                                                <td>{{ $theme->is_active ? 'Yes' : 'No' }}</td>
                                                <td>
                                                    <a href="{{ url('admin/themes/'.$theme->id.'/edit') }}">Edit</a>
                                                    @unless ($theme->is_active)
                                                        <form action="{{ url('admin/themes/'.$theme->id.'/activate') }}" method="post" style="display:inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                                        </form>
                                                    @endunless
                                                    @unless ($theme->is_locked || $theme->is_active)
                                                        <form action="{{ url('admin/themes/'.$theme->id.'/delete') }}" method="post" style="display:inline" onsubmit="return confirm('Delete this theme?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    @endunless
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
    </div>
@endsection
