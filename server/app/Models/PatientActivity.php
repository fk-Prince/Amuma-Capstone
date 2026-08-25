<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientActivity extends Model
{
    protected $primaryKey = 'patient_activity_id';

    public const TYPE_APPOINTMENT = 'appointment';
    public const TYPE_THERAPY = 'therapy';
    public const TYPE_MEAL = 'meal';
    public const TYPE_ACTIVITY = 'activity';

    protected $fillable = [
        'patient_id',
        'title',
        'subtitle',
        'description',
        'type',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
