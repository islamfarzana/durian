<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Models\Setting;
use App\Models\User;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The shopper's bag. Guests are keyed by the session's cart id and signed-in
 * customers by user id, matching how login moves a guest bag onto the user.
 */
class CartService
{
    public const SESSION_VOUCHER = 'couponCode';

    private ?CartSummary $summary = null;

    public function __construct(
        private VoucherCalculator $vouchers,
        private DeliveryOptions $delivery,
    ) {}

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $rows = $this->ownedRows()
            ->with(['product' => fn ($query) => $query->withCardData()])
            ->orderByDesc('id')
            ->get();

        return $rows
            ->filter(fn (Cart $row): bool => $row->product instanceof Product)
            ->map(function (Cart $row): CartLine {
                $variant = $row->product->variants->first(fn (ProductsAttribute $variant): bool => $variant->size === $row->size);

                return new CartLine($row, $row->product, $variant, $row->product->price($variant), (int) $row->quantity);
            })
            ->values();
    }

    public function summary(?User $user = null): CartSummary
    {
        if ($this->summary !== null) {
            return $this->summary;
        }

        $lines = $this->lines();
        $orderable = $lines->filter(fn (CartLine $line): bool => $line->problem() === null);
        $subtotal = round($orderable->sum(fn (CartLine $line): float => $line->total()), 2);
        $weight = (float) $orderable->sum(fn (CartLine $line): float => $line->weight());

        $packages = $lines
            ->groupBy(fn (CartLine $line): int => $line->vendorId())
            ->map(function (Collection $packageLines, int $vendorId): array {
                $vendor = $packageLines->first()->product->vendor;

                return [
                    'vendor_id' => $vendorId,
                    'name' => $vendorId > 0 && $vendor ? $vendor->store_name : Setting::get('store_name'),
                    'url' => $vendorId > 0 && $vendor?->slug ? $vendor->storeUrl() : null,
                    'is_official' => $vendorId === 0 || (bool) $vendor?->is_official,
                    'lines' => $packageLines->values(),
                    'subtotal' => round($packageLines->filter(fn (CartLine $line): bool => $line->problem() === null)->sum(fn (CartLine $line): float => $line->total()), 2),
                ];
            })
            ->values();

        $voucher = $this->evaluateVoucher($orderable, $user ?? $this->customer());
        $subtotalAfterVoucher = max(0, $subtotal - ($voucher->applies() ? $voucher->discount : 0));

        return $this->summary = new CartSummary(
            lines: $lines,
            packages: $packages,
            subtotal: $subtotal,
            savings: round($orderable->sum(fn (CartLine $line): float => $line->saving()), 2),
            voucher: $voucher,
            deliveryFee: $this->delivery->fee($subtotalAfterVoucher, $weight, $this->delivery->selectedCity(), null),
            freeDeliveryRemaining: $this->delivery->freeDeliveryRemaining($subtotalAfterVoucher),
            weightInGrams: $weight,
        );
    }

    /**
     * Put an exact quantity of one variant in the bag; zero removes it.
     */
    public function set(Product $product, string $size, int $quantity): void
    {
        $this->summary = null;
        $row = $this->ownedRows()->where('product_id', $product->id)->where('size', $size)->first();

        if ($quantity <= 0) {
            $row?->delete();

            return;
        }

        $variant = $this->purchasableVariant($product, $size);

        if ($quantity > $variant->stock) {
            throw new CartException($variant->stock > 0
                ? 'Only '.$variant->stock.' of this item '.($variant->stock === 1 ? 'is' : 'are').' left.'
                : 'This item is out of stock.');
        }

        if ($row === null) {
            $row = new Cart;
            $row->session_id = $this->sessionKey();
            $row->user_id = $this->customer()?->id ?? 0;
            $row->product_id = $product->id;
            $row->size = $size;
        }

        $row->quantity = $quantity;
        $row->save();
    }

    public function add(Product $product, string $size, int $quantity): void
    {
        $current = (int) ($this->ownedRows()->where('product_id', $product->id)->where('size', $size)->value('quantity') ?? 0);

        $this->set($product, $size, $current + max(1, $quantity));
    }

    public function quantityOf(Product $product, string $size): int
    {
        return (int) ($this->ownedRows()->where('product_id', $product->id)->where('size', $size)->value('quantity') ?? 0);
    }

    /**
     * A cart row id that belongs to someone else is treated as missing.
     */
    public function findRow(int $cartId): ?Cart
    {
        return $this->ownedRows()->whereKey($cartId)->first();
    }

    public function remove(Cart $row): void
    {
        $this->summary = null;
        $this->ownedRows()->whereKey($row->id)->delete();
    }

    public function applyVoucher(string $code): VoucherResult
    {
        $this->summary = null;
        $coupon = Coupon::query()->with('vendor.businessDetails')->where('coupon_code', trim($code))->first();

        if ($coupon === null) {
            return new VoucherResult(null, error: 'We could not find that voucher code.');
        }

        $orderable = $this->lines()->filter(fn (CartLine $line): bool => $line->problem() === null);
        $result = $this->vouchers->evaluate($coupon, $orderable, $this->customer());

        if ($result->applies()) {
            Session::put(self::SESSION_VOUCHER, $coupon->coupon_code);
        }

        return $result;
    }

    public function removeVoucher(): void
    {
        $this->summary = null;
        Session::forget([self::SESSION_VOUCHER, 'couponAmount']);
    }

    public function clear(): void
    {
        $this->summary = null;
        $this->ownedRows()->delete();
        $this->removeVoucher();
    }

    /**
     * @param  Collection<int, CartLine>  $orderable
     */
    private function evaluateVoucher(Collection $orderable, ?User $user): VoucherResult
    {
        $code = Session::get(self::SESSION_VOUCHER);

        if (! is_string($code) || $code === '') {
            return new VoucherResult(null);
        }

        $coupon = Coupon::query()->with('vendor.businessDetails')->where('coupon_code', $code)->first();

        if ($coupon === null) {
            Session::forget(self::SESSION_VOUCHER);

            return new VoucherResult(null);
        }

        return $this->vouchers->evaluate($coupon, $orderable, $user);
    }

    private function purchasableVariant(Product $product, string $size): ProductsAttribute
    {
        $product->loadMissing('vendor');

        if ((int) $product->status !== 1) {
            throw new CartException('This product is no longer available.');
        }

        if ($product->vendor_id > 0 && ! ($product->vendor?->isAcceptingOrders() ?? false)) {
            throw new CartException('This seller is not taking orders right now.');
        }

        $variant = $product->variants()->where('size', $size)->where('status', 1)->first();

        if ($variant === null) {
            throw new CartException('Please choose an available option.');
        }

        return $variant;
    }

    /**
     * @return Builder<Cart>
     */
    private function ownedRows(): Builder
    {
        $customer = $this->customer();

        if ($customer !== null) {
            return Cart::query()->where('user_id', $customer->id);
        }

        return Cart::query()
            ->where('session_id', $this->sessionKey())
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', 0));
    }

    /**
     * Storefront customers sign in on the web guard; a signed-in admin is not a customer.
     */
    private function customer(): ?User
    {
        $user = Auth::guard('web')->user();

        return $user instanceof User ? $user : null;
    }

    private function sessionKey(): string
    {
        $key = Session::get('session_id');

        if (! is_string($key) || $key === '') {
            $key = Session::getId();
            Session::put('session_id', $key);
        }

        return $key;
    }
}
