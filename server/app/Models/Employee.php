<?php

namespace App\Models;

use App\Models\Concerns\CapitalizesNames;
use App\Models\Location;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use CapitalizesNames;

    protected $primaryKey = 'employee_id';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ONLEAVE = 'on_leave';


    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'birth_date',
        'location_id',
        'phone_number',
        'status',
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

            $last = self::lockForUpdate()
                ->whereNotNull('employee_code')
                ->orderByDesc('employee_id')
                ->first();

            $next = $last
                ? ((int) substr($last->employee_code, 4)) + 1
                : 1;

            $employee->employee_code = 'EMP-' . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
        });
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
