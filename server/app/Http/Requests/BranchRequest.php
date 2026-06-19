<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
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
            'name'        => ['required', 'string', 'unique:branches,name'],
            'description' => ['required', 'string'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'location.street'     => ['required', 'string'],
            'location.city'       => ['required', 'string'],
            'location.province'   => ['required', 'string'],
            'location.country'    => ['required', 'string'],
            'location.longitude'   => ['nullable', 'numeric'],
            'location.latitude'    => ['nullable', 'numeric'],
        ];
    }
}
