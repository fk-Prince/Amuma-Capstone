<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    protected $primaryKey = 'vital_id';

    protected $fillable = [
        'patient_id',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'oxygen_saturation',
        'blood_glucose',
        'pain_level',
        'recorded_date',
        'recorded_time',
        'notes',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'recorded_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
