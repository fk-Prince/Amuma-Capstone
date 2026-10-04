<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'otp_key' => ['required', 'string'],
            'user.first_name' => ['required', 'string', 'max:255'],
            'user.last_name' => ['required', 'string', 'max:255'],
            'user.email' => ['required', 'email'],
            'user.password' => ['required', 'string', 'min:8'],
            'otp_value' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'otp_key.required' => __('Your verification session has ended. Request a new code.'),
            'otp_value.required' => __('Enter the 6-digit code from your email.'),
            'otp_value.digits' => __('The code must be exactly 6 digits.'),
            'user.first_name.required' => __('Enter your first name.'),
            'user.last_name.required' => __('Enter your last name.'),
            'user.email.required' => __('Enter your email address.'),
            'user.email.email' => __('Enter a valid email address.'),
            'user.password.required' => __('Enter a password.'),
            'user.password.min' => __('Password must be at least 8 characters.'),
        ];
    }
}
