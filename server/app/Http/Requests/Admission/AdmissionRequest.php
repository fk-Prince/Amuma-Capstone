<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\PatientAdmission;

class AdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => [
                'required',
                'uuid',
            ],

            'p_uuid' => [
                'required',
                'uuid',
            ],

            'admission_id' => [
                'required',
                'integer',
                Rule::exists('patient_admissions', 'patient_admission_id'),
            ],
            'contract' => [
                'nullable',
                'required_if:action,extend',
            ],
            'action' => [
                'required',
                Rule::in([
                    'admit',
                    'cancel',
                    'discharge',
                    'change-room',
                    'contract',
                    'extend'
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'branch_uuid.required' => 'Branch is required.',
            'branch_uuid.uuid' => 'Invalid branch UUID.',
            'p_uuid.required' => 'Patient is required.',
            'p_uuid.uuid' => 'Invalid patient UUID.',
            'admission_id.exists' => 'Admission record not found.',
            'action.in' => 'Invalid admission action.',
        ];
    }
}
