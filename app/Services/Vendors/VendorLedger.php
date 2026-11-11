<?php

namespace App\Services\Vendors;

use App\Models\OrdersProduct;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * What a seller has earned and what the marketplace owes them. Earnings clear
 * once an item is delivered; payouts already paid or requested come off the top.
 */
class VendorLedger
{
    /**
     * @return array{orders: int, gross: float, commission: float, net: float, cleared: float, paid: float, requested: float, available: float}
     */
    public function summary(Vendor $vendor): array
    {
        $totals = $this->openLines($vendor)
            ->selectRaw('COUNT(DISTINCT orders_products.order_id) as orders')
            ->selectRaw('COALESCE(SUM(orders_products.product_price * orders_products.product_qty), 0) as gross')
            ->selectRaw('COALESCE(SUM(orders_products.product_price * orders_products.product_qty * orders_products.commission / 100), 0) as commission')
            ->first();

        $cleared = (float) $this->openLines($vendor)
            ->where(fn (Builder $delivered) => $delivered->where('orders.order_status', 'Delivered')->orWhere('orders_products.item_status', 'Delivered'))
            ->selectRaw('COALESCE(SUM(orders_products.product_price * orders_products.product_qty * (1 - orders_products.commission / 100)), 0) as net')
            ->value('net');

        $paid = (float) $vendor->payouts()->where('status', VendorPayout::PAID)->sum('amount');
        $requested = (float) $vendor->payouts()->where('status', VendorPayout::REQUESTED)->sum('amount');
        $gross = round((float) $totals->gross, 2);
        $commission = round((float) $totals->commission, 2);

        return [
            'orders' => (int) $totals->orders,
            'gross' => $gross,
            'commission' => $commission,
            'net' => round($gross - $commission, 2),
            'cleared' => round($cleared, 2),
            'paid' => round($paid, 2),
            'requested' => round($requested, 2),
            'available' => round(max(0, $cleared - $paid - $requested), 2),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, OrdersProduct>
     */
    public function statement(Vendor $vendor, int $perPage = 20): LengthAwarePaginator
    {
        return OrdersProduct::query()
            ->with('order:id,order_status,payment_method,created_at')
            ->where('vendor_id', $vendor->id)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @return Builder<OrdersProduct>
     */
    private function openLines(Vendor $vendor): Builder
    {
        return OrdersProduct::query()
            ->join('orders', 'orders.id', '=', 'orders_products.order_id')
            ->where('orders_products.vendor_id', $vendor->id)
            ->where('orders.order_status', '!=', 'Cancelled')
            ->where(fn (Builder $line) => $line->whereNull('orders_products.item_status')->orWhere('orders_products.item_status', '!=', 'Cancelled'));
    }
}
