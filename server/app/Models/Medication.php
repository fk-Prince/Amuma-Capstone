<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    protected $primaryKey = 'medication_id';

    protected $fillable = [
        'patient_id',
        'name',
        'strength',
        'dosage_amount',
        'dosage_unit',
        'route',
        'instructions',
        'taken_for',
        'duration',
        'frequency',
        'kind',
        'times',
        'start_date',
        'recorded_at',
    ];

    protected $casts = [
        'dosage_amount' => 'decimal:2',
        'times' => 'array',
        'start_date' => 'date',
        'recorded_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class, 'medication_id', 'medication_id');
    }
}
