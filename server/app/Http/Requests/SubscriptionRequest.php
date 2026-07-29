<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class SubscriptionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        //, 'unique:branches,name'
        return [
            'token_id' => ['nullable', 'string'],
            'authentication_id' => ['nullable', 'string'],

            'plan_code' => ['required', 'string'],
            'billing_interval' => ['required', 'string'],
            'payment_method' => ['nullable', 'string'],
            'payment_type' => ['nullable', 'string'],

            // Agency data
            'agency_id'          => ['nullable'],
            'agency_name'        => ['nullable', 'string', 'required_with:agency_street,agency_city,agency_province,agency_country'],
            'agency_description' => ['nullable', 'string', 'max:1000'],
            'agency_street'      => ['nullable', 'string', 'required_with:agency_name'],
            'agency_city'        => ['nullable', 'string', 'required_with:agency_name'],
            'agency_province'    => ['nullable', 'string', 'required_with:agency_name'],
            'agency_country'     => ['nullable', 'string', 'required_with:agency_name'],

            // Branch data
            'branch_name' => ['required', 'string'],
            'branch_street' => ['required', 'string'],
            'branch_description' => ['required', 'string', 'max:1000'],
            'branch_city' => ['required', 'string'],
            'branch_province' => ['required', 'string'],
            'branch_country' => ['required', 'string'],
            'branch_contact_number' => ['nullable', 'string'],
            'branch_image' => ['nullable', 'file', 'image', 'max:5120'],
            'branch_settings' => ['required', 'array'],
            'branch_settings.currency' => ['required', 'string'],
            'branch_settings.opening' => ['required', 'string'],
            'branch_settings.closing' => ['required', 'string'],
            'branch_settings.time_zone' => ['required', 'string'],
        ];
    }
}
