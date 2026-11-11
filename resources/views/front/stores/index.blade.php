@extends('front.layout.layout')

@section('content')
    @include('front.layout.breadcrumbs', ['breadcrumbs' => [['label' => 'Stores', 'url' => null]]])

    <div class="listing-head">
        <div>
            <h1>Shop by store</h1>
            <p>Official brand stores and verified sellers — follow your favourites to see their deals first.</p>
        </div>
    </div>

    <form method="get" action="{{ route('stores.index') }}" class="toolbar">
        <div class="search-box" style="flex: 1; max-width: 420px; height: 40px">
            <input type="search" name="q" value="{{ $term }}" placeholder="Search stores" aria-label="Search stores">
            <button type="submit" aria-label="Search stores" style="width: 32px; height: 32px"><x-shop.icon name="search" class="icon--sm" /></button>
        </div>
        <label class="chip"><input type="checkbox" name="official" value="1" data-autosubmit @checked(request()->boolean('official'))><x-shop.icon name="verified" class="icon--sm" />Official stores only</label>
    </form>

    @if ($stores->isEmpty())
        <div class="empty-state">
            <x-shop.icon name="store" />
            <h2>No stores found</h2>
            <p>Try another name, or <a class="link" href="{{ route('seller.register') }}">open your own store</a>.</p>
        </div>
    @else
        <div class="store-grid">
            @foreach ($stores as $vendor)
                <x-shop.store-card :vendor="$vendor" />
            @endforeach
        </div>
        <div class="pagination-wrap">{{ $stores->links() }}</div>
    @endif
@endsection
