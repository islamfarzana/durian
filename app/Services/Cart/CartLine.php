<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Services\Catalog\Price;

final readonly class CartLine
{
    public function __construct(
        public Cart $row,
        public Product $product,
        public ?ProductsAttribute $variant,
        public Price $price,
        public int $quantity,
    ) {}

    public function total(): float
    {
        return round($this->price->final * $this->quantity, 2);
    }

    public function saving(): float
    {
        return round($this->price->saving() * $this->quantity, 2);
    }

    public function vendorId(): int
    {
        return (int) $this->product->vendor_id;
    }

    public function weight(): float
    {
        return (float) $this->product->product_weight * $this->quantity;
    }

    /**
     * Why this line cannot be ordered as it is, or null when it can.
     */
    public function problem(): ?string
    {
        if ((int) $this->product->status !== 1 || $this->variant === null || $this->variant->status !== 1) {
            return 'No longer available';
        }

        if ($this->product->vendor_id > 0 && ! ($this->product->vendor?->isAcceptingOrders() ?? false)) {
            return 'Seller is not taking orders right now';
        }

        if ($this->variant->stock < 1) {
            return 'Out of stock';
        }

        if ($this->variant->stock < $this->quantity) {
            return 'Only '.$this->variant->stock.' left';
        }

        return null;
    }
}
