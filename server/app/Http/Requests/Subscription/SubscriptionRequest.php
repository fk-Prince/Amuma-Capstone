<?php

namespace App\Http\Requests;

use App\Rules\ValidTin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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

            'branch_uuid' => ['nullable', 'string', 'exists:branches,uuid'],

            'subscription_uuid' => ['nullable', 'string', 'exists:subscriptions,uuid'],

            // Agency data
            'agency_id'          => ['nullable'],
            'agency_name'        => ['nullable', 'string', 'required_with:agency_street,agency_city,agency_province,agency_country,agency_email'],
            'agency_description' => ['nullable', 'string', 'max:1000'],
            'agency_street'      => ['nullable', 'string', 'required_with:agency_name'],
            'agency_city'        => ['nullable', 'string', 'required_with:agency_name'],
            'agency_province'    => ['nullable', 'string', 'required_with:agency_name'],
            'agency_country'     => ['nullable', 'string', 'required_with:agency_name'],
            'agency_email'       => [
                'nullable',
                'string',
                'required_with:agency_name',
                Rule::unique('agencies', 'email')
                    ->ignore($this->input('agency_id'), 'agency_id'),
            ],
            'agency_image'       => ['nullable', 'file', 'image', 'max:5120'],
            'agency_id_front'    => ['nullable', 'file', 'image', 'max:5120', 'required_with:agency_name'],
            'agency_id_back'     => ['nullable', 'file', 'image', 'max:5120', 'required_with:agency_name'],
            'agency_document'    => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120', 'required_with:agency_name'],

            // Branch data
            'branch_name' => ['required', 'string'],
            'branch_street' => ['required', 'string'],
            'branch_description' => ['required', 'string', 'max:1000'],
            'branch_city' => ['required', 'string'],
            'branch_province' => ['required', 'string'],
            'branch_country' => ['required', 'string'],
            'branch_email' => ['required', 'string', 'unique:branches,email'],
            'branch_contact_number' => ['required', 'string'],
            'branch_image' => ['nullable', 'file', 'image', 'max:5120'],
            'branch_document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
            'branch_settings' => ['required', 'array'],
            'branch_settings.currency' => ['required', 'string'],
            'branch_settings.opening' => ['required', 'string'],
            'branch_settings.closing' => ['required', 'string'],
            'branch_settings.time_zone' => ['required', 'string'],
            'branch_settings.reserved_walkin_slots' => ['required', 'integer'],
            'branch_settings.enable_booking_pre_admission' => ['required', 'boolean'],
            'branch_settings.enable_booking_complete_admission' => ['required', 'boolean'],
            'branch_settings.requires_full_payment_on_admit' => ['nullable', 'boolean'],
            'branch_settings.complete_admission_booking_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'branch_settings.minimum_adl_hours' => ['required', 'integer'],
            'branch_settings.tin' => ['required', 'string', new ValidTin()],
            'branch_settings.is_open' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_settings.tin' => 'TIN',
            'branch_settings.currency' => 'currency',
            'branch_settings.opening' => 'opening time',
            'branch_settings.closing' => 'closing time',
            'branch_settings.time_zone' => 'time zone',
            'branch_settings.reserved_walkin_slots' => 'reserved walk-in slots',
            'branch_settings.minimum_adl_hours' => 'minimum ADL hours',
            'branch_settings.enable_booking_pre_admission' => 'pre-admission booking',
            'branch_settings.enable_booking_complete_admission' => 'complete-admission booking',
            'branch_settings.requires_full_payment_on_admit' => 'full payment on admit',
            'branch_settings.complete_admission_booking_percent' => 'booking percentage',
            'branch_settings.is_open' => 'branch availability',
        ];
    }

}
