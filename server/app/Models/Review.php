<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $primaryKey = 'review_id';

    protected $fillable = [
        'branch_id',
        'user_id',
        'rate',
        'description',
        'image',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    protected $appends = [
        'reviewer',
    ];

    public const REVIEWER_RELATIONS = [
        'user.employee.employeeBranch.branches.agencies',
        'user.client',
        'user.systemOwner',
    ];

    public function getReviewerAttribute(): ?array
    {
        $user = $this->user;

        if (!$user) {
            return null;
        }

        if ($user->systemOwner) {
            return ['role' => 'amuma_team', 'organization' => 'AMUMA'];
        }

        $ranks = array_map(fn(RoleEnum $role) => $role->value, RoleEnum::cases());
        $rank = fn(string $slug) => ($index = array_search($slug, $ranks, true)) === false ? PHP_INT_MAX : $index;

        $assignment = ($user->employee?->employeeBranch ?? collect())
            ->where('status', '!=', EmployeeBranch::STATUS_INACTIVE)
            ->sortBy(fn($employeeBranch) => $rank(RoleEnum::slug($employeeBranch->role_name)))
            ->first();

        if (!$assignment) {
            return ['role' => 'client', 'organization' => null];
        }

        $role = RoleEnum::slug($assignment->role_name);

        return [
            'role' => $role,
            'organization' => $role === RoleEnum::AgencyOwner->value
                ? ($assignment->branches?->agencies?->name ?? $assignment->branches?->name)
                : $assignment->branches?->name,
        ];
    }


    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
