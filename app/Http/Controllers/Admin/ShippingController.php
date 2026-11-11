<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Models\ShippingCharge;

class ShippingController extends Controller
{
    public function shippingCharges()
    {

        Session::put('page', 'shipping');

        $shippingCharges = ShippingCharge::get()->toArray();
        return view('admin.shipping.shipping_charges')->with(compact('shippingCharges'));
    }

    public function updateShippingStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if ($data['status'] == 'Active') {
                $status = 0;
            } else {
                $status = 1;
            }
            ShippingCharge::where('id', $data['shipping_id'])->update(['status' => $status]);
            return response()->json([
                'status'      => $status,
                'shipping_id' => $data['shipping_id']
            ]);
        }
    }

    public function addEditShippingCharges(Request $request, $id = null)
    {
        Session::put('page', 'shipping');
        if ($id == '') {
            $title = 'Add Shipping Charges';
            $shippingCharges = new ShippingCharge();

            $message = 'Shipping Charges added successfully!';
        } else {
            $title = 'Edit Shipping Charges';
            $shippingCharges = ShippingCharge::find($id);

            $message = 'Shipping Charges updated successfully!';
        }

        if ($request->isMethod('post')) {
            $data = $request->all();

            $rules = [
                'country' => 'required|regex:/^[\pL\s\-]+$/u',
                'city'    => 'required|regex:/^[\pL\s\-]+$/u',
            ];

            $customMessages = [
                'country.required' => 'Shipping country Name is required',
                'country.regex'    => 'Valid Shipping country Name is required',
                'city.required' => 'Shipping city Name is required',
                'city.regex'    => 'Valid Shipping city Name is required',
            ];

            $this->validate($request, $rules, $customMessages);
            $shippingCharges->country   = $data['country'];
            $shippingCharges->city   = $data['city'];
            $shippingCharges->between_0_500g   = $data['between_0_500g'];
            $shippingCharges->between_501g_1000g   = $data['between_501g_1000g'];
            $shippingCharges->between_1001_2000g   = $data['between_1001_2000g'];
            $shippingCharges->between_2001g_5000g   = $data['between_2001g_5000g'];
            $shippingCharges->above_5000g   = $data['above_5000g'];
            $shippingCharges->status = 1;
            $shippingCharges->save();
            return redirect('admin/shipping-charges')->with('success_message', $message);

            return redirect()->back()->with('success_message', $message);
        }

        return view('admin.shipping.add_edit_shipping_charges')->with(compact('title', 'shippingCharges'));
    }
}
