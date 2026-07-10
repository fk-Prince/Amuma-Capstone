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
            ],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'service_name' => [
                'required',
                'string',
                'max:255',
            ],

            'maximum_duration' => [
                'required',
                'date_format:H:i:s',
            ],
            'is_available' => ['boolean'],
            'type' => ['required', 'in:online,facility,both'],
        ];
    }
}
