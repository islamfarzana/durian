@extends('admin.layout.layout')

@php
    $catalog = \App\Services\Themes\ThemeCatalog::class;
@endphp

@section('content')
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="row">
                <div class="col-md-10 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $title }}</h4>
                            <a href="{{ url('admin/themes') }}">Back to themes</a>

                            @if ($errors->any())
                                <div class="alert alert-danger mt-3">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <form class="forms-sample mt-3" method="post" enctype="multipart/form-data"
                                action="{{ $theme->exists ? url('admin/themes/'.$theme->id) : url('admin/themes') }}">
                                @csrf
                                <div class="form-group">
                                    <label for="name">Theme name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $theme->name) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="slug">Slug</label>
                                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $theme->slug) }}" @if ($theme->is_locked) readonly @endif required>
                                </div>
                                <div class="form-group">
                                    <label for="niche">Shop type</label>
                                    <select class="form-control" id="niche" name="niche" required>
                                        @foreach ($catalog::niches() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('niche', $theme->niche) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $theme->description) }}</textarea>
                                </div>
                                <div class="form-row">
                                    @foreach (['primary' => 'Primary', 'secondary' => 'Secondary', 'accent' => 'Accent', 'text' => 'Text', 'surface' => 'Surface'] as $field => $label)
                                        <div class="form-group col-md-4">
                                            <label for="{{ $field }}">{{ $label }}</label>
                                            <input type="color" class="form-control" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $theme->setting($field)) }}">
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="font_heading">Heading font</label>
                                        <select class="form-control" id="font_heading" name="font_heading">
                                            @foreach ($catalog::fonts() as $font)
                                                <option value="{{ $font }}" @selected(old('font_heading', $theme->setting('font_heading')) === $font)>{{ $font }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="font_body">Body font</label>
                                        <select class="form-control" id="font_body" name="font_body">
                                            @foreach ($catalog::fonts() as $font)
                                                <option value="{{ $font }}" @selected(old('font_body', $theme->setting('font_body')) === $font)>{{ $font }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="header_style">Header</label>
                                        <select class="form-control" id="header_style" name="header_style">
                                            @foreach ($catalog::headerStyles() as $value => $label)
                                                <option value="{{ $value }}" @selected(old('header_style', $theme->setting('header_style')) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="product_card">Product cards</label>
                                        <select class="form-control" id="product_card" name="product_card">
                                            @foreach ($catalog::productCards() as $value => $label)
                                                <option value="{{ $value }}" @selected(old('product_card', $theme->setting('product_card')) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="announcement">Announcement bar</label>
                                    <input type="text" class="form-control" id="announcement" name="announcement" maxlength="160" value="{{ old('announcement', $theme->setting('announcement')) }}">
                                </div>
                                <div class="form-group">
                                    <label for="custom_css">Custom CSS</label>
                                    <textarea class="form-control" id="custom_css" name="custom_css" rows="6" placeholder=".product-title { letter-spacing: 0.04em; }">{{ old('custom_css', $theme->custom_css) }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label for="preview_image">Preview image</label>
                                    <input type="file" class="form-control" id="preview_image" name="preview_image" accept="image/png,image/jpeg,image/webp">
                                    @if ($theme->previewUrl())
                                        <img src="{{ $theme->previewUrl() }}" alt="" class="mt-2" style="max-width: 180px">
                                    @endif
                                </div>
                                <div class="form-check mb-3">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" name="activate" value="1" @checked(old('activate'))>
                                        Make this the live storefront theme
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">Save theme</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
