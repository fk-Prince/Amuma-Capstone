<?php

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use App\Rules\NoEmoji;
use App\Service\Booking\BookingHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffHomecareBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient' => ['required', 'array'],
            'patient.first_name' => ['required', 'string', 'max:100'],
            'patient.middle_name' => ['nullable', 'string', 'max:100'],
            'patient.last_name' => ['required', 'string', 'max:100'],
            'patient.suffix' => ['nullable', 'string', 'max:20'],
            'patient.gender' => ['required', 'string', 'max:20'],
            'patient.date_of_birth' => ['required', 'date', 'before:today'],
            'patient.address' => ['required', 'string', 'max:255'],
            'patient.phone_number' => ['nullable', 'string', 'max:30'],
            'guardian' => ['required', 'array'],
            'guardian.first_name' => ['required', 'string', 'max:100'],
            'guardian.middle_name' => ['nullable', 'string', 'max:100'],
            'guardian.last_name' => ['required', 'string', 'max:100'],
            'guardian.email' => ['required', 'email', 'max:255'],
            'guardian.phone_number' => ['required', 'string', 'max:30'],
            'guardian.relationship' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in([Booking::TYPE_ADL, Booking::TYPE_MEDICAL])],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'prefered_time' => ['required', 'string', 'max:10'],
            'time_span' => ['nullable'],
            'billing' => ['nullable', Rule::in([BookingHelper::ADL_BILLING_HOURLY, BookingHelper::ADL_BILLING_MONTHLY])],
            'months' => ['required_if:billing,' . BookingHelper::ADL_BILLING_MONTHLY, 'nullable', 'integer', 'min:1'],
            'monthly_rate' => ['nullable', 'numeric', 'min:1'],
            'activities' => ['nullable', 'array', 'min:1'],
            'activities.*' => ['required', 'string', 'max:60', 'distinct:ignore_case', new NoEmoji()],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'services' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:500'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'diagnoses' => ['nullable', 'array'],
            'assessment' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'months.required_if' => 'Enter how many months of care.',
            'months.integer' => 'Enter a whole number of months.',
            'months.min' => 'Enter at least 1 month.',
            'monthly_rate.min' => 'The monthly base price must be greater than zero.',
            'activities.min' => 'Select at least one activity.',
            'activities.*.distinct' => 'Each activity can only be added once.',
            'activities.*.max' => 'Activity must be 60 characters or fewer.',
            'note.max' => 'Location instructions must be 500 characters or fewer.',
        ];
    }
}
