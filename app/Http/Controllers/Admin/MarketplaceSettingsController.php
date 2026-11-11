<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class MarketplaceSettingsController extends Controller
{
    public function edit(): View
    {
        Session::put('page', 'marketplace_settings');

        return view('admin.settings.marketplace', [
            'settings' => collect(Setting::DEFAULTS)->mapWithKeys(fn (string $default, string $key): array => [$key => Setting::get($key)])->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'hotline' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:120'],
            'free_delivery_threshold' => ['required', 'numeric', 'min:0'],
            'default_delivery_fee' => ['required', 'numeric', 'min:0'],
            'delivery_days_ahead' => ['required', 'integer', 'between:1,14'],
            'slot_lead_minutes' => ['required', 'integer', 'between:0,1440'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'min_payout_amount' => ['required', 'numeric', 'min:0'],
        ]);

        Setting::store($validated + ['cod_enabled' => $request->boolean('cod_enabled') ? '1' : '0']);

        return back()->with('success_message', 'Marketplace settings saved.');
    }
}
