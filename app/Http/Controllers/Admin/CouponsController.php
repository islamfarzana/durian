<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CouponsController extends Controller
{
    public function coupons()
    {

        Session::put('page', 'coupons');
        $adminType = Auth::guard('admin')->user()->type;

        if ($adminType == 'vendor') {
            $vendorStatus = Auth::guard('admin')->user()->status;

            if ($vendorStatus == 0) {
                return redirect('admin/update-vendor-details/personal')->with('error_message', 'Your Vendor Account is not approved yet. Please make sure to fill your valid personal, business and bank details');
            }
        }

        $coupons = $this->visibleCoupons()->get()->toArray();

        return view('admin.coupons.coupons')->with(compact('coupons'));
    }

    public function updateCouponStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            $this->visibleCoupons()->whereKey($data['coupon_id'])->firstOrFail()->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'coupon_id' => $data['coupon_id'],
            ]);
        }
    }

    public function deleteCoupon($id)
    {
        $this->visibleCoupons()->whereKey($id)->firstOrFail()->delete();

        $message = 'Coupon has been deleted successfully!';

        return redirect()->back()->with('success_message', $message);
    }

    public function addEditCoupon(Request $request, $id = null)
    {

        Session::put('page', 'coupons');
        if ($id == '') {

            $title = 'Add Coupon';
            $coupon = new Coupon;
            $selCats = [];
            $selBrands = [];
            $selUsers = [];

            $message = 'Coupon added successfully!';
        } else {

            $title = 'Edit Coupon';
            $coupon = $this->visibleCoupons()->whereKey($id)->firstOrFail();
            $selCats = explode(',', (string) $coupon['categories']);
            $selBrands = explode(',', (string) $coupon['brands']);
            $selUsers = explode(',', (string) $coupon['users']);

            $message = 'Coupon updated successfully!';
        }
        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'coupon_option' => 'required',
                'coupon_code' => [Rule::requiredIf(($data['coupon_option'] ?? '') === 'Manual'), 'nullable', 'alpha_dash', 'max:40', Rule::unique('coupons', 'coupon_code')->ignore($coupon->id)],
                'title' => 'nullable|string|max:120',
                'coupon_type' => 'required',
                'amount_type' => 'required',
                'amount' => 'required|numeric|min:0.01',
                'min_spend' => 'nullable|numeric|min:0',
                'max_discount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expiry_date' => 'required|date'.(empty($data['starts_at']) ? '' : '|after_or_equal:starts_at'),
            ];

            $customMessages = [
                'coupon_option.required' => 'Select Coupon Option',
                'coupon_code.required' => 'Enter a Coupon Code',
                'coupon_code.unique' => 'That Coupon Code is already in use',
                'coupon_type.required' => 'Select Coupon Type',
                'amount_type.required' => 'Select Amount Type',
                'amount.required' => 'Enter Amount',
                'amount.numeric' => 'Enter Valid Amount',
                'expiry_date.required' => 'Enter Expiry Date',
            ];

            $this->validate($request, $rules, $customMessages);
            $categories = isset($data['categories']) ? implode(',', $data['categories']) : '';
            $brands = isset($data['brands']) ? implode(',', $data['brands']) : '';
            $users = isset($data['users']) ? implode(',', $data['users']) : '';

            if (! empty($coupon->coupon_code)) {
                $coupon_code = $coupon->coupon_code;
            } elseif ($data['coupon_option'] == 'Automatic') {
                $coupon_code = strtoupper(Str::random(8));
            } else {
                $coupon_code = $data['coupon_code'];
            }
            $adminType = Auth::guard('admin')->user()->type;
            if ($adminType == 'vendor') {
                $coupon->vendor_id = Auth::guard('admin')->user()->vendor_id;
            } elseif (! $coupon->exists) {
                $coupon->vendor_id = 0;
            }
            $coupon->coupon_option = $data['coupon_option'];
            $coupon->coupon_code = $coupon_code;
            $coupon->title = $data['title'] ?? null;
            $coupon->categories = $categories;
            $coupon->brands = $brands;
            $coupon->users = $users;
            $coupon->coupon_type = $data['coupon_type'];
            $coupon->amount_type = $data['amount_type'];
            $coupon->amount = $data['amount'];
            $coupon->min_spend = $data['min_spend'] ?? 0;
            $coupon->max_discount = $data['max_discount'] ?? null;
            $coupon->usage_limit = $data['usage_limit'] ?? null;
            $coupon->is_public = $request->boolean('is_public');
            $coupon->starts_at = $data['starts_at'] ?? null;
            $coupon->expiry_date = $data['expiry_date'];
            $coupon->status = 1;

            $coupon->save();

            return redirect('admin/coupons')->with('success_message', $message);
        }

        $categories = Section::with('categories')->get()->toArray();
        $brands = Brand::where('status', 1)->get()->toArray();
        $users = User::select('email')->where('status', 1)->get();

        return view('admin.coupons.add_edit_coupon')->with(compact('title', 'coupon', 'categories', 'brands', 'users', 'selCats', 'selBrands', 'selUsers'));
    }

    /**
     * Vendors manage only their own vouchers; marketplace admins manage all of them.
     *
     * @return Builder<Coupon>
     */
    private function visibleCoupons(): Builder
    {
        $admin = Auth::guard('admin')->user();

        return Coupon::query()->when($admin->type === 'vendor', fn (Builder $query) => $query->where('vendor_id', $admin->vendor_id));
    }
}
