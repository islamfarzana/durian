<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
            'holiday_mode' => 'boolean',
            'status' => 'integer',
            'commission' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vendor $vendor): void {
            if (blank($vendor->slug)) {
                $vendor->slug = static::uniqueSlug($vendor->name ?: 'store', $vendor->id);
            }
        });
    }

    public function storeTheme(): BelongsTo
    {
        return $this->belongsTo(StoreTheme::class, 'theme_id');
    }

    public function vendorbusinessdetails()
    {
        return $this->belongsTo('App\Models\VendorsBusinessDetail', 'id', 'vendor_id');
    }

    public function businessDetails(): HasOne
    {
        return $this->hasOne(VendorsBusinessDetail::class);
    }

    public function bankDetails(): HasOne
    {
        return $this->hasOne(VendorsBankDetail::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_follows')->withTimestamps();
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class);
    }

    /**
     * Approved stores that have a public URL.
     */
    #[Scope]
    protected function listed(Builder $query): void
    {
        $query->where('vendors.status', 1)->whereNotNull('vendors.slug');
    }

    /**
     * Shop name from the business profile, or the account name until one exists.
     */
    protected function storeName(): Attribute
    {
        return Attribute::get(function (): string {
            $details = $this->relationLoaded('vendorbusinessdetails') ? $this->vendorbusinessdetails : $this->businessDetails;

            return filled($details?->shop_name) ? $details->shop_name : (string) $this->name;
        });
    }

    public function isAcceptingOrders(): bool
    {
        return $this->status === 1 && ! $this->holiday_mode;
    }

    public function storeUrl(): string
    {
        return route('stores.show', $this->slug);
    }

    public function logoUrl(): ?string
    {
        return filled($this->logo) && is_file(public_path($this->logo)) ? asset($this->logo) : null;
    }

    public function bannerUrl(): ?string
    {
        return filled($this->banner) && is_file(public_path($this->banner)) ? asset($this->banner) : null;
    }

    public function initials(): string
    {
        return Str::upper(Str::substr(Str::of($this->store_name)->squish()->value(), 0, 1)) ?: 'S';
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'store';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public static function getVendorShop($vendorid)
    {
        $shop = VendorsBusinessDetail::where('vendor_id', $vendorid)->first();

        if ($shop === null) {
            $vendorName = Vendor::query()->whereKey($vendorid)->value('name');

            return [
                'shop_name' => $vendorName ?: 'Vendor',
                'shop_address' => '',
                'shop_city' => '',
                'shop_state' => '',
                'shop_country' => '',
                'shop_pincode' => '',
                'shop_mobile' => '',
                'shop_email' => '',
                'address_proof_image' => '',
            ];
        }

        return $shop->toArray();
    }

    public static function getVendorCommission($vendor_id)
    {
        $getVendorCommission = Vendor::select('commission')->where('id', $vendor_id)->first()->toArray();

        return $getVendorCommission['commission'];
    }
}
