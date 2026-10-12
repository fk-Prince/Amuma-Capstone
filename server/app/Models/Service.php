<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'service_id';

    protected $fillable = [
        'category_id',
        'branch_id',
        'price',
        'service_name',
        'description',
        'maximum_duration',
        'is_available',
        'staff_role',
        'type',
    ];
    public const STAFF_ROLE_BOTH = 'both';
    public const STAFF_ROLE_NURSE = 'nurse';
    public const STAFF_ROLE_CAREGIVER = 'caregiver';

    public function uniqueIds()
    {
        return ['service_uuid'];
    }

    public function allowedStaffRoles(): array
    {
        return match ($this->staff_role) {
            self::STAFF_ROLE_NURSE => [self::STAFF_ROLE_NURSE],
            self::STAFF_ROLE_CAREGIVER => [self::STAFF_ROLE_CAREGIVER],
            default => [self::STAFF_ROLE_NURSE, self::STAFF_ROLE_CAREGIVER],
        };
    }

    protected $casts = [
        'is_available' => 'boolean',
    ];


    public function branches()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }



    public function staff()
    {
        return $this->belongsToMany(User::class,   'staff_services',  'service_id', 'user_id')->withPivot('is_active');
    }
}
