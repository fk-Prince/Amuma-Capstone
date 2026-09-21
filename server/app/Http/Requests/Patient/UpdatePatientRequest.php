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
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'citizenship' => ['nullable', 'string', 'max:20'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:255'],
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
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
            'avatar.image' => 'The photo must be an image.',
            'avatar.max' => 'The photo must not be larger than 5 MB.',
        ];
    }
}
