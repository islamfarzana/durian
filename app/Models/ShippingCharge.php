<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCharge extends Model
{
    use HasFactory;

    public static function getShippingCharges($total_weight, $country)
    {
        $shippingDetails = ShippingCharge::where('country', $country)->first();

        if ($shippingDetails === null) {
            return 0;
        }

        $shippingDetails = $shippingDetails->toArray();
        if ($total_weight > 0) {

            if ($total_weight > 0 && $total_weight <= 500) {
                $rate = $shippingDetails['between_0_500g'];
            } elseif ($total_weight > 500 && $total_weight <= 1000) {
                $rate = $shippingDetails['between_501g_1000g'];
            } elseif ($total_weight > 1000 && $total_weight <= 2000) {
                $rate = $shippingDetails['between_1001_2000g'];
            } elseif ($total_weight > 2000 && $total_weight <= 5000) {
                $rate = $shippingDetails['between_2001g_5000g'];
            } elseif ($total_weight > 5000) {
                $rate = $shippingDetails['above_5000g'];
            } else {
                $rate = 0;
            }
        } else {
            $rate = 0;
        }

        return $rate;
    }
}
