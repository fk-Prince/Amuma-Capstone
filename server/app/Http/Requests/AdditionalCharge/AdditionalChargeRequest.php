<?php

namespace App\Http\Requests\AdditionalCharge;

use App\Models\AdditionalCharge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdditionalChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'patient_uuid' => ['required', 'uuid'],
            'charges' => ['required', 'array', 'min:1', 'max:50'],
            'charges.*.type' => ['required', Rule::in(AdditionalCharge::TYPES)],
            'charges.*.diagnosis_case_uuid' => [
                'nullable',
                'uuid',
                'required_if:charges.*.type,' . AdditionalCharge::TYPE_DIAGNOSIS_CASE,
            ],
            'charges.*.patient_diagnosis_uuid' => ['nullable', 'uuid'],
            'charges.*.new_diagnosis' => ['nullable', 'array'],
            'charges.*.new_diagnosis.diagnosis' => ['required_with:charges.*.new_diagnosis', 'string', 'max:200'],
            'charges.*.new_diagnosis.diagnosis_date' => ['required_with:charges.*.new_diagnosis', 'date', 'before_or_equal:today'],
            'charges.*.new_diagnosis.diagnosis_notes' => ['nullable', 'string', 'max:1000'],
            'charges.*.new_diagnosis.diagnosis_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'charges.*.description' => ['required', 'string', 'max:255'],
            'charges.*.amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'charges.*.type' => 'type',
            'charges.*.diagnosis_case_uuid' => 'diagnosis case',
            'charges.*.patient_diagnosis_uuid' => 'diagnosis',
            'charges.*.new_diagnosis.diagnosis' => 'diagnosis',
            'charges.*.new_diagnosis.diagnosis_date' => 'date diagnosed',
            'charges.*.new_diagnosis.diagnosis_notes' => 'diagnosis notes',
            'charges.*.new_diagnosis.diagnosis_file' => 'supporting document',
            'charges.*.description' => 'description',
            'charges.*.amount' => 'amount',
        ];
    }
}
