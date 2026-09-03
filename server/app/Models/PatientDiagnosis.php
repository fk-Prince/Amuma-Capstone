<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDiagnosis extends Model
{
    use HasUuids;

    protected $table = 'patient_diagnosis';

    protected $primaryKey = 'patient_diagnosis_id';

    protected $fillable = [
        'patient_id',
        'diagnosis',
        'diagnosis_date',
        'diagnosis_notes',
        'diagnosis_file',
    ];

    protected $casts = [
        'diagnosis_date' => 'date',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getKeyName()
    {
        return 'patient_diagnosis_id';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
