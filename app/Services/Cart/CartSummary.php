<?php

namespace App\Services\Cart;

use Illuminate\Support\Collection;

final readonly class CartSummary
{
    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  Collection<int, array{vendor_id: int, name: string, url: ?string, is_official: bool, lines: Collection<int, CartLine>, subtotal: float}>  $packages
     */
    public function __construct(
        public Collection $lines,
        public Collection $packages,
        public float $subtotal,
        public float $savings,
        public VoucherResult $voucher,
        public float $deliveryFee,
        public ?float $freeDeliveryRemaining,
        public float $weightInGrams,
    ) {}

    public function itemCount(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line): int => $line->quantity);
    }

    public function voucherDiscount(): float
    {
        return $this->voucher->applies() ? $this->voucher->discount : 0;
    }

    public function total(): float
    {
        return round(max(0, $this->subtotal - $this->voucherDiscount()) + $this->deliveryFee, 2);
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function hasProblems(): bool
    {
        return $this->lines->contains(fn (CartLine $line): bool => $line->problem() !== null);
    }

    /**
     * Bag quantity per product, which product cards use for their steppers.
     *
     * @return array<int, int>
     */
    public function quantitiesByProduct(): array
    {
        return $this->lines
            ->groupBy(fn (CartLine $line): int => (int) $line->product->id)
            ->map(fn (Collection $lines): int => (int) $lines->sum(fn (CartLine $line): int => $line->quantity))
            ->all();
    }
}
