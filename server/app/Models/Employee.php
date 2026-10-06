<?php

namespace App\Models;

use App\Models\Concerns\CapitalizesNames;
use App\Models\Location;
use App\Utils\BranchCode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use CapitalizesNames;

    protected $primaryKey = 'employee_id';

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'birth_date',
        'location_id',
        'phone_number',
        'avatar',
        'documents',
    ];

    protected $casts = [
        'documents' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            if ($employee->employee_code) {
                return;
            }

            $employee->employee_code = self::generateCode(null);
        });
    }

    public static function generateCode(mixed $branchId): string
    {
        return BranchCode::make(self::class, 'EMP', 'employee_code', $branchId);
    }

    protected function fullName()
    {
        return Attribute::make(
            get: fn() => trim("{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : '') . "{$this->last_name}")
        );
    }
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} " . ($this->middle_name ? "{$this->middle_name} " : '') . "{$this->last_name}");
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function permissions()
    {
        return $this->hasMany(EmployeePermission::class, 'employee_id', 'employee_id');
    }

    public function locations()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function employeeBranch()
    {
        return $this->hasMany(EmployeeBranch::class, 'employee_id', 'employee_id');
    }

    public function conflictingSchedules()
    {
        return $this->hasManyThrough(
            Schedule::class,
            ScheduleAssigned::class,
            'employee_id',
            'schedule_id',
            'employee_id',
            'schedule_id'
        );
    }
}
