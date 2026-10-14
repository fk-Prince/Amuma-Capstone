<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EmployeeNote extends Model
{
    use HasUuids;

    protected $primaryKey = 'employee_note_id';

    protected $fillable = [
        'branch_id',
        'employee_id',
        'schedule_id',
        'patient_id',
        'note',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id', 'schedule_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
