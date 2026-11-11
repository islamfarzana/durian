<?php

namespace App\Services\Cart;

use App\Models\Coupon;

final readonly class VoucherResult
{
    public function __construct(
        public ?Coupon $coupon,
        public float $discount = 0,
        public ?string $error = null,
    ) {}

    public function applies(): bool
    {
        return $this->coupon !== null && $this->error === null && $this->discount > 0;
    }
}
