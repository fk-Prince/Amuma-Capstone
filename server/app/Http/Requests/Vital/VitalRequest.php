<?php

namespace App\Http\Requests\Vital;

use Illuminate\Foundation\Http\FormRequest;

class VitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
            'payload.bloodPressureSystolic' => ['required', 'numeric', 'between:40,260'],
            'payload.bloodPressureDiastolic' => ['required', 'numeric', 'between:20,200'],
            'payload.heartRate' => ['required', 'numeric', 'between:20,250'],
            'payload.respiratoryRate' => ['required', 'numeric', 'between:4,60'],
            'payload.temperature' => ['required', 'numeric', 'between:30,43'],
            'payload.oxygenSaturation' => ['required', 'numeric', 'between:50,100'],
            'payload.bloodGlucose' => ['required', 'numeric', 'between:20,800'],
            'payload.painLevel' => ['required', 'integer', 'between:0,10'],
            'payload.recordedDate' => ['required', 'date'],
            'payload.recordedTime' => ['required', 'string'],
            'payload.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.bloodPressureSystolic.required' => 'Systolic is required.',
            'payload.bloodPressureSystolic.between' => 'Systolic must be between 40 and 260.',
            'payload.bloodPressureDiastolic.required' => 'Diastolic is required.',
            'payload.bloodPressureDiastolic.between' => 'Diastolic must be between 20 and 200.',
            'payload.heartRate.required' => 'Heart rate is required.',
            'payload.heartRate.between' => 'Heart rate must be between 20 and 250 bpm.',
            'payload.respiratoryRate.required' => 'Respiratory rate is required.',
            'payload.respiratoryRate.between' => 'Respiratory rate must be between 4 and 60.',
            'payload.temperature.required' => 'Temperature is required.',
            'payload.temperature.between' => 'Temperature must be between 30°C and 43°C.',
            'payload.oxygenSaturation.required' => 'O2 saturation is required.',
            'payload.oxygenSaturation.between' => 'O2 saturation must be between 50% and 100%.',
            'payload.bloodGlucose.required' => 'Blood glucose is required.',
            'payload.bloodGlucose.between' => 'Blood glucose must be between 20 and 800 mg/dL.',
            'payload.painLevel.required' => 'Choose a pain level.',
            'payload.painLevel.between' => 'Pain level must be between 0 and 10.',
            'payload.recordedDate.required' => 'Date is required.',
            'payload.recordedTime.required' => 'Time is required.',
        ];
    }
}
