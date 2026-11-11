@extends('front.layout.layout')

@section('body_class', 'has-buybar')

@section('content')
    @php
        $images = collect([$product->product_image])->merge($product->images->pluck('image'))->filter()->unique()->values();
        $ratingAverage = round((float) $product->rating_avg, 1);
        $ratingCount = (int) $product->rating_count;
        $answeredCount = $questions->filter(fn ($question) => $question->isAnswered())->count();
        $flashSale = $price->flashSale;
        $purchasable = $product->isPurchasable();
        $vendor = $store['vendor'] ?? null;
        $genericVariant = $variants->count() === 1 && in_array(mb_strtolower(trim((string) $variants->first()->size)), ['normal', 'standard', 'default', 'free size', 'one size'], true);
        $stockLeft = (int) ($selectedVariant?->stock ?? 0);
        $specs = array_filter([
            'Brand' => $product->brand?->name,
            'Product code' => $product->product_code,
            'Pack size' => $product->unit,
            'Colour' => $product->product_color,
            'Weight' => $product->product_weight ? number_format($product->product_weight).' g' : null,
            'Country of origin' => $product->country_of_origin,
            'Fabric' => $product->fabric,
            'Pattern' => $product->pattern,
            'Sleeve' => $product->sleeve,
            'Fit' => $product->fit,
            'Occasion' => $product->occasion,
            'RAM' => $product->ram,
            'Screen size' => $product->screen_size,
            'Operating system' => $product->operating_system,
            'Warranty' => $product->warranty,
            'Returns' => $product->return_days ? $product->return_days.' days' : null,
        ], fn ($value) => filled($value));
    @endphp

    @include('front.layout.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

    <div class="pdp">
        <div class="gallery" data-gallery>
            <div class="gallery__main">
                <img src="{{ \App\Models\Product::imageUrlFor($images->first(), 'large') }}" alt="{{ $product->product_name }}" data-gallery-main>
                <div class="product-card__badges">
                    @if ($price->discountPercent() > 0)
                        <span class="badge badge--deal">-{{ $price->discountPercent() }}%</span>
                    @endif
                </div>
            </div>
            @if ($images->count() > 1)
                <div class="gallery__thumbs">
                    @foreach ($images as $image)
                        <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-gallery-thumb="{{ \App\Models\Product::imageUrlFor($image, 'large') }}" aria-label="Show image {{ $loop->iteration }}">
                            <img src="{{ \App\Models\Product::imageUrlFor($image, 'small') }}" alt="">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pdp__info" data-buy-box data-product="{{ $product->id }}">
            @if ($flashSale)
                <div class="flash-bar">
                    <span class="flash-bar__title"><x-shop.icon name="bolt" class="icon--fill" />{{ $flashSale->flashSale->title }}</span>
                    <span class="countdown" data-countdown="{{ $flashSale->flashSale->ends_at->toIso8601String() }}">
                        <span data-countdown-label>Ends in</span>
                        <span class="countdown__digits"><span data-unit="h">00</span>:<span data-unit="m">00</span>:<span data-unit="s">00</span></span>
                    </span>
                    @if ($flashSale->stock_limit)
                        <div class="flash-bar__sold">
                            {{ $flashSale->sold_count }} of {{ $flashSale->stock_limit }} claimed
                            <div class="progress"><span style="width: {{ $flashSale->soldPercent() }}%"></span></div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="cluster">
                @if ($vendor === null || $vendor->is_official)
                    <span class="badge badge--official"><x-shop.icon name="verified" class="icon--sm" />{{ $vendor ? 'Official Store' : 'Sold by '.$marketplace['name'] }}</span>
                @endif
                @if ($product->is_bestseller === 'Yes')
                    <span class="badge badge--warning">Best seller</span>
                @endif
                @if ($product->category)
                    <a class="badge badge--muted" href="{{ url($product->category->url) }}">{{ $product->category->category_name }}</a>
                @endif
            </div>

            <h1 class="pdp__title">{{ $product->product_name }}</h1>
            <div class="pdp__summary">
                @if ($ratingCount > 0)
                    <a href="#reviews"><x-shop.stars :rating="$ratingAverage" :count="$ratingCount" /></a>
                @else
                    <span>No ratings yet</span>
                @endif
                <a href="#questions">{{ $answeredCount }} answered {{ \Illuminate\Support\Str::plural('question', $answeredCount) }}</a>
                @if ($product->sold_count > 0)
                    <span>{{ number_format($product->sold_count) }} sold</span>
                @endif
                @if ($product->brand)
                    <span>Brand: <a href="{{ route('search', ['q' => $product->brand->name]) }}">{{ $product->brand->name }}</a></span>
                @endif
            </div>

            <div class="pdp__price">
                <div class="price{{ $price->hasDiscount() ? ' price--deal' : '' }}">
                    <span class="price__now" data-price-final>{{ formatMoney($price->final) }}</span>
                    <s class="price__was" data-price-original @if (! $price->hasDiscount()) hidden @endif>{{ formatMoney($price->original) }}</s>
                    <span class="price__off" data-price-discount @if (! $price->hasDiscount()) hidden @endif>-{{ $price->discountPercent() }}%</span>
                </div>
                @if ($product->packLabel())
                    <div class="muted small">{{ $product->packLabel() }}</div>
                @endif
            </div>

            <input type="hidden" data-selected-size value="{{ $selectedVariant?->size }}">
            @if ($variants->isNotEmpty() && ! $genericVariant)
                <div class="variant-group">
                    <div class="variant-group__label">Option: <span data-selected-label>{{ $selectedVariant?->size }}</span></div>
                    <div class="variant-chips">
                        @foreach ($variants as $variant)
                            @php $info = $variantPrices[$variant->size]; @endphp
                            <button type="button" class="variant-chip {{ $selectedVariant?->is($variant) ? 'is-active' : '' }}" data-variant-chip
                                data-size="{{ $variant->size }}" data-final="{{ $info['final'] }}" data-original="{{ $info['original'] }}"
                                data-discount="{{ $info['discount'] }}" data-stock="{{ $info['stock'] }}" data-in-bag="{{ $info['inBag'] }}"
                                aria-pressed="{{ $selectedVariant?->is($variant) ? 'true' : 'false' }}" @disabled($info['stock'] < 1)>
                                {{ $variant->size }}<small>{{ $info['final'] }}</small>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($purchasable)
                <div class="stock-note {{ $stockLeft <= 5 ? 'stock-note--low' : 'stock-note--in' }}" data-stock-note>
                    {{ $stockLeft <= 5 ? 'Hurry, only '.$stockLeft.' left' : 'In stock' }}
                </div>
                <div class="buy-row">
                    <div class="qty-picker">
                        <button type="button" data-quantity-step="-1" aria-label="Decrease quantity"><x-shop.icon name="minus" /></button>
                        <input type="number" min="1" max="{{ max(1, $stockLeft) }}" value="1" data-quantity aria-label="Quantity">
                        <button type="button" data-quantity-step="1" aria-label="Increase quantity"><x-shop.icon name="plus" /></button>
                    </div>
                    <button type="button" class="btn btn--primary btn--lg" data-buy-action="add"><x-shop.icon name="bag" />Add to bag</button>
                    <button type="button" class="btn btn--accent btn--lg" data-buy-action="buy">Buy now</button>
                </div>
                <p class="small muted" data-in-bag>{{ $bagQuantity > 0 ? $bagQuantity.' in your bag' : '' }}</p>
            @else
                <div class="alert alert--info">
                    <x-shop.icon name="info" />
                    @if ($vendor && $vendor->holiday_mode)
                        {{ $vendor->store_name }} is on a break and not taking orders right now. Follow the store to know when it's back.
                    @else
                        This product is out of stock right now. Save it to your favourites and check back soon.
                    @endif
                </div>
            @endif

            <div class="cluster">
                <button type="button" class="btn btn--ghost btn--sm wish-inline" data-wishlist="{{ route('wishlist.toggle', $product->id) }}" aria-pressed="{{ in_array((int) $product->id, $wishlistIds ?? [], true) ? 'true' : 'false' }}"><x-shop.icon name="heart" />Save</button>
                <button type="button" class="btn btn--ghost btn--sm" data-copy="{{ url()->current() }}" data-copy-message="Link copied — share it with anyone."><x-shop.icon name="share" />Share</button>
            </div>

            <ul class="fact-list">
                @if ($product->packLabel())
                    <li><x-shop.icon name="package" />{{ $product->packLabel() }}</li>
                @endif
                @if ($product->country_of_origin)
                    <li><x-shop.icon name="globe" />Product of {{ $product->country_of_origin }}</li>
                @endif
                <li><x-shop.icon name="return" />{{ $product->return_days ? $product->return_days.'-day returns' : 'No change-of-mind returns' }}</li>
                @if ($codEnabled)
                    <li><x-shop.icon name="cash" />Cash on delivery</li>
                @endif
            </ul>
        </div>

        <aside class="pdp__aside">
            <div class="side-card">
                <div class="side-card__title">Delivery</div>
                <div class="side-row">
                    <x-shop.icon name="pin" />
                    <div>{{ $deliveryCity }}<small>Delivery area</small></div>
                    <button type="button" class="link side-row__end" style="border: 0; background: none" data-dialog-open="#location-dialog">Change</button>
                </div>
                <div class="side-row">
                    <x-shop.icon name="truck" />
                    <div>Scheduled delivery<small>Pick a time slot at checkout</small></div>
                    <span class="side-row__end">{{ $deliveryFee > 0 ? formatMoney($deliveryFee) : 'Free' }}</span>
                </div>
                @if ($freeDeliveryThreshold > 0)
                    <div class="side-row">
                        <x-shop.icon name="gift" />
                        <div>Free delivery over {{ formatMoney($freeDeliveryThreshold) }}<small>Everything in one bag counts, from every store</small></div>
                    </div>
                @endif
                @if ($codEnabled)
                    <div class="side-row"><x-shop.icon name="cash" /><div>Cash on delivery available</div></div>
                @endif
            </div>

            <div class="side-card">
                <div class="side-card__title">Returns &amp; warranty</div>
                <div class="side-row"><x-shop.icon name="return" /><div>{{ $product->return_days ? $product->return_days.'-day easy return' : 'Change of mind is not applicable' }}</div></div>
                <div class="side-row"><x-shop.icon name="shield" /><div>{{ $product->warranty ?: 'Warranty not available' }}</div></div>
            </div>

            <div class="side-card seller-card">
                <div class="side-card__title" style="padding: 0 0 10px">Sold by</div>
                @if ($vendor)
                    <div class="seller-card__head">
                        <span class="store-logo">
                            @if ($vendor->logoUrl())
                                <img src="{{ $vendor->logoUrl() }}" alt="">
                            @else
                                {{ $vendor->initials() }}
                            @endif
                        </span>
                        <div>
                            <div class="seller-card__name">{{ $vendor->store_name }}</div>
                            @if ($vendor->is_official)
                                <span class="badge badge--official"><x-shop.icon name="verified" class="icon--sm" />Official Store</span>
                            @else
                                <span class="badge badge--verified"><x-shop.icon name="shield" class="icon--sm" />Verified seller</span>
                            @endif
                        </div>
                    </div>
                    <div class="seller-metrics">
                        <div><strong>{{ $store['positive_percent'] !== null ? $store['positive_percent'].'%' : '—' }}</strong><span>Positive ratings</span></div>
                        <div><strong data-followers>{{ number_format($store['followers']) }}</strong><span>Followers</span></div>
                        <div><strong>{{ number_format($store['products']) }}</strong><span>Products</span></div>
                    </div>
                    <div class="cluster">
                        <a class="btn btn--outline btn--sm" style="flex: 1" href="{{ $vendor->storeUrl() }}">Visit store</a>
                        @php $following = in_array((int) $vendor->id, $followedStoreIds, true); @endphp
                        <button type="button" class="btn btn--ghost btn--sm" style="flex: 1" data-follow="{{ route('stores.follow', $vendor->slug) }}" aria-pressed="{{ $following ? 'true' : 'false' }}"><x-shop.icon name="plus" class="icon--sm" /><span data-follow-label>{{ $following ? 'Following' : 'Follow' }}</span></button>
                    </div>
                    <p class="small muted" style="margin: 10px 0 0">Selling since {{ $store['joined'] }}</p>
                @else
                    <div class="seller-card__head">
                        <x-shop.logo :name="$marketplace['name']" />
                    </div>
                    <p class="small muted" style="margin: 0">Stocked, packed and delivered by {{ $marketplace['name'] }}.</p>
                @endif
            </div>
        </aside>
    </div>

    <nav class="tabs" data-scrollspy aria-label="Product information">
        <a href="#description" class="is-active">Description</a>
        <a href="#specifications">Specifications</a>
        <a href="#reviews">Reviews ({{ $ratingCount }})</a>
        <a href="#questions">Questions ({{ $questions->count() }})</a>
    </nav>

    <section class="pdp-section" id="description">
        <h2>Product details</h2>
        <div class="prose">
            @if (filled($product->description))
                {!! nl2br(e(trim(strip_tags($product->description)))) !!}
            @else
                <p class="muted">The seller has not added a description yet. Ask a question below.</p>
            @endif
        </div>
    </section>

    <section class="pdp-section" id="specifications">
        <h2>Specifications</h2>
        <table class="spec-table">
            @foreach ($specs as $label => $value)
                <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
            @endforeach
        </table>
    </section>

    <section class="pdp-section" id="reviews">
        <h2>Ratings &amp; reviews</h2>
        <div class="review-summary">
            <div class="review-summary__score">
                <strong>{{ number_format($ratingAverage, 1) }}<span style="font-size: 18px">/5</span></strong>
                <x-shop.stars :rating="$ratingAverage" size="lg" />
                <span>{{ $ratingCount }} {{ \Illuminate\Support\Str::plural('rating', $ratingCount) }}</span>
            </div>
            <div class="histogram">
                @for ($stars = 5; $stars >= 1; $stars--)
                    @php $starCount = (int) ($ratingBreakdown[$stars] ?? 0); @endphp
                    <div>
                        <span>{{ $stars }} star</span>
                        <span class="bar"><span style="width: {{ $ratingCount > 0 ? round($starCount / $ratingCount * 100) : 0 }}%"></span></span>
                        <span>{{ $starCount }}</span>
                    </div>
                @endfor
            </div>
        </div>

        @if ($canReview)
            <form class="card" method="post" action="{{ route('ratings.store') }}" style="margin-bottom: 18px">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <h3 class="card__title">Rate this product</h3>
                <div class="star-input" role="radiogroup" aria-label="Your rating">
                    @for ($stars = 5; $stars >= 1; $stars--)
                        <input type="radio" name="rating" id="rate-{{ $stars }}" value="{{ $stars }}" required>
                        <label for="rate-{{ $stars }}" title="{{ $stars }} stars"><x-shop.icon name="star" /><span class="sr-only">{{ $stars }} stars</span></label>
                    @endfor
                </div>
                <div class="field" style="margin-top: 12px">
                    <label for="review-text">Your review (optional)</label>
                    <textarea class="textarea" id="review-text" name="review" maxlength="1000" placeholder="What did you like or dislike?"></textarea>
                </div>
                <button type="submit" class="btn btn--primary">Submit review</button>
            </form>
        @endif

        @forelse ($ratings as $rating)
            <article class="review">
                <div class="review__head">
                    <x-shop.stars :rating="$rating->rating" />
                    <strong>{{ \Illuminate\Support\Str::of($rating->user?->name ?? 'Customer')->before(' ') }}</strong>
                    <span class="badge badge--success"><x-shop.icon name="check" class="icon--sm" />Verified purchase</span>
                    <span>{{ $rating->created_at?->format('j M Y') }}</span>
                </div>
                @if (filled($rating->review))
                    <p class="review__body">{{ $rating->review }}</p>
                @endif
                @if (filled($rating->seller_reply))
                    <div class="seller-reply"><strong>Seller response</strong>{{ $rating->seller_reply }}</div>
                @endif
            </article>
        @empty
            <p class="muted">No reviews yet. Customers who buy this product can review it from their orders.</p>
        @endforelse
    </section>

    <section class="pdp-section" id="questions">
        <h2>Questions about this product</h2>
        @if ($customer)
            <form class="card" method="post" action="{{ route('products.questions.store', $product->id) }}" style="margin-bottom: 14px">
                @csrf
                <div class="field">
                    <label for="question-text">Ask the seller</label>
                    <textarea class="textarea" id="question-text" name="question" minlength="5" maxlength="500" required placeholder="Is it fresh? What's the size? When does it expire?" style="min-height: 72px">{{ old('question') }}</textarea>
                    @error('question')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn--primary btn--sm">Ask question</button>
            </form>
        @else
            <p class="muted"><a class="link" href="{{ route('login') }}">Log in</a> to ask the seller a question. Answers show here for everyone.</p>
        @endif

        @forelse ($questions as $question)
            <div class="qa">
                <div class="qa__line"><span class="qa__tag">Q</span><span>{{ $question->question }}</span></div>
                <div class="qa__meta">{{ \Illuminate\Support\Str::of($question->user?->name ?? 'Customer')->before(' ') }} · {{ $question->created_at?->diffForHumans() }}</div>
                @if ($question->isAnswered())
                    <div class="qa__line"><span class="qa__tag qa__tag--a">A</span><span>{{ $question->answer }}</span></div>
                    <div class="qa__meta">{{ $vendor?->store_name ?? $marketplace['name'] }} · {{ $question->answered_at?->diffForHumans() }}</div>
                @else
                    <div class="qa__meta">Waiting for the seller to answer</div>
                @endif
            </div>
        @empty
            <p class="muted">No questions yet. Be the first to ask.</p>
        @endforelse
    </section>

    @if ($moreFromStore->isNotEmpty() && $vendor)
        @include('front.products.rail', ['id' => 'store', 'title' => 'More from '.$vendor->store_name, 'lead' => null, 'products' => $moreFromStore, 'link' => $vendor->storeUrl()])
    @endif
    @if ($similarProducts->isNotEmpty())
        @include('front.products.rail', ['id' => 'similar', 'title' => 'You may also like', 'lead' => null, 'products' => $similarProducts, 'link' => $product->category ? url($product->category->url) : null])
    @endif
    @if ($recentlyViewed->isNotEmpty())
        @include('front.products.rail', ['id' => 'recent', 'title' => 'Recently viewed', 'lead' => null, 'products' => $recentlyViewed, 'link' => null])
    @endif

    @if ($purchasable)
        <div class="buy-bar">
            <button type="button" class="btn btn--primary" data-buy-proxy="add"><x-shop.icon name="bag" />Add to bag</button>
            <button type="button" class="btn btn--accent" data-buy-proxy="buy">Buy now</button>
        </div>
    @endif
@endsection
