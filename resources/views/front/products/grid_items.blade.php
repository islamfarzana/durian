@foreach ($products as $product)
    <x-shop.product-card :product="$product" />
@endforeach
