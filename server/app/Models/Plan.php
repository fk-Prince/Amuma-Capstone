<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $primaryKey = 'plan_id';
    public $timestamps = false;

    public const TYPE_SME = 'sme';
    public const TYPE_ENTERPRISE = 'enterprise';
    public const TYPES = [self::TYPE_SME, self::TYPE_ENTERPRISE];

    public const CODE_HYBRID = 'C';

    public const BRANCH_LIMITS = [
        self::TYPE_SME => 1,
        self::TYPE_ENTERPRISE => 10,
    ];

    protected $fillable = [
        'plan_code',
        'type',
        'price',
        'additional_branch_price',
        'description',
        'name'
    ];

    protected $appends = ['branch_limit'];

    public function getBranchLimitAttribute(): int
    {
        return self::branchLimitFor($this->type);
    }

    public function getAdditionalBranchPriceAttribute($value)
    {
        if ((float) $value > 0) {
            return $value;
        }

        if ($this->type === self::TYPE_ENTERPRISE) {
            $sme = self::where('plan_code', $this->plan_code)
                ->where('type', self::TYPE_SME)
                ->value('price');

            return $sme ?? $this->price;
        }

        return $this->price;
    }

    public static function branchLimitFor(?string $type): int
    {
        return self::BRANCH_LIMITS[$type] ?? self::BRANCH_LIMITS[self::TYPE_SME];
    }

    public static function branchLimitSql(string $typeColumn): string
    {
        $cases = collect(self::BRANCH_LIMITS)
            ->map(fn($limit, $type) => "when '{$type}' then {$limit}")
            ->implode(' ');

        return "(case {$typeColumn} {$cases} else " . self::BRANCH_LIMITS[self::TYPE_SME] . " end)";
    }

    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'plan_id');
    }
}
