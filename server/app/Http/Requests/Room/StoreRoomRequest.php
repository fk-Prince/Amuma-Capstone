<?php

namespace App\Http\Requests\Room;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
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
        return [
            'branch_uuid' => ['required', 'string'],
            'room_no' => ['required', 'string', 'max:50', 'unique:rooms'],
            'floor' => ['required', 'string', 'min:1'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', Rule::in(['VIP', 'Common',])],
            'status' => ['required', Rule::in(['Available',  'Occupied',  'Maintenance'])],
        ];
    }
}
