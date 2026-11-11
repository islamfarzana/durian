<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Section extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('storefront.sections'));
        static::deleted(fn () => Cache::forget('storefront.sections'));
    }

    public function categories()
    {
        return $this->hasMany('App\Models\Category', 'section_id')->where([
            'parent_id' => 0,
            'status' => 1,
        ])->with('subCategories');
    }

    public static function sections()
    {
        return Cache::remember('storefront.sections', 600, function () {
            return Section::with('categories')->where('status', 1)->get()->toArray();
        });
    }
}
