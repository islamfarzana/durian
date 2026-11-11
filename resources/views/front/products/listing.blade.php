@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <div class="listing-head">
        <div>
            <h1>{{ $heading }}</h1>
            <p>
                {{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                @if (filled($intro))
                    · {{ \Illuminate\Support\Str::limit(strip_tags($intro), 160) }}
                @endif
            </p>
        </div>
    </div>

    @if ($subCategories->isNotEmpty())
        <div class="subcats" aria-label="Sub-categories">
            @foreach ($subCategories as $subCategory)
                <a class="category-tile" href="{{ url($subCategory->url) }}">
                    @if ($subCategory->imageUrl())
                        <img src="{{ $subCategory->imageUrl() }}" alt="" loading="lazy">
                    @else
                        <span class="category-tile__initial">{{ mb_substr($subCategory->category_name, 0, 1) }}</span>
                    @endif
                    {{ $subCategory->category_name }}
                </a>
            @endforeach
        </div>
    @endif

    @include('front.products.catalog', [
        'formAction' => url()->current(),
        'keep' => ['q' => $searchTerm ?? null, 'collection' => request('collection')],
    ])
@endsection
