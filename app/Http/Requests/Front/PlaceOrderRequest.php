<?php

namespace App\Http\Requests\Front;

use App\Models\Setting;
use App\Services\Delivery\DeliveryOptions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Payment methods the storefront offers right now.
     *
     * @return array<string, string>
     */
    public static function paymentGateways(): array
    {
        return array_filter([
            'COD' => Setting::enabled('cod_enabled') ? 'Cash on delivery' : null,
            'Paypal' => filled(config('services.paypal.client_id')) ? 'PayPal or card' : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', Rule::exists('delivery_addresses', 'id')->where('user_id', $this->user()->id)],
            'payment_gateway' => ['required', Rule::in(array_keys(self::paymentGateways()))],
            'accept' => ['required'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d', 'required_with:delivery_slot'],
            'delivery_slot' => ['nullable', 'integer', 'required_with:delivery_date', Rule::exists('delivery_slots', 'id')->where('status', 1)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address_id.required' => 'Please choose a delivery address.',
            'address_id.exists' => 'Please choose a delivery address.',
            'payment_gateway.required' => 'Please choose a payment method.',
            'payment_gateway.in' => 'Please choose a payment method.',
            'accept.required' => 'Please agree to the terms and conditions.',
            'delivery_slot.exists' => 'Please choose a delivery slot.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['delivery_date', 'delivery_slot']) || ! $this->filled('delivery_slot')) {
                    return;
                }

                if (! app(DeliveryOptions::class)->isAvailable((string) $this->input('delivery_date'), $this->integer('delivery_slot'))) {
                    $validator->errors()->add('delivery_slot', 'That delivery slot is no longer available. Please pick another one.');
                }
            },
        ];
    }
}
