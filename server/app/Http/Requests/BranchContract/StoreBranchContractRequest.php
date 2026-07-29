<?php

namespace App\Http\Requests\BranchContract;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchContractRequest extends FormRequest
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
            'branch_uuid' => ['nullable', 'string', 'exists:branches,uuid'],
            'category' => ['required',  Rule::in(['Homecare', 'Facility'])],
            'accommodation_type' => ['required', Rule::in(['ADL', 'VIP', 'COMMON'])],
            'price' => ['required',  'numeric',  'min:1'],
            'billing_cycle' => ['required',   Rule::in(['MONTHLY', 'YEARLY', 'OPEN', 'HOURLY'])],
            'description' => ['nullable', 'string',  'max:500'],
        ];
    }
}
