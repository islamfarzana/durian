<?php

namespace App\Services\Checkout;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryAddress;
use App\Models\DeliverySlot;
use App\Models\FlashSaleProduct;
use App\Models\Order;
use App\Models\OrdersLog;
use App\Models\OrdersProduct;
use App\Models\Product;
use App\Models\ProductsAttribute;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartService;
use App\Services\Cart\VoucherCalculator;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Turns the customer's bag into one order with a line per item. Stock and
 * flash-sale counters are locked while the order is written, so two customers
 * cannot buy the same last unit.
 */
class PlaceOrder
{
    public function __construct(
        private CartService $cart,
        private VoucherCalculator $vouchers,
        private DeliveryOptions $delivery,
    ) {}

    /**
     * @throws CheckoutException
     */
    public function handle(User $user, DeliveryAddress $address, string $paymentGateway, string $deliveryDate, DeliverySlot $slot, ?string $notes = null): Order
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw new CheckoutException('Your bag is empty. Add something before checking out.');
        }

        $order = DB::transaction(function () use ($lines, $user, $address, $paymentGateway, $deliveryDate, $slot, $notes): Order {
            $pricedLines = $lines->map(fn (CartLine $line): array => $this->lockAndPrice($line));
            $cartLines = $pricedLines->map(fn (array $priced): CartLine => $priced['line']);
            $subtotal = round($cartLines->sum(fn (CartLine $line): float => $line->total()), 2);
            [$couponCode, $discount] = $this->voucherFor($cartLines, $user);
            $weight = (float) $cartLines->sum(fn (CartLine $line): float => $line->weight());
            $deliveryFee = $this->delivery->fee(max(0, $subtotal - $discount), $weight, $address->city, $address->country);
            $isCashOnDelivery = $paymentGateway === 'COD';

            $order = new Order;
            $order->user_id = $user->id;
            $order->name = $address->name;
            $order->address = $address->address;
            $order->city = $address->city;
            $order->state = $address->state;
            $order->country = $address->country;
            $order->pincode = $address->pincode;
            $order->mobile = $address->mobile;
            $order->email = $user->email;
            $order->shipping_charges = $deliveryFee;
            $order->coupon_code = $couponCode;
            $order->coupon_amount = $discount;
            $order->order_status = $isCashOnDelivery ? 'Order Placed' : 'Pending';
            $order->payment_method = $isCashOnDelivery ? 'COD' : 'Prepaid';
            $order->payment_gateway = $paymentGateway;
            $order->grand_total = round(max(0, $subtotal - $discount) + $deliveryFee, 2);
            $order->delivery_date = $deliveryDate;
            $order->delivery_slot = $slot->label;
            $order->notes = $notes;
            $order->save();

            $commissions = Vendor::query()
                ->whereIn('id', $cartLines->map(fn (CartLine $line): int => $line->vendorId())->filter()->unique())
                ->pluck('commission', 'id');

            foreach ($pricedLines as $priced) {
                $this->writeLine($order, $user, $priced['line'], $priced['flashSale'], (float) ($commissions[$priced['line']->vendorId()] ?? 0));
            }

            $log = new OrdersLog;
            $log->order_id = $order->id;
            $log->order_status = $order->order_status;
            $log->save();

            Cart::query()->where('user_id', $user->id)->delete();

            return $order;
        });

        $this->cart->removeVoucher();
        Session::put('order_id', $order->id);
        Session::put('grand_total', $order->grand_total);

        return $order;
    }

    /**
     * @return array{line: CartLine, flashSale: ?FlashSaleProduct}
     */
    private function lockAndPrice(CartLine $line): array
    {
        $product = $line->product;
        $variant = $line->variant === null ? null : ProductsAttribute::query()->whereKey($line->variant->id)->lockForUpdate()->first();
        $name = $product->product_name.($line->row->size !== '' ? ' ('.$line->row->size.')' : '');

        if ($variant === null || $variant->status !== 1 || (int) $product->status !== 1 || (int) ($product->category?->status ?? 0) !== 1) {
            throw new CheckoutException($name.' is no longer available. Please remove it from your bag.');
        }

        if ($product->vendor_id > 0 && ! ($product->vendor?->isAcceptingOrders() ?? false)) {
            throw new CheckoutException($name.' is from a seller who is not taking orders right now. Please remove it from your bag.');
        }

        if ($variant->stock < $line->quantity) {
            throw new CheckoutException($name.' has only '.max(0, $variant->stock).' left in stock. Please reduce the quantity and try again.');
        }

        $price = $product->price($variant);
        $flashSale = null;

        if ($price->flashSale !== null) {
            $flashSale = FlashSaleProduct::query()->whereKey($price->flashSale->id)->lockForUpdate()->first();

            if ($flashSale === null || ($flashSale->stock_limit !== null && $flashSale->sold_count + $line->quantity > $flashSale->stock_limit)) {
                $product->setRelation('activeFlashSale', null);
                $price = $product->price($variant);
                $flashSale = null;
            }
        }

        return [
            'line' => new CartLine($line->row, $product, $variant, $price, $line->quantity),
            'flashSale' => $flashSale,
        ];
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return array{0: ?string, 1: float}
     */
    private function voucherFor(Collection $lines, User $user): array
    {
        $code = Session::get(CartService::SESSION_VOUCHER);

        if (! is_string($code) || $code === '') {
            return [null, 0.0];
        }

        $coupon = Coupon::query()->with('vendor.businessDetails')->where('coupon_code', $code)->first();

        if ($coupon === null) {
            return [null, 0.0];
        }

        $result = $this->vouchers->evaluate($coupon, $lines, $user);

        if (! $result->applies()) {
            throw new CheckoutException('Voucher '.$coupon->coupon_code.': '.($result->error ?? 'it no longer applies.').' Remove it from your bag to continue.');
        }

        return [$coupon->coupon_code, $result->discount];
    }

    private function writeLine(Order $order, User $user, CartLine $line, ?FlashSaleProduct $flashSale, float $commission): void
    {
        $product = $line->product;

        $orderLine = new OrdersProduct;
        $orderLine->order_id = $order->id;
        $orderLine->user_id = $user->id;
        $orderLine->admin_id = $product->admin_id;
        $orderLine->vendor_id = $product->vendor_id;
        $orderLine->commission = $product->vendor_id > 0 ? $commission : 0;
        $orderLine->product_id = $product->id;
        $orderLine->product_code = $product->product_code;
        $orderLine->product_name = $product->product_name;
        $orderLine->product_color = $product->product_color;
        $orderLine->product_size = $line->row->size;
        $orderLine->product_price = $line->price->final;
        $orderLine->product_qty = $line->quantity;
        $orderLine->item_status = 'Pending';
        $orderLine->save();

        ProductsAttribute::query()->whereKey($line->variant->id)->decrement('stock', $line->quantity);
        Product::query()->whereKey($product->id)->increment('sold_count', $line->quantity);
        $flashSale?->increment('sold_count', $line->quantity);
    }
}
