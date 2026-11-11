<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\PlaceOrderRequest;
use App\Models\Country;
use App\Models\DeliveryAddress;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutException;
use App\Services\Checkout\PlaceOrder;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cart, DeliveryOptions $delivery): View|RedirectResponse
    {
        $bag = $cart->summary($request->user());

        if ($bag->isEmpty()) {
            return redirect()->route('cart')->with('error_message', 'Your bag is empty. Add something before checking out.');
        }

        if ($bag->hasProblems()) {
            return redirect()->route('cart')->with('error_message', 'Some items in your bag need attention before you can check out.');
        }

        $addresses = DeliveryAddress::query()->where('user_id', $request->user()->id)->orderByDesc('id')->get();
        $afterVoucher = max(0, $bag->subtotal - $bag->voucherDiscount());

        return view('front.products.checkout', [
            'bag' => $bag,
            'addresses' => $addresses,
            'deliveryFees' => $addresses->mapWithKeys(fn (DeliveryAddress $address): array => [
                $address->id => $delivery->fee($afterVoucher, $bag->weightInGrams, $address->city, $address->country),
            ])->all(),
            'deliveryDays' => $delivery->days(),
            'paymentGateways' => PlaceOrderRequest::paymentGateways(),
            'countries' => Country::query()->where('status', 1)->orderBy('country_name')->get(),
            'meta_title' => 'Checkout',
        ]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrder $placeOrder, DeliveryOptions $delivery): RedirectResponse
    {
        $address = DeliveryAddress::query()->where('user_id', $request->user()->id)->findOrFail($request->integer('address_id'));

        if ($request->filled('delivery_slot')) {
            $deliveryDate = (string) $request->input('delivery_date');
            $slot = DeliverySlot::query()->findOrFail($request->integer('delivery_slot'));
        } else {
            $first = $delivery->firstAvailable();

            if ($first === null) {
                return back()->withInput()->with('error_message', 'There are no delivery slots open right now. Please try again later.');
            }

            ['date' => $deliveryDate, 'slot' => $slot] = $first;
        }

        try {
            $order = $placeOrder->handle(
                $request->user(),
                $address,
                (string) $request->input('payment_gateway'),
                $deliveryDate,
                $slot,
                $request->input('notes'),
            );
        } catch (CheckoutException $exception) {
            return redirect()->route('cart')->with('error_message', $exception->getMessage());
        }

        if ($order->payment_gateway === 'COD') {
            $this->sendOrderMail($order);

            return redirect()->route('checkout.thanks');
        }

        return redirect('/paypal');
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $order = Order::query()
            ->with('orders_products')
            ->where('user_id', $request->user()->id)
            ->find(Session::get('order_id'));

        if ($order === null) {
            return redirect()->route('cart');
        }

        return view('front.products.thanks', ['order' => $order, 'meta_title' => 'Order placed']);
    }

    private function sendOrderMail(Order $order): void
    {
        $order->load('orders_products');

        try {
            Mail::send('emails.order', [
                'email' => $order->email,
                'name' => $order->name,
                'order_id' => $order->id,
                'orderDetails' => $order->toArray(),
            ], function ($message) use ($order) {
                $message->to($order->email)->subject('Order Placed - Durian');
            });
        } catch (\Throwable $exception) {
            Log::error('Order mail could not be sent.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
