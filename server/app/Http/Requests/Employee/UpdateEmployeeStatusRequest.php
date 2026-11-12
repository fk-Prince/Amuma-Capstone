<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeLeave;
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
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'type' => ['exclude_unless:status,on_leave', 'required', Rule::in(EmployeeLeave::TYPES)],
            'reason' => ['exclude_unless:status,on_leave', 'required', 'string', 'max:255'],
            'ends_on' => ['exclude_unless:status,on_leave', 'nullable', 'date', 'after:today'],
            'unassign_work' => ['exclude_unless:status,on_leave', 'nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Choose a leave type.',
            'type.in' => 'Choose a valid leave type.',
            'reason.required' => 'Give the reason for this leave.',
            'ends_on.after' => 'The return date must be after today.',
        ];
    }
}
