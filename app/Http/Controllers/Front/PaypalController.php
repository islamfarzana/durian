<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductsAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Omnipay\Omnipay;

class PaypalController extends Controller
{
    private $gateway;

    public function __construct()
    {

        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(config('services.paypal.client_id'));
        $this->gateway->setSecret(config('services.paypal.secret'));
        $this->gateway->setTestMode((bool) config('services.paypal.test_mode'));
    }

    public function pay(Request $request)
    {
        try {
            $paypal_amount = round(Session::get('grand_total') / 80, 2);
            $response = $this->gateway->purchase([

                'amount' => $paypal_amount,
                'currency' => config('services.paypal.currency'),
                'returnUrl' => url('success'),
                'cancelUrl' => url('error'),
            ])->send();
            if ($response->isRedirect()) {

                $response->redirect();
            } else {

                return $response->getMessage();
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function success(Request $request)
    {
        if (! Session::has('order_id')) {
            return redirect()->route('cart');
        }
        if ($request->input('paymentId') && $request->input('PayerID')) {
            $transaction = $this->gateway->completePurchase([
                'payer_id' => $request->input('PayerID'),
                'transactionReference' => $request->input('paymentId'),
            ]);

            $response = $transaction->send();

            if ($response->isSuccessful()) {
                $arr = $response->getData();
                $payment = new Payment;

                $payment->order_id = Session::get('order_id');
                $payment->user_id = Auth::user()->id;
                $payment->payment_id = $arr['id'];
                $payment->payer_id = $arr['payer']['payer_info']['payer_id'];
                $payment->payer_email = $arr['payer']['payer_info']['email'];
                $payment->amount = $arr['transactions'][0]['amount']['total'];
                $payment->currency = config('services.paypal.currency');
                $payment->payment_status = $arr['state'];

                $payment->save();
                $orderId = Session::get('order_id');
                Order::where('id', $orderId)->update(['order_status' => 'Paid']);
                $order = Order::with('orders_products')->find($orderId);
                $email = Auth::user()->email;

                try {
                    Mail::send('emails.order', [
                        'email' => $email,
                        'name' => Auth::user()->name,
                        'order_id' => $orderId,
                        'orderDetails' => $order?->toArray() ?? [],
                    ], function ($message) use ($email) {
                        $message->to($email)->subject('Order Paid through PayPal - Durian');
                    });
                } catch (\Throwable $exception) {
                    Log::error('PayPal order mail could not be sent.', [
                        'order_id' => $orderId,
                        'message' => $exception->getMessage(),
                    ]);
                }

                return view('front.paypal.success');
            } else {

                return $response->getMessage();
            }
        } else {
            return 'Payment Declined!';
        }
    }

    public function error()
    {
        $this->releasePendingPaypalStock();

        return view('front.paypal.fail');
    }

    private function releasePendingPaypalStock(): void
    {
        $orderId = Session::get('order_id');

        if ($orderId === null) {
            return;
        }

        $order = Order::with('orders_products')->find($orderId);

        if ($order === null || $order->order_status !== 'Pending' || $order->payment_gateway !== 'Paypal') {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orders_products as $line) {
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

            $order->order_status = 'Cancelled';
            $order->save();
        });

        Session::forget('order_id');
    }

    public function paypal()
    {
        if (Session::has('order_id')) {
            return view('front.paypal.paypal');
        } else {
            return redirect('cart');
        }
    }
}
