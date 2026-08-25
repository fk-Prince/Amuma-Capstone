<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationSchedule extends Model
{
    protected $primaryKey = 'medication_schedule_id';

    public $timestamps = false;

    public const STATUS_TAKEN = 'taken';
    public const STATUS_MISSED = 'missed';
    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'medication_id',
        'date',
        'time',
        'status',
        'marked_by',
        'recorded_at',
    ];

    protected $casts = [
        'date' => 'date',
        'recorded_at' => 'datetime',
    ];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'medication_id', 'medication_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by', 'user_id');
    }
}
