<?php

namespace App\Http\Requests\Patient\Clinical;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'diagnosis' => ['required', 'string', 'max:200'],
            'diagnosis_date' => ['required', 'date'],
            'diagnosis_notes' => ['nullable', 'string', 'max:1000'],
            'diagnosis_file' => [
                'nullable',
                'file',
                'mimes:pdf,png,jpg,jpeg',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'diagnosis.required' => 'Primary Diagnosis is required',
            'diagnosis_date.required' => 'Date Diagnosed is required',
        ];
    }
}
