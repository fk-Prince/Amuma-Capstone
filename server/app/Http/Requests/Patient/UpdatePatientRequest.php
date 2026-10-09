<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'citizenship' => ['required', 'string', 'max:20'],
            'occupation' => ['required', 'string', 'max:255'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'height' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:300'],
            'weight' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:500'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'address' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'file', 'image', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'gender.required' => 'Gender is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'citizenship.required' => 'Citizenship is required.',
            'occupation.required' => 'Occupation is required.',
            'marital_status.required' => 'Marital status is required.',
            'marital_status.in' => 'Select a valid marital status.',
            'address.required' => 'Address is required.',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
            'avatar.image' => 'The photo must be an image.',
            'avatar.max' => 'The photo must not be larger than 5 MB.',
        ];
    }
}
