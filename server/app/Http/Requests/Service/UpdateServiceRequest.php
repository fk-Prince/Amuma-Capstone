<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('service');

        return [
            'branch_uuid' => ['required', 'string'],
            'category_name' => ['required', 'string', 'max:255',],
            'service_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'service_name')
                    ->ignore($id, 'service_id'),
            ],
            'price' => ['required', 'numeric', 'min:1'],
            'maximum_duration' => ['required', 'date_format:H:i:s',],
            'is_available' => ['boolean'],
            'type' => ['required', Rule::in(['online', 'facility', 'both'])],
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
