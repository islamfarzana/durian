<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date:Y-m-d',
            'starts_at' => 'date:Y-m-d',
            'is_public' => 'boolean',
            'amount' => 'float',
            'min_spend' => 'float',
            'max_discount' => 'float',
            'usage_limit' => 'integer',
            'vendor_id' => 'integer',
            'status' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Active vouchers shown to every shopper on the offers and store pages.
     */
    #[Scope]
    protected function publiclyListed(Builder $query): void
    {
        $today = now(config('marketplace.timezone'))->toDateString();

        $query->where('status', 1)
            ->where('is_public', true)
            ->whereDate('expiry_date', '>=', $today)
            ->where(fn (Builder $starts) => $starts->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today));
    }

    public function isStoreVoucher(): bool
    {
        return $this->vendor_id > 0;
    }

    public function discountLabel(): string
    {
        $symbol = Setting::get('currency_symbol');

        return $this->amount_type === 'Fixed'
            ? $symbol.number_format($this->amount).' off'
            : rtrim(rtrim(number_format($this->amount, 2), '0'), '.').'% off';
    }

    public static function couponDetails($coupon_code)
    {
        $couponDetails = Coupon::where('coupon_code', $coupon_code)->first()->toArray();

        return $couponDetails;
    }
}
