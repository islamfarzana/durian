<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Services\Vendors\VendorLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayoutController extends Controller
{
    /**
     * Vendors see their earnings statement; admins see every payout request.
     */
    public function index(VendorLedger $ledger): View
    {
        Session::put('page', 'payouts');
        $admin = Auth::guard('admin')->user();

        if ($admin->type === 'vendor') {
            $vendor = Vendor::query()->with('bankDetails')->findOrFail($admin->vendor_id);

            return view('admin.payouts.earnings', [
                'vendor' => $vendor,
                'summary' => $ledger->summary($vendor),
                'statement' => $ledger->statement($vendor),
                'payouts' => $vendor->payouts()->latest()->get(),
                'minimumPayout' => Setting::number('min_payout_amount'),
            ]);
        }

        $vendors = Vendor::query()->with('businessDetails:id,vendor_id,shop_name')->where('status', 1)->orderBy('id')->get();

        return view('admin.payouts.payouts', [
            'payouts' => VendorPayout::query()
                ->with('vendor.businessDetails:id,vendor_id,shop_name')
                ->orderByRaw("CASE WHEN status = 'requested' THEN 0 ELSE 1 END")
                ->latest()
                ->get(),
            'balances' => $vendors->map(fn (Vendor $vendor): array => ['vendor' => $vendor] + $ledger->summary($vendor)),
        ]);
    }

    public function store(Request $request, VendorLedger $ledger): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $vendor = Vendor::query()->with('bankDetails')->findOrFail($admin->vendor_id);

        if ($vendor->bankDetails === null || blank($vendor->bankDetails->account_number)) {
            return back()->with('error_message', 'Add your bank details before requesting a payout.');
        }

        $payout = DB::transaction(function () use ($request, $vendor, $ledger): VendorPayout {
            Vendor::query()->whereKey($vendor->id)->lockForUpdate()->first();
            $available = $ledger->summary($vendor)['available'];

            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'min:'.Setting::number('min_payout_amount'), 'max:'.$available],
            ], [
                'amount.min' => 'The minimum payout is '.formatMoney(Setting::number('min_payout_amount')).'.',
                'amount.max' => 'You can request up to '.formatMoney($available).' right now.',
            ]);

            $bank = $vendor->bankDetails;

            return $vendor->payouts()->create([
                'amount' => round((float) $validated['amount'], 2),
                'status' => VendorPayout::REQUESTED,
                'account_details' => $bank->account_holder_name.' · '.$bank->bank_name.' · '.$bank->account_number.($bank->bank_ifsc_code ? ' · '.$bank->bank_ifsc_code : ''),
            ]);
        });

        return back()->with('success_message', 'Payout of '.formatMoney($payout->amount).' requested. We will notify you once it is paid.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $payout = VendorPayout::query()->where('status', VendorPayout::REQUESTED)->findOrFail($id);
        $validated = $request->validate([
            'action' => ['required', Rule::in([VendorPayout::PAID, VendorPayout::REJECTED])],
            'reference' => ['required_if:action,paid', 'nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'reference.required_if' => 'Enter the bank transfer reference for a paid payout.',
        ]);

        $payout->update([
            'status' => $validated['action'],
            'reference' => $validated['reference'] ?? null,
            'note' => $validated['note'] ?? null,
            'processed_at' => now(),
        ]);

        return back()->with('success_message', 'Payout #'.$payout->id.' marked as '.$validated['action'].'.');
    }
}
