<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AgencyRequest extends FormRequest
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
            'agency_name'        => ['required', 'string', 'unique:agencies,name'],
            'agency_description' => ['required', 'string'],

            'location.street'     => ['required', 'string'],
            'location.city'       => ['required', 'string'],
            'location.province'   => ['required', 'string'],
            'location.country'    => ['required', 'string'],
            'location.longitude'   => ['nullable', 'numeric'],
            'location.latitude'    => ['nullable', 'numeric'],
        ];
    }

    public function attributes(): array
    {
        return [
            'location.street'   => 'street',
            'location.city'     => 'city',
            'location.province' => 'province',
            'location.country'  => 'country',
        ];
    }
}
