<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        //'unique:branches,name'
        return [
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'name'        => ['required', 'string'],
            'description' => ['required', 'string'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['file', 'image', 'max:5120'],
                    ['string']
                ),
            ],
            'location.street'     => ['required', 'string'],
            'location.city'       => ['required', 'string'],
            'location.province'   => ['required', 'string'],
            'location.country'    => ['required', 'string'],
            'location.longitude'   => ['nullable', 'numeric'],
            'location.latitude'    => ['nullable', 'numeric'],
            // 'settings' => ['required', 'array'],
            // 'settings.currency' => ['required', 'string'],
            // 'settings.opening' => ['required', 'string'],
            // 'settings.closing' => ['required', 'string'],
            // 'settings.time_zone' => ['required', 'string'],
        ];
    }
}
