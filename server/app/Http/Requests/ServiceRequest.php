<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'exists:branches,uuid'],

            'category_id' => [
                'required_without:category_name',
                'nullable',
                'exists:categories,category_id',
                'prohibits:category_name',
            ],

            'category_name' => [
                'required_without:category_id',
                'nullable',
                'string',
                'max:255',
                'prohibits:category_id',
            ],
            'price' => ['required', 'decimal:2'],

            'service_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'service_name')
                    ->where('branch_id', $this->input('branch_id')),
            ],

            'maximum_duration' => ['required'],
            'is_available' => ['boolean'],
            'type' => ['required', 'in:online,facility,both'],
        ];
    }
}
