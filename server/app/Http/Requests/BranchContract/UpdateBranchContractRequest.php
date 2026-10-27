<?php

namespace App\Http\Requests\BranchContract;

use App\Rules\NoEmoji;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchContractRequest extends FormRequest
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
        $id = $this->route('id');
        return [
            'branch_contract_id' => ['required', 'string', Rule::unique('branch_contracts', 'branch_contract_id')->ignore($id, 'id')],
            'branch_uuid' => ['nullable', 'string', 'exists:branches,uuid'],
            'category' => ['required',  Rule::in(['Homecare', 'Facility'])],
            'accommodation_type' => ['required', Rule::in(['ADL', 'VIP', 'COMMON'])],
            'price' => ['required',  'numeric',  'min:1'],
            'billing_cycle' => ['required',   Rule::in(['MONTHLY', 'YEARLY', 'OPEN', 'HOURLY'])],
            'description' => ['nullable', 'string',  'max:1000'],
            'activities' => ['required_if:accommodation_type,ADL', 'nullable', 'array', 'min:1'],
            'activities.*' => ['required', 'string', 'max:60', 'distinct:ignore_case', new NoEmoji()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'activities.required_if' => 'Select at least one activity.',
            'activities.min' => 'Select at least one activity.',
            'activities.*.distinct' => 'Each activity can only be added once.',
            'activities.*.max' => 'Activity must be 60 characters or fewer.',
        ];
    }
}
