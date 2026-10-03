<?php

namespace App\Http\Requests\DiagnosisCase;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'price' => 'price',
        ];
    }
}
