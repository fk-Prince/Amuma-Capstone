<?php

namespace App\Http\Requests;

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

            // Only sent when adding a branch against existing paid capacity,
            // where it identifies the agency the caller already belongs to.
            'branch_uuid' => ['nullable', 'string', 'exists:branches,uuid'],

            // Which of the agency's subscriptions the new branch should join;
            // ownership is re-checked server-side against the resolved agency.
            'subscription_uuid' => ['nullable', 'string', 'exists:subscriptions,uuid'],

            // Agency data
            'agency_id'          => ['nullable'],
            'agency_name'        => ['nullable', 'string', 'required_with:agency_street,agency_city,agency_province,agency_country,agency_email'],
            'agency_description' => ['nullable', 'string', 'max:1000'],
            'agency_street'      => ['nullable', 'string', 'required_with:agency_name'],
            'agency_city'        => ['nullable', 'string', 'required_with:agency_name'],
            'agency_province'    => ['nullable', 'string', 'required_with:agency_name'],
            'agency_country'     => ['nullable', 'string', 'required_with:agency_name'],
            // Unique only for a *new* agency. When agency_id is supplied the
            // subscriber already has an agency (adding another branch, or any
            // subsequent subscription), and its own email must not count as a
            // collision — that surfaced as "the agency email has already been
            // taken" straight after a subscription succeeded.
            // ignore(null) is a no-op, so new agencies stay fully unique.
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
            'branch_settings.minimum_adl_hours' => ['required', 'integer'],
            'branch_settings.is_open' => ['required', 'boolean'],
        ];
    }
}
