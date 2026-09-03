<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAssessment extends Model
{
    use HasUuids;

    protected $primaryKey = 'patient_assessment_id';

    public const LIFE_SYSTEM_ACTIVITIES = [
        'bathing',
        'transferring',
        'toileting',
        'grooming',
        'eating',
        'locomotion',
        'dressing',
    ];

    protected $fillable = [
        'patient_id',
        'condition',
        'mental_state',
        'affect',
        'behavior',
        'communication',
        'speech',
        'bathing',
        'transferring',
        'toileting',
        'grooming',
        'eating',
        'locomotion',
        'dressing',
    ];

    protected $casts = [
        'bathing' => 'integer',
        'transferring' => 'integer',
        'toileting' => 'integer',
        'grooming' => 'integer',
        'eating' => 'integer',
        'locomotion' => 'integer',
        'dressing' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getKeyName()
    {
        return 'patient_assessment_id';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function getLifeSystemProfileAttribute(): array
    {
        return collect(self::LIFE_SYSTEM_ACTIVITIES)
            ->mapWithKeys(fn($activity) => [$activity => (int) $this->{$activity}])
            ->all();
    }
}
