<?php

namespace App\Http\Requests\Service;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'branch_uuid' => ['required', 'exists:branches,uuid'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'service_name' => ['required', 'string', 'max:255',],
            'maximum_duration' => ['required', 'date_format:H:i:s',],
            'is_available' => ['boolean'],
            'type' => ['required', 'in:online,facility,both'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (
            $this->has('maximum_duration') &&
            is_numeric($this->maximum_duration) &&
            !preg_match('/^\d{2}:\d{2}:\d{2}$/', $this->maximum_duration)
        ) {
            $minutes = (int) $this->maximum_duration;

            $this->merge([
                'maximum_duration' => sprintf(
                    '%02d:%02d:00',
                    intdiv($minutes, 60),
                    $minutes % 60
                ),
            ]);
        }
    }
}
