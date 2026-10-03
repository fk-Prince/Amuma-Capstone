<?php

namespace App\Models;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $primaryKey = 'schedule_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ONGOING = 'ongoing'; // on going / on process
    public const STATUS_COMPLETED = 'completed'; // completed
    public const STATUS_CANCELLED = 'cancelled'; // cancelled
    public const STATUS_MISSED = 'missed'; // no show up

    // When only one schedule is shown, the one that matters most comes first.
    public const STATUS_PRIORITY = [
        self::STATUS_ONGOING,
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_MISSED,
    ];

    public static function byPriority(\Illuminate\Support\Collection $schedules): \Illuminate\Support\Collection
    {
        return $schedules
            ->sortBy(function ($schedule) {
                $rank = array_search(strtolower((string) $schedule->status), self::STATUS_PRIORITY, true);

                return $rank === false ? count(self::STATUS_PRIORITY) : $rank;
            })
            ->values();
    }


    public const CATEGORYHOMECARE = 'Homecare';
    public const CATEGORYFACILITY = 'Facility';


    protected $fillable = [
        'patient_id',
        'location_id',
        'scheduled_at',
        'status',
        'category',
        'schedule_code',
        'note',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function isOnsite()
    {
        return $this->category === self::CATEGORYFACILITY;
    }

    public function scheduleServices()
    {
        return $this->hasMany(ScheduleService::class, 'schedule_id', 'schedule_id');
    }

    protected static function booted()
    {
        static::creating(function ($schedule) {
            if (!$schedule->schedule_code) {
                $lastSchedule = self::whereNotNull('schedule_code')
                    ->orderByDesc('schedule_id')
                    ->first();

                $nextNumber = 1;

                if ($lastSchedule && $lastSchedule->schedule_code) {
                    $lastNumber = (int) substr($lastSchedule->schedule_code, 4);
                    $nextNumber = $lastNumber + 1;
                }

                $schedule->schedule_code = 'SCH-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
