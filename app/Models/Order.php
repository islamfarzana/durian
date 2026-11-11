<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Statuses in which the customer may still cancel a cash-on-delivery order.
     *
     * @var array<int, string>
     */
    public const CANCELLABLE_STATUSES = ['New', 'Order Placed', 'Pending'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'grand_total' => 'float',
            'shipping_charges' => 'float',
            'coupon_amount' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrdersLog::class);
    }

    public function isCancellable(): bool
    {
        return $this->payment_method === 'COD' && in_array($this->order_status, self::CANCELLABLE_STATUSES, true);
    }

    public function orders_products()
    {
        return $this->hasMany('App\Models\OrdersProduct', 'order_id');
    }

    public function order_items()
    {
        return $this->hasMany('App\Models\OrdersProduct', 'order_id');
    }

    public static function pushOrder($order_id)
    {
        $orderDetails = Order::with('order_items')->where('id', $order_id)->first()->toArray();
        $orderDetails['order_id'] = $orderDetails['id'];
        $orderDetails['order_date'] = $orderDetails['created_at'];
        $orderDetails['pickup_location'] = 'Test';
        $orderDetails['channel_id'] = '1855855';
        $orderDetails['comment'] = 'Test Order';
        $orderDetails['billing_customer_name'] = $orderDetails['name'];
        $orderDetails['billing_last_name'] = '';
        $orderDetails['billing_address'] = $orderDetails['address'];
        $orderDetails['billing_address_2'] = '';
        $orderDetails['billing_city'] = $orderDetails['city'];
        $orderDetails['billing_pincode'] = $orderDetails['pincode'];
        $orderDetails['billing_state'] = $orderDetails['state'];
        $orderDetails['billing_country'] = $orderDetails['country'];
        $orderDetails['billing_email'] = $orderDetails['email'];
        $orderDetails['billing_phone'] = (int) $orderDetails['mobile'];

        $orderDetails['shipping_is_billing'] = true;

        $orderDetails['shipping_customer_name'] = $orderDetails['name'];
        $orderDetails['shipping_last_name'] = '';
        $orderDetails['shipping_address'] = $orderDetails['address'];
        $orderDetails['shipping_address_2'] = '';
        $orderDetails['shipping_city'] = $orderDetails['city'];
        $orderDetails['shipping_pincode'] = $orderDetails['pincode'];
        $orderDetails['shipping_state'] = $orderDetails['state'];
        $orderDetails['shipping_country'] = $orderDetails['country'];
        $orderDetails['shipping_email'] = $orderDetails['email'];
        $orderDetails['shipping_phone'] = (int) $orderDetails['mobile'];

        foreach ($orderDetails['order_items'] as $key => $item) {
            $orderDetails['order_items'][$key]['name'] = $item['product_name'];
            $orderDetails['order_items'][$key]['sku'] = $item['product_code'];
            $orderDetails['order_items'][$key]['units'] = $item['product_qty'];
            $orderDetails['order_items'][$key]['selling_price'] = $item['product_price'];
            $orderDetails['order_items'][$key]['discount'] = '';
            $orderDetails['order_items'][$key]['tax'] = '';
            $orderDetails['order_items'][$key]['hsn'] = '';
        }
        $orderDetails['shipping_charges'] = 0;
        $orderDetails['giftwrap_charges'] = 0;
        $orderDetails['transaction_charges'] = 0;
        $orderDetails['total_discount'] = 0;
        $orderDetails['sub_total'] = $orderDetails['grand_total'];
        $orderDetails['length'] = 1;
        $orderDetails['breadth'] = 1;
        $orderDetails['height'] = 1;
        $orderDetails['weight'] = 1;
        $orderDetails = json_encode($orderDetails);
        $c = curl_init();
        $url = 'https://apiv2.shiprocket.in/v1/external/auth/login';
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query(['email' => config('services.shiprocket.email'), 'password' => config('services.shiprocket.password')]));
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $server_output = curl_exec($c);

        /*
            Server's JSON Response Example:

            "{
                "id":1621230,"first_name":"API","last_name":"USER","email":"stackdevelopers2@gmail.com","company_id":1595375,"created_at":"2021-07-06 20:56:12","token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjE2MjEyMzAsImlzcyI6Imh0dHBzOi8vYXBpdjIuc2hpcHJvY2tldC5pbi92MS9leHRlcm5hbC9hdXRoL2xvZ2luIiwiaWF0IjoxNjgxNjcyOTcwLCJleHAiOjE2ODI1MzY5NzAsIm5iZiI6MTY4MTY3Mjk3MCwianRpIjoiZlpSazV0MFpneEQ4RzNTZSJ9.rOwcxnZzfsEg0pCuSNmKAV_aPVcMm2ohSjBWJhUIk5I"
            }"
        */

        curl_close($c);
        $server_output = json_decode($server_output, true);

        /*
            Server's PHP array Response Example:

            [
                "id"         => 1621230,
                "first_name" => "API",
                "last_name"  => "USER",
                "email"      => "stackdevelopers2@gmail.com",
                "company_id" => 1595375,
                "created_at" => "2021-07-06 20:56:12",
                "token"      => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjE2MjEyMzAsImlzcyI6Imh0dHBzOi8vYXBpdjIuc2hpcHJvY2tldC5pbi92MS9leHRlcm5hbC9hdXRoL2xvZ2luIiwiaWF0IjoxNjgxNjczNjY2LCJ"
            ]
        */
        $url = 'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc';
        $c = curl_init($url);

        curl_setopt($c, CURLOPT_POST, 1);
        curl_setopt($c, CURLOPT_POSTFIELDS, $orderDetails);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$server_output['token'].'',
        ]);

        $result = curl_exec($c);

        /*
            Server's JSON Response Example:

            "{
                "order_id":335443365,"shipment_id":334820645,"status":"NEW","status_code":1,"onboarding_completed_now":0,"awb_code":"","courier_company_id":"","courier_name""
            }"
        */

        curl_close($c);
        $result = json_decode($result, true);

        /*
            Server's PHP array Response Example:

            [
                "order_id"                 => 335443365,
                "shipment_id"              => 334820645,
                "status"                   => "NEW",
                "status_code"              => 1,
                "onboarding_completed_now" => 0,
                "awb_code"                 => "",
                "courier_company_id"       => "",
                "courier_name"             => ""
            ]
        */
        if (isset($result['status_code']) && $result['status_code'] == 1) {
            Order::where('id', $order_id)->update(['is_pushed' => 1]);

            $status = true;
            $message = 'Order successfully pushed to ShipRocket';
        } else {
            $status = false;
            $message = 'Order has not been pushed to ShipRocket. Please contact Admin';
        }

        return [
            'status' => $status,
            'message' => $message,
        ];
    }
}
