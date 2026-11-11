<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class AddressController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('front.users.addresses', [
            'deliveryAddresses' => DeliveryAddress::deliveryAddresses(),
            'countries' => Country::where('status', 1)->get()->toArray(),
            'meta_title' => 'Address book',
        ]);
    }

    public function getDeliveryAddress(Request $request): JsonResponse
    {
        $deliveryAddress = DeliveryAddress::query()
            ->where('user_id', Auth::id())
            ->findOrFail($request->integer('addressid'));

        return response()->json([
            'address' => $deliveryAddress->toArray(),
        ]);
    }

    public function saveDeliveryAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delivery_name' => 'required|string|max:100',
            'delivery_address' => 'required|string|max:100',
            'delivery_city' => 'required|string|max:100',
            'delivery_state' => 'required|string|max:100',
            'delivery_country' => 'required|string|max:100',
            'delivery_pincode' => 'required|digits_between:4,6',
            'delivery_mobile' => 'required|numeric|digits_between:10,11',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'type' => 'error',
                'errors' => $validator->messages(),
            ]);
        }

        $data = $request->all();
        $address = [
            'user_id' => Auth::id(),
            'name' => $data['delivery_name'],
            'address' => $data['delivery_address'],
            'city' => $data['delivery_city'],
            'state' => $data['delivery_state'],
            'country' => $data['delivery_country'],
            'pincode' => $data['delivery_pincode'],
            'mobile' => $data['delivery_mobile'],
        ];

        if (! empty($data['delivery_id'])) {
            DeliveryAddress::query()->where('user_id', Auth::id())->findOrFail((int) $data['delivery_id'])->update($address);
        } else {
            DeliveryAddress::create($address);
        }

        return response()->json([
            'type' => 'success',
            'view' => $this->addressList(),
        ]);
    }

    public function removeDeliveryAddress(Request $request): JsonResponse
    {
        DeliveryAddress::query()
            ->where('user_id', Auth::id())
            ->findOrFail($request->integer('addressid'))
            ->delete();

        return response()->json([
            'type' => 'success',
            'view' => $this->addressList(),
        ]);
    }

    private function addressList(): string
    {
        return (string) View::make('front.products.delivery_addresses', [
            'deliveryAddresses' => DeliveryAddress::deliveryAddresses(),
        ]);
    }
}
