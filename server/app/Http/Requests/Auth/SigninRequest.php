<?php

namespace App\Http\Requests\Auth;

use App\Enums\PortalEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SigninRequest extends FormRequest
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
            'email' => ['required_without:employee_code', 'nullable', 'email'],
            'employee_code' => ['required_without:email', 'nullable', 'string', 'max:50'],
            'password' => ['required'],
            'portal' => ['required', Rule::enum(PortalEnum::class)],
        ];
    }
}
