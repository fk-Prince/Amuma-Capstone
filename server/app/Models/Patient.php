<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasUuids;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'branch_id',
        'location_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'height',
        'weight',
        'blood_type',
        'date_of_birth',
        'phone_number',
        'citizenship',
        'initial_assessment',
        'medication',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'initial_assessment' => 'array',
        'medication' => 'array',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'patient_id', 'patient_id');
    }

    public function admissions()
    {
        return $this->hasMany(PatientAdmission::class, 'patient_id', 'patient_id');
    }

    public function currentAdmission()
    {
        $now = now();

        return $this->hasOne(PatientAdmission::class, 'patient_id', 'patient_id')
            ->where('status', 'admitted')
            ->whereHas('invoiceAdmission', function ($query) use ($now) {
                $query
                    ->where('start_date', '<=', $now)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', $now);
                    });
            })
            ->latestOfMany('patient_admission_id');
    }

    public function latestAdmission()
    {
        return $this->hasOne(PatientAdmission::class, 'patient_id', 'patient_id')
            ->latestOfMany('patient_admission_id');
    }
}
