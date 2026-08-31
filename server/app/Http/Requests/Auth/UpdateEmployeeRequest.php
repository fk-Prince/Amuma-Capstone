<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uuid = $this->route('employee');

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($uuid, 'uuid'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string'],
            'birth_date' => ['required', 'date'],
            'phone_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],

            'location' => ['required', 'array'],
            'location.street' => ['required', 'string', 'max:255'],
            'location.city' => ['required', 'string', 'max:255'],
            'location.province' => ['required', 'string', 'max:255'],
            'location.country' => ['required', 'string', 'max:255'],

            'role_name' => ['required', 'string', 'max:255'],
            'assignment_type' => ['nullable', 'string', 'max:255'],

            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],

            'permissions' => ['nullable', 'array'],
            'permissions.*.module_id' => ['required', 'integer', 'exists:modules,module_id'],
            'permissions.*.can_read' => ['required', 'boolean'],
            'permissions.*.can_create' => ['required', 'boolean'],
            'permissions.*.can_update' => ['required', 'boolean'],

            'documents' => ['nullable', 'array'],
            'documents.*.label' => ['required', 'string', 'max:255'],
            'documents.*.file' => ['nullable', 'file', 'max:10240'],
            'documents.*.url' => ['nullable', 'string'],
        ];
    }
}
