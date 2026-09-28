<?php

namespace App\Http\Requests\Auth;

use App\Rules\CaseInsensitiveUnique;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],

            'email' => [
                'nullable',
                'email',
                'max:255',
                new CaseInsensitiveUnique('users', 'email', $this->user()->user_id, 'user_id'),
            ],

            'phone_number' => ['nullable', 'string', 'max:30'],
            'birth_date'   => ['nullable', 'date', 'before:today'],
            'occupation'   => ['nullable', 'string', 'max:255'],

            'avatar' => ['nullable', 'file', 'image', 'max:5120'],

            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ],

            // Location is a single record shared by whichever profile rows the
            // user has; platform admins have no location column and skip it.
            'street'    => ['nullable', 'string', 'max:255'],
            'city'      => ['nullable', 'string', 'max:255'],
            'province'  => ['nullable', 'string', 'max:255'],
            'country'   => ['nullable', 'string', 'max:255'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required_with' => 'Enter your current password to set a new one.',
            'password.confirmed' => 'The new password confirmation does not match.',
            'password.different' => 'Your new password must differ from the current one.',
            'birth_date.before' => 'Birth date must be in the past.',
        ];
    }
}
