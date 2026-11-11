<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsAttribute extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'stock' => 'integer',
            'status' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function getProductStock($product_id, $size)
    {
        $getProductStock = ProductsAttribute::select('stock')->where([
            'product_id' => $product_id,
            'size' => $size,
        ])->first();

        return $getProductStock->stock;
    }

    public static function getAttributeStatus($product_id, $size)
    {
        $getAttributeStatus = ProductsAttribute::select('status')->where([
            'product_id' => $product_id,
            'size' => $size,
        ])->first();

        return $getAttributeStatus->status;
    }
}
