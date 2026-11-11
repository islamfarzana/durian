<?php

namespace App\Services\Cart;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Decides whether a voucher applies to a bag and how much it takes off.
 * A platform voucher covers every eligible line; a store voucher only covers
 * lines sold by the store that issued it.
 */
class VoucherCalculator
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function evaluate(Coupon $coupon, Collection $lines, ?User $user): VoucherResult
    {
        $today = now(config('marketplace.timezone'))->startOfDay();

        if ($coupon->status !== 1) {
            return new VoucherResult($coupon, error: 'This voucher is not active.');
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->startOfDay()->gt($today)) {
            return new VoucherResult($coupon, error: 'This voucher starts on '.$coupon->starts_at->format('j M Y').'.');
        }

        if ($coupon->expiry_date === null || $coupon->expiry_date->startOfDay()->lt($today)) {
            return new VoucherResult($coupon, error: 'This voucher has expired.');
        }

        $eligibleLines = $this->eligibleLines($coupon, $lines);

        if ($eligibleLines->isEmpty()) {
            return new VoucherResult($coupon, error: $coupon->isStoreVoucher()
                ? 'This voucher only works on items from '.($coupon->vendor?->store_name ?? 'its store').'.'
                : 'This voucher does not apply to the items in your bag.');
        }

        if ($error = $this->accountError($coupon, $user)) {
            return new VoucherResult($coupon, error: $error);
        }

        if ($coupon->usage_limit !== null && $this->redemptions($coupon) >= $coupon->usage_limit) {
            return new VoucherResult($coupon, error: 'This voucher has been fully redeemed.');
        }

        $eligibleSubtotal = round($eligibleLines->sum(fn (CartLine $line): float => $line->total()), 2);

        if ($eligibleSubtotal < $coupon->min_spend) {
            $shortfall = number_format($coupon->min_spend - $eligibleSubtotal, 2, '.', '');

            return new VoucherResult($coupon, error: 'Add '.Setting::get('currency_symbol').rtrim(rtrim($shortfall, '0'), '.').' more of eligible items to use this voucher.');
        }

        return new VoucherResult($coupon, $this->discountFor($coupon, $eligibleSubtotal));
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, CartLine>
     */
    private function eligibleLines(Coupon $coupon, Collection $lines): Collection
    {
        $categoryIds = $this->idList($coupon->categories);
        $brandIds = $this->idList($coupon->brands);

        return $lines
            ->filter(fn (CartLine $line): bool => $line->problem() === null)
            ->filter(fn (CartLine $line): bool => ! $coupon->isStoreVoucher() || $line->vendorId() === $coupon->vendor_id)
            ->filter(fn (CartLine $line): bool => $categoryIds === [] || in_array((int) $line->product->category_id, $categoryIds, true))
            ->filter(fn (CartLine $line): bool => $brandIds === [] || in_array((int) $line->product->brand_id, $brandIds, true))
            ->values();
    }

    private function accountError(Coupon $coupon, ?User $user): ?string
    {
        $allowedEmails = array_values(array_filter(array_map('trim', explode(',', (string) $coupon->users))));

        if ($allowedEmails !== []) {
            if ($user === null) {
                return 'Log in to use this voucher.';
            }

            if (! in_array(mb_strtolower($user->email), array_map('mb_strtolower', $allowedEmails), true)) {
                return 'This voucher is not available for your account.';
            }
        }

        if ($coupon->coupon_type === 'Single Time' && $user !== null) {
            $alreadyUsed = Order::query()
                ->where('user_id', $user->id)
                ->where('coupon_code', $coupon->coupon_code)
                ->where('order_status', '!=', 'Cancelled')
                ->exists();

            if ($alreadyUsed) {
                return 'You have already used this voucher.';
            }
        }

        return null;
    }

    private function redemptions(Coupon $coupon): int
    {
        return Order::query()
            ->where('coupon_code', $coupon->coupon_code)
            ->where('order_status', '!=', 'Cancelled')
            ->count();
    }

    private function discountFor(Coupon $coupon, float $eligibleSubtotal): float
    {
        if ($coupon->amount_type === 'Fixed') {
            return round(min($coupon->amount, $eligibleSubtotal), 2);
        }

        $discount = $eligibleSubtotal * $coupon->amount / 100;

        if ($coupon->max_discount !== null && $coupon->max_discount > 0) {
            $discount = min($discount, $coupon->max_discount);
        }

        return round(min($discount, $eligibleSubtotal), 2);
    }

    /**
     * @return array<int, int>
     */
    private function idList(?string $csv): array
    {
        return array_values(array_map('intval', array_filter(explode(',', (string) $csv), fn (string $id): bool => is_numeric(trim($id)))));
    }
}
