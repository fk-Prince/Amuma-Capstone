<?php

namespace App\Http\Requests\Agency;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgencyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = Branch::where('uuid', $this->input('branch_uuid'))->value('agency_id');

        return [
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'agency_name' => [
                'sometimes',
                'string',
                Rule::unique('agencies', 'name')->ignore($agencyId, 'agency_id'),
            ],
            'agency_email' => [
                'sometimes',
                'string',
                Rule::unique('agencies', 'email')->ignore($agencyId, 'agency_id'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'agency_name' => 'agency name',
            'agency_email' => 'agency email',
        ];
    }
}
