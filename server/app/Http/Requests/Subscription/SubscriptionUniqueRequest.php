<?php

namespace App\Http\Requests\Subscription;

use App\Models\Branch;
use App\Rules\CaseInsensitiveUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            'branch_email' => ['sometimes', 'string', $this->uniqueBranchEmail()],
        ];
    }

    public function uniqueAgencyName()
    {
        return new CaseInsensitiveUnique('agencies', 'name', $this->input('agency_id'), 'agency_id');
    }

    public function uniqueAgencyEmail()
    {
        return new CaseInsensitiveUnique('agencies', 'email', $this->input('agency_id'), 'agency_id');
    }

    public function uniqueBranchEmail()
    {
        return new CaseInsensitiveUnique('branches', 'email');
    }

    public function uniqueBranchName()
    {
        $agencyId = $this->input('agency_id')
            ?? Branch::where('uuid', $this->input('branch_uuid'))->value('agency_id');

        return function (string $attribute, mixed $value, \Closure $fail) use ($agencyId) {
            if (!$agencyId) {
                return;
            }

            $exists = Branch::whereRaw('LOWER(TRIM(name)) = ?', [Str::lower(trim($value))])
                ->where('agency_id', $agencyId)
                ->exists();

            if ($exists) {
                $fail('The branch name has already been taken.');
            }
        };
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
