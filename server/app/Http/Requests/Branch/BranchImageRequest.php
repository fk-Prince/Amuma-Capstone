<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:5120'],
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'type' => ['required', 'string', 'in:BRANCH,COMMON_ROOM,VIP_ROOM,FACILITY,OTHER'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
