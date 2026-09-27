<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'status' => ['required', Rule::in(['active', 'on_leave'])],
        ];
    }
}
