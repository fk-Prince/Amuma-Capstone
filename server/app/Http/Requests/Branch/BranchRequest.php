<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
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
        $agencyId = Branch::where('uuid', $this->input('branch_uuid'))->value('agency_id');

        return [
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'name'        => [
                'required',
                'string',
                Rule::unique('branches', 'name')
                    ->where('agency_id', $agencyId)
                    ->ignore($this->input('branch_uuid'), 'uuid'),
            ],
            'email'       => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('branches', 'email')->ignore($this->input('branch_uuid'), 'uuid'),
            ],
            'description' => ['required', 'string'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'image' => [
                'nullable',
                Rule::when(
                    $this->hasFile('image'),
                    ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
                    ['string']
                ),
            ],
            'location.street'     => ['required', 'string'],
            'location.city'       => ['required', 'string'],
            'location.province'   => ['required', 'string'],
            'location.country'    => ['required', 'string'],
            'location.longitude'   => ['nullable', 'numeric'],
            'location.full_address' => ['nullable', 'string', 'max:500'],
            'location.latitude'    => ['nullable', 'numeric'],
            // 'settings' => ['required', 'array'],
            // 'settings.currency' => ['required', 'string'],
            // 'settings.opening' => ['required', 'string'],
            // 'settings.closing' => ['required', 'string'],
            // 'settings.time_zone' => ['required', 'string'],
        ];
    }
}
