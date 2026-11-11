<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdersProduct extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_price' => 'float',
            'product_qty' => 'integer',
            'commission' => 'float',
            'vendor_id' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function lineTotal(): float
    {
        return round($this->product_price * $this->product_qty, 2);
    }

    public function commissionAmount(): float
    {
        return round($this->lineTotal() * $this->commission / 100, 2);
    }

    public function netAmount(): float
    {
        return round($this->lineTotal() - $this->commissionAmount(), 2);
    }
}
