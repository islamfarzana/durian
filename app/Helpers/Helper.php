<?php

use App\Models\Cart;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

function totalCartItems()
{
    if (Auth::check()) {
        $user_id = Auth::user()->id;
        $totalCartItems = Cart::where('user_id', $user_id)->sum('quantity');
    } else {
        $session_id = Session::get('session_id');
        $totalCartItems = Cart::where('session_id', $session_id)->sum('quantity');
    }

    return $totalCartItems;
}
function getCartItems()
{

    if (Auth::check()) {
        $getCartItems = Cart::with([
            'product' => function ($query) {
                $query->select('id', 'category_id', 'product_name', 'product_code', 'product_color', 'product_image');
            },
        ])->orderBy('id', 'Desc')->where([
            'user_id' => Auth::user()->id,

        ])->get()->toArray();
    } else {
        $getCartItems = Cart::with([
            'product' => function ($query) {
                $query->select('id', 'category_id', 'product_name', 'product_code', 'product_color', 'product_image');
            },
        ])->orderBy('id', 'Desc')->where([

            'session_id' => Session::get('session_id'),
        ])->get()->toArray();
    }

    return $getCartItems;
}

/**
 * Amount in the marketplace currency, without decimals when there are none.
 */
function formatMoney(float|int|string|null $amount): string
{
    $amount = round((float) $amount, 2);
    $decimals = floor(abs($amount)) == abs($amount) ? 0 : 2;

    return ($amount < 0 ? '-' : '').Setting::get('currency_symbol').number_format(abs($amount), $decimals);
}
