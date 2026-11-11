<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleProduct extends Model
{
    use HasFactory;

    public const PENDING = 0;

    public const APPROVED = 1;

    public const REJECTED = 2;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['flash_sale_id', 'product_id', 'vendor_id', 'discount_percent', 'stock_limit', 'sold_count', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_percent' => 'float',
            'stock_limit' => 'integer',
            'sold_count' => 'integer',
            'status' => 'integer',
            'vendor_id' => 'integer',
        ];
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasStockLeft(): bool
    {
        return $this->stock_limit === null || $this->sold_count < $this->stock_limit;
    }

    /**
     * Share of the flash stock already sold, for the progress bar.
     */
    public function soldPercent(): int
    {
        if ($this->stock_limit === null || $this->stock_limit === 0) {
            return 0;
        }

        return (int) min(100, round($this->sold_count / $this->stock_limit * 100));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            default => 'Pending review',
        };
    }
}
