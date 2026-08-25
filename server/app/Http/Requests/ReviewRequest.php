<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
            'branch_uuid' => ['nullable', 'exists:branches,uuid'],
            'rate' => ['required', 'decimal:2'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
