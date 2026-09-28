<?php

namespace App\Http\Requests\Subscription;

use App\Models\Branch;
use App\Rules\CaseInsensitiveUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BranchResubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = Branch::where('uuid', $this->input('target_branch_uuid'))
            ->first(['branch_id', 'agency_id']);

        return [
            'branch_uuid' => ['required', 'string', 'exists:branches,uuid'],
            'target_branch_uuid' => ['required', 'string', 'exists:branches,uuid'],

            'branch_name' => ['required', 'string', 'max:255', $this->uniqueName($target)],
            'branch_email' => [
                'required',
                'email',
                'max:255',
                new CaseInsensitiveUnique('branches', 'email', $target?->branch_id, 'branch_id'),
            ],
            'branch_contact_number' => ['required', 'string'],
            'branch_description' => ['required', 'string', 'max:1000'],
            'branch_tin' => ['nullable', 'string', 'max:20'],
            'branch_image' => ['nullable', 'file', 'image', 'max:5120'],
            'branch_document' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],

            'branch_street' => ['required', 'string'],
            'branch_city' => ['required', 'string'],
            'branch_province' => ['required', 'string'],
            'branch_country' => ['required', 'string'],
            'branch_full_address' => ['nullable', 'string', 'max:500'],
            'branch_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'branch_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'agency_name' => [
                'sometimes',
                'string',
                'max:255',
                new CaseInsensitiveUnique('agencies', 'name', $target?->agency_id, 'agency_id'),
            ],
            'agency_email' => [
                'required_with:agency_name',
                'email',
                'max:255',
                new CaseInsensitiveUnique('agencies', 'email', $target?->agency_id, 'agency_id'),
            ],
            'agency_description' => ['required_with:agency_name', 'string', 'max:1000'],
            'agency_street' => ['required_with:agency_name', 'string'],
            'agency_city' => ['required_with:agency_name', 'string'],
            'agency_province' => ['required_with:agency_name', 'string'],
            'agency_country' => ['required_with:agency_name', 'string'],
            'agency_full_address' => ['nullable', 'string', 'max:500'],
            'agency_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'agency_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'agency_image' => ['nullable', 'file', 'image', 'max:5120'],
            'agency_id_front' => ['nullable', 'file', 'image', 'max:5120'],
            'agency_id_back' => ['nullable', 'file', 'image', 'max:5120'],
            'agency_document' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ];
    }

    private function uniqueName(?Branch $target)
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($target) {
            if (!$target?->agency_id) {
                return;
            }

            $exists = Branch::whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($value))])
                ->where('agency_id', $target->agency_id)
                ->where('branch_id', '!=', $target->branch_id)
                ->exists();

            if ($exists) {
                $fail('The branch name has already been taken.');
            }
        };
    }

    public function attributes(): array
    {
        return [
            'branch_name' => 'branch name',
            'branch_email' => 'branch email',
            'branch_contact_number' => 'contact number',
            'branch_description' => 'description',
            'branch_street' => 'street',
            'branch_city' => 'city',
            'branch_province' => 'province',
            'branch_country' => 'country',
            'agency_name' => 'agency name',
            'agency_email' => 'agency email',
            'agency_description' => 'agency description',
            'agency_street' => 'agency street',
            'agency_city' => 'agency city',
            'agency_province' => 'agency province',
            'agency_country' => 'agency country',
        ];
    }
}
