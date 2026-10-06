<?php

namespace App\Http\Requests\Patient\Clinical;

use App\Models\PatientAssessment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'branch_uuid' => ['required', 'uuid'],
            'condition' => ['required', 'in:ambulatory,wheelchair,stretcher'],
            'mental_state' => ['required', 'in:alert,drowsy,lethargic,forgetfulness'],
            'affect' => ['required', 'in:cheerful,flat,tearful,depressed,angry'],
            'behavior' => ['required', 'in:cooperative,uncooperative,lack_of_interaction,communication_barrier'],
            'communication' => ['required', 'in:Coherent & Logical,Impaired'],
            'speech' => ['required', 'in:clear,slurred,aphasic'],
            'life_system_profile' => ['required', 'array'],
        ];

        foreach (PatientAssessment::LIFE_SYSTEM_ACTIVITIES as $activity) {
            $rules["life_system_profile.$activity"] = ['required', 'integer', 'between:0,5'];
        }

        return $rules;
    }
}
