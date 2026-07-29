<?php

namespace App\Http\Requests\Bed;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => [
                'required',
            ],

            'room_id' => [
                'required',
                'exists:rooms,room_id',
            ],

            'bed_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('beds', 'bed_no')
                    ->where(function ($query) {
                        return $query->where(
                            'room_id',
                            $this->room_id
                        );
                    })
                    ->ignore($this->id, 'bed_id'),
            ],

            'status' => [
                'required',
                'string',
                'in:Available,Occupied,Maintenance',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'bed_no.unique' => 'This bed number already exists in this room.',
        ];
    }
}
