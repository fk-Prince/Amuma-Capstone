<?php

namespace App\Http\Requests\Subscription;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionUniqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agency_id' => ['nullable'],
            'agency_name' => ['sometimes', 'string', $this->uniqueAgencyName()],
            'agency_email' => ['sometimes', 'string', $this->uniqueAgencyEmail()],
            'branch_name' => ['sometimes', 'string', $this->uniqueBranchName()],
            'branch_email' => ['sometimes', 'string', 'unique:branches,email'],
        ];
    }

    public function uniqueAgencyName()
    {
        return Rule::unique('agencies', 'name')
            ->ignore($this->input('agency_id'), 'agency_id');
    }

    public function uniqueAgencyEmail()
    {
        return Rule::unique('agencies', 'email')
            ->ignore($this->input('agency_id'), 'agency_id');
    }

    public function uniqueBranchName()
    {
        $agencyId = $this->input('agency_id')
            ?? Branch::where('uuid', $this->input('branch_uuid'))->value('agency_id');

        return Rule::unique('branches', 'name')->where(
            fn($query) => $agencyId
                ? $query->where('agency_id', $agencyId)
                : $query->whereRaw('1 = 0')
        );
    }

    public function attributes(): array
    {
        return [
            'agency_name' => 'agency name',
            'agency_email' => 'agency email',
            'branch_name' => 'branch name',
            'branch_email' => 'branch email',
        ];
    }
}
