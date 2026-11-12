<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeLeave extends Model
{
    use HasUuids;

    protected $primaryKey = 'employee_leave_id';

    public const TYPE_SICK = 'sick';
    public const TYPE_VACATION = 'vacation';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_PERSONAL = 'personal';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_SICK,
        self::TYPE_VACATION,
        self::TYPE_EMERGENCY,
        self::TYPE_PERSONAL,
        self::TYPE_OTHER,
    ];

    protected $fillable = [
        'employee_branch_id',
        'type',
        'reason',
        'starts_on',
        'ends_on',
        'returned_at',
        'recorded_by',
        'returned_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'returned_at' => 'datetime',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function employeeBranch()
    {
        return $this->belongsTo(EmployeeBranch::class, 'employee_branch_id', 'employee_branch_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }

    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by', 'user_id');
    }
}
