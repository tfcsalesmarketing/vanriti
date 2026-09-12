<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_name' => 'required|string|max:255',
            'shipping_mobile' => ['required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'shipping_address_line1' => 'required|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_landmark' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => 'required|string|max:120',
            'shipping_pincode' => 'required|string|regex:/^[0-9]{6}$/',
            'shipping_country' => 'nullable|string|max:80',
            'billing_same' => 'nullable|in:1',
            'billing_name' => 'exclude_if:billing_same,1|required|string|max:255',
            'billing_mobile' => ['exclude_if:billing_same,1', 'required', 'string', 'max:15', 'regex:/^(?:\+91[\s-]?|0)?[6-9][0-9][\s-]?[0-9]{3}[\s-]?[0-9]{5}$/'],
            'billing_address_line1' => 'exclude_if:billing_same,1|required|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_landmark' => 'nullable|string|max:255',
            'billing_city' => 'exclude_if:billing_same,1|required|string|max:120',
            'billing_state' => 'exclude_if:billing_same,1|required|string|max:120',
            'billing_pincode' => 'exclude_if:billing_same,1|required|string|regex:/^[0-9]{6}$/',
            'billing_country' => 'nullable|string|max:80',
            'shipping_method' => 'required|in:standard,express',
            'payment_method' => 'required|in:cod,razorpay',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_name.required' => 'Please enter the full name.',
            'shipping_mobile.required' => 'Please enter a mobile number.',
            'shipping_mobile.regex' => 'Please enter a valid 10-digit mobile number.',
            'shipping_address_line1.required' => 'Please enter the address.',
            'shipping_city.required' => 'Please enter the city.',
            'shipping_state.required' => 'Please enter the state.',
            'shipping_pincode.required' => 'Please enter a pincode.',
            'shipping_pincode.regex' => 'Please enter a valid 6-digit pincode.',
            'billing_name.required' => 'Please enter the billing full name.',
            'billing_mobile.required' => 'Please enter a billing mobile number.',
            'billing_mobile.regex' => 'Please enter a valid 10-digit billing mobile number.',
            'billing_address_line1.required' => 'Please enter the billing address.',
            'billing_city.required' => 'Please enter the billing city.',
            'billing_state.required' => 'Please enter the billing state.',
            'billing_pincode.required' => 'Please enter a billing pincode.',
            'billing_pincode.regex' => 'Please enter a valid 6-digit billing pincode.',
            'shipping_method.required' => 'Please select a shipping method.',
            'shipping_method.in' => 'Please select a valid shipping method.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Please select a valid payment method.',
        ];
    }
}