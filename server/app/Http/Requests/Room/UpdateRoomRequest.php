<?php

namespace App\Http\Requests\Room;

use App\Models\Branch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
    protected function prepareForValidation(): void
    {
        if ($this->input('room_type') === 'VIP') {
            $this->merge(['capacity' => 1]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('room');
        return [
            'branch_uuid' => ['required', 'string'],
            'room_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_no')
                    ->ignore($id, 'room_id')
                    ->where(fn ($q) => $q->whereIn('branch_id', Branch::select('branch_id')->where('uuid', $this->input('branch_uuid')))),
            ],
            'floor' => ['required', 'string', 'min:1'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', Rule::in(['VIP', 'Common'])],
            'status' => ['required', Rule::in(['Available', 'Occupied', 'Maintenance'])],
        ];
    }
}
