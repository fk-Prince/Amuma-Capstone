<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CaregiverShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'branch_uuid' => ['required', 'uuid'],
            'admission_id' => [
                $creating ? 'required' : 'sometimes',
                'integer',
                Rule::exists('patient_admissions', 'patient_admission_id'),
            ],
            'caregiver_id' => [
                $creating ? 'required' : 'prohibited',
                'integer',
                Rule::exists('employees', 'employee_id'),
            ],
            'start_time' => [$creating ? 'required' : 'sometimes', 'date_format:H:i'],
            'end_time' => [$creating ? 'required' : 'sometimes', 'date_format:H:i', 'different:start_time'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.date_format' => 'Enter the start time as hours and minutes.',
            'end_time.date_format' => 'Enter the end time as hours and minutes.',
            'end_time.different' => 'The end time must be different from the start time.',
        ];
    }
}
