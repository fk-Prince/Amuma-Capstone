<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaregiverShift extends Model
{
    protected $table = 'caregiver_shifts';

    protected $primaryKey = 'caregiver_shift_id';

    protected $fillable = [
        'caregiver_id',
        'admission_id',
        'note',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function caregiver()
    {
        return $this->belongsTo(Employee::class, 'caregiver_id', 'employee_id');
    }

    public function admission()
    {
        return $this->belongsTo(
            PatientAdmission::class,
            'admission_id',
            'patient_admission_id'
        );
    }
}
