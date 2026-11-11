<nav aria-label="Breadcrumb">
    <ol class="breadcrumbs">
        <li><a href="{{ route('home') }}">Home</a></li>
        @foreach ($breadcrumbs as $crumb)
            <li>
                @if (! empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
