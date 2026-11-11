<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryAreaController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'city' => ['required', 'string', Rule::exists((new City)->getTable(), 'city_name')->where('status', 1)],
        ], [
            'city.exists' => 'We do not deliver to that city yet.',
        ]);

        $request->session()->put(DeliveryOptions::SESSION_CITY, $validated['city']);

        return back()->with('success_message', 'Showing delivery options for '.$validated['city'].'.');
    }
}
