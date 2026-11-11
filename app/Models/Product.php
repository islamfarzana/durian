<?php

namespace App\Models;

use App\Services\Catalog\Price;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    /**
     * Variant labels that describe no real choice, so they are not shown as a pack size.
     *
     * @var array<int, string>
     */
    private const GENERIC_VARIANT_LABELS = ['normal', 'standard', 'default', 'free size', 'one size'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_count' => 'integer',
            'return_days' => 'integer',
        ];
    }

    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\Brand', 'brand_id');
    }

    public function attributes()
    {
        return $this->hasMany('App\Models\ProductsAttribute');
    }

    /**
     * The sellable size, pack, or colour rows. Same table as attributes(), whose
     * name collides with Eloquent's own $attributes property inside the model.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductsAttribute::class);
    }

    public function images()
    {
        return $this->hasMany('App\Models\ProductsImage');
    }

    public function vendor()
    {
        return $this->belongsTo('App\Models\Vendor', 'vendor_id')->with('vendorbusinessdetails');
    }

    public function approvedRatings(): HasMany
    {
        return $this->hasMany(Rating::class)->where('status', 1);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function flashSaleItems(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    /**
     * The approved flash sale item that is running now and still has flash stock.
     */
    public function activeFlashSale(): HasOne
    {
        return $this->hasOne(FlashSaleProduct::class)
            ->where('flash_sale_products.status', FlashSaleProduct::APPROVED)
            ->where(function (Builder $query): void {
                $query->whereNull('flash_sale_products.stock_limit')
                    ->orWhereColumn('flash_sale_products.sold_count', '<', 'flash_sale_products.stock_limit');
            })
            ->whereHas('flashSale', fn (Builder $query) => $query->running())
            ->orderByDesc('flash_sale_products.discount_percent');
    }

    /**
     * Products a shopper may see: active, in an active category, and sold by the
     * platform or by an approved vendor who is not on holiday.
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('products.status', 1)
            ->whereHas('category', fn (Builder $category) => $category->where('status', 1))
            ->where(function (Builder $seller): void {
                $seller->where('products.vendor_id', 0)
                    ->orWhereIn('products.vendor_id', Vendor::query()->select('id')->where('status', 1)->where('holiday_mode', false));
            });
    }

    /**
     * Everything a product card renders, loaded in a fixed number of queries.
     */
    #[Scope]
    protected function withCardData(Builder $query): void
    {
        $query->with([
            'category:id,category_name,url,category_discount,status',
            'brand:id,name',
            'vendor:id,name,slug,is_official,status,holiday_mode',
            'activeFlashSale.flashSale',
            'variants' => fn (HasMany $variants) => $variants->where('status', 1)->orderBy('id'),
        ])
            ->withAvg('approvedRatings as rating_avg', 'rating')
            ->withCount('approvedRatings as rating_count');
    }

    /**
     * Price of one variant, or of the product itself when no variant is given.
     * A running flash sale replaces the product or category discount.
     */
    public function price(?ProductsAttribute $variant = null): Price
    {
        $original = (float) ($variant?->price ?? $this->product_price);
        $flashSale = $this->activeFlashSale;

        if ($flashSale instanceof FlashSaleProduct && $flashSale->hasStockLeft()) {
            return new Price($original, self::discounted($original, $flashSale->discount_percent), $flashSale);
        }

        $percent = (float) $this->product_discount > 0
            ? (float) $this->product_discount
            : (float) ($this->category?->category_discount ?? 0);

        return new Price($original, self::discounted($original, $percent));
    }

    /**
     * The variant a quick "add" button puts in the bag: the first one in stock.
     */
    public function defaultVariant(): ?ProductsAttribute
    {
        $variants = $this->activeVariants();

        return $variants->first(fn (ProductsAttribute $variant): bool => $variant->stock > 0) ?? $variants->first();
    }

    /**
     * @return Collection<int, ProductsAttribute>
     */
    public function activeVariants(): Collection
    {
        return $this->variants->where('status', 1)->values();
    }

    public function isPurchasable(): bool
    {
        return (int) $this->status === 1
            && ($this->defaultVariant()?->stock ?? 0) > 0
            && ($this->vendor_id == 0 || ($this->vendor?->isAcceptingOrders() ?? false));
    }

    /**
     * Pack size shown under the name, such as "1 kg" or "500 gm".
     */
    public function packLabel(): ?string
    {
        if (filled($this->unit)) {
            return $this->unit;
        }

        $variants = $this->activeVariants();

        if ($variants->count() === 1 && ! in_array(mb_strtolower(trim((string) $variants->first()->size)), self::GENERIC_VARIANT_LABELS, true)) {
            return $variants->first()->size;
        }

        return null;
    }

    public function imageUrl(string $size = 'medium'): string
    {
        return self::imageUrlFor($this->product_image, $size);
    }

    public static function imageUrlFor(?string $image, string $size = 'medium'): string
    {
        if (filled($image) && is_file(public_path('front/images/product_images/'.$size.'/'.$image))) {
            return asset('front/images/product_images/'.$size.'/'.$image);
        }

        return asset('front/images/placeholder-product.svg');
    }

    public function url(): string
    {
        return route('products.show', $this->id);
    }

    public static function getDiscountPrice($product_id)
    {
        $product = Product::query()->with(['category', 'activeFlashSale'])->find($product_id);

        if ($product === null) {
            return 0;
        }

        $price = $product->price();

        return $price->hasDiscount() ? $price->final : 0;
    }

    public static function getDiscountAttributePrice($product_id, $size)
    {
        $variant = ProductsAttribute::query()->where([
            'product_id' => $product_id,
            'size' => $size,
        ])->firstOrFail();
        $price = Product::query()->with(['category', 'activeFlashSale'])->findOrFail($product_id)->price($variant);

        return [
            'product_price' => $price->original,
            'final_price' => $price->final,
            'discount' => $price->saving(),
        ];
    }

    public static function isProductNew($product_id)
    {

        $productIds = Product::select('id')->where('status', 1)->orderBy('id', 'Desc')->limit(3)->pluck('id');
        $productIds = json_decode(json_encode($productIds, true));

        if (in_array($product_id, $productIds)) {
            $isProductNew = 'Yes';
        } else {
            $isProductNew = 'No';
        }

        return $isProductNew;
    }

    public static function getProductImage($product_id)
    {
        $getProductImage = Product::select('product_image')->where('id', $product_id)->first()->toArray();

        return $getProductImage['product_image'];
    }

    public static function getProductStatus($product_id)
    {
        $getProductStatus = Product::select('status')->where('id', $product_id)->first();

        return $getProductStatus->status;
    }

    public static function deleteCartProduct($product_id)
    {
        Cart::where('product_id', $product_id)->delete();
    }

    /**
     * Discounted prices are rounded to whole currency units, the way taka prices are shown and paid.
     */
    private static function discounted(float $amount, float $percent): float
    {
        $percent = max(0, min(100, $percent));

        return round($amount - $amount * $percent / 100);
    }
}
