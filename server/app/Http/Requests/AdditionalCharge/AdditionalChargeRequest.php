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
            'charges.*.description' => ['required', 'string', 'max:255'],
            'charges.*.amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'charges.*.type' => 'type',
            'charges.*.description' => 'description',
            'charges.*.amount' => 'amount',
        ];
    }
}
