<?php

namespace App\Services\Catalog;

use App\Models\FlashSaleProduct;

final readonly class Price
{
    public function __construct(
        public float $original,
        public float $final,
        public ?FlashSaleProduct $flashSale = null,
    ) {}

    public function hasDiscount(): bool
    {
        return $this->final < $this->original;
    }

    public function discountPercent(): int
    {
        if ($this->original <= 0 || ! $this->hasDiscount()) {
            return 0;
        }

        return (int) round(($this->original - $this->final) / $this->original * 100);
    }

    public function saving(): float
    {
        return round($this->original - $this->final, 2);
    }
}
