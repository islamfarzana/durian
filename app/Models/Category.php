<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('storefront.sections');
        });
        static::deleted(function () {
            Cache::forget('storefront.sections');
        });
    }

    /**
     * Storefront paths that must not be taken over by a category URL.
     *
     * @return array<int, string>
     */
    public static function reservedUrls(): array
    {
        return [
            'admin',
            'api',
            'cart',
            'checkout',
            'contact',
            'delivery-area',
            'error',
            'flash-sale',
            'getmoreproducts',
            'offers',
            'orders',
            'paypal',
            'product',
            'products',
            'quickview',
            'search',
            'search-products',
            'search-suggestion',
            'store',
            'stores',
            'success',
            'thanks',
            'user',
            'vendor',
            'wishlist',
        ];
    }

    public function section()
    {
        return $this->belongsTo('App\Models\Section', 'section_id')->select('id', 'name');
    }

    public function parentCategory()
    {
        return $this->belongsTo('App\Models\Category', 'parent_id')->select('id', 'category_name');
    }

    public function subCategories()
    {
        return $this->hasMany('App\Models\Category', 'parent_id')->where('status', 1);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function imageUrl(): ?string
    {
        if (filled($this->category_image) && is_file(public_path('front/images/category_images/'.$this->category_image))) {
            return asset('front/images/category_images/'.$this->category_image);
        }

        return null;
    }

    /**
     * Ids of this category and its active children, the set a listing page shows.
     *
     * @return array<int, int>
     */
    public function idsWithChildren(): array
    {
        return array_merge([(int) $this->id], $this->subCategories()->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    public static function categoryDetails($url)
    {
        $categoryDetails = Category::select('id', 'parent_id', 'category_name', 'url', 'description', 'meta_title', 'meta_description', 'meta_keywords')->with([
            'subCategories' => function ($query) {
                $query->select('id', 'parent_id', 'category_name', 'url', 'description', 'meta_title', 'meta_description', 'meta_keywords');
            },
        ])->where('url', $url)->first();

        if ($categoryDetails === null) {
            abort(404);
        }

        $categoryDetails = $categoryDetails->toArray();

        $catIds = [];
        $catIds[] = $categoryDetails['id'];

        if ($categoryDetails['parent_id'] == 0) {

            $breadcrumbs = '
                <li class="breadcrumb-item active" aria-current="page"><a href="'.url($categoryDetails['url']).'">'.$categoryDetails['category_name'].'</a></li>
            ';
        } else {

            $parentCategory = Category::select('category_name', 'url')->where('id', $categoryDetails['parent_id'])->first()->toArray();
            $breadcrumbs = '
                <li class="breadcrumb-item"><a href="'.url($parentCategory['url']).'">'.$parentCategory['category_name'].'</a></li>
                <li class="breadcrumb-item active" aria-current="page"><a href="'.url($categoryDetails['url']).'">'.$categoryDetails['category_name'].'</a></li>
            ';
        }

        foreach ($categoryDetails['sub_categories'] as $key => $subcat) {
            $catIds[] = $subcat['id'];
        }

        $resp = [
            'catIds' => $catIds,
            'categoryDetails' => $categoryDetails,
            'breadcrumbs' => $breadcrumbs,
        ];

        return $resp;
    }

    public static function getCategoryName($category_id)
    {
        $getCategoryName = Category::select('category_name')->where('id', $category_id)->first();

        return $getCategoryName->category_name;
    }

    public static function getCategoryStatus($category_id)
    {
        $getCategoryStatus = Category::select('status')->where('id', $category_id)->first();

        return $getCategoryStatus->status;
    }
}
