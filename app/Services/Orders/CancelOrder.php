<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrdersLog;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\ProductsAttribute;
use Illuminate\Support\Facades\DB;

/**
 * Customer cancellation of a cash-on-delivery order that no seller has shipped.
 * Every line's stock goes back on the shelf.
 */
class CancelOrder
{
    /**
     * @throws CannotCancelOrder
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lines = OrdersProduct::query()->where('order_id', $locked->id)->get();
            $alreadyShipped = $lines->contains(fn (OrdersProduct $line): bool => in_array($line->item_status, ['Shipped', 'Delivered'], true));

            if (! $locked->isCancellable() || $alreadyShipped) {
                throw new CannotCancelOrder('This order can no longer be cancelled. Please contact support.');
            }

            foreach ($lines as $line) {
                ProductsAttribute::query()
                    ->where('product_id', $line->product_id)
                    ->where('size', $line->product_size)
                    ->increment('stock', $line->product_qty);
                Product::query()
                    ->whereKey($line->product_id)
                    ->update(['sold_count' => DB::raw('GREATEST(CAST(sold_count AS SIGNED) - '.(int) $line->product_qty.', 0)')]);

                $line->item_status = 'Cancelled';
                $line->save();
            }

            $locked->order_status = 'Cancelled';
            $locked->save();

            $log = new OrdersLog;
            $log->order_id = $locked->id;
            $log->order_status = 'Cancelled';
            $log->save();
        });

        $order->refresh();
    }
}
