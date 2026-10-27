<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Utils\BranchCode;
use Carbon\Carbon;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use Auditable;

    protected string $auditModule = 'schedules';

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
        'is_stay_in',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_stay_in' => 'boolean',
    ];

    public function auditBranchId(): ?int
    {
        $branchId = Patient::where('patient_id', $this->patient_id)->value('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function auditDescription(string $action): ?string
    {
        $code = $this->schedule_code ?: 'a schedule';
        $name = trim((string) $this->patient?->display_name);
        $for = $name ? " for {$name}" : '';

        if ($action === 'created') {
            return "Created schedule {$code}{$for}.";
        }

        if ($this->wasChanged('status')) {
            return match ($this->status) {
                self::STATUS_ONGOING => "Started schedule {$code}{$for}.",
                self::STATUS_COMPLETED => "Completed schedule {$code}{$for}.",
                self::STATUS_CANCELLED => "Cancelled schedule {$code}{$for}.",
                self::STATUS_MISSED => "Marked schedule {$code}{$for} as missed.",
                self::STATUS_PENDING => "Set schedule {$code}{$for} back to pending.",
                default => null,
            };
        }

        if ($this->wasChanged('scheduled_at')) {
            return "Rescheduled {$code}{$for}.";
        }

        if ($this->wasChanged('note')) {
            return "Updated the note on schedule {$code}.";
        }

        return "Updated schedule {$code}.";
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function firstScanAt(): ?Carbon
    {
        $at = AdlAttendance::whereHas(
            'assigned.scheduleService',
            fn($query) => $query->where('schedule_id', $this->schedule_id)
        )->min('in_timestamp');

        return $at ? Carbon::parse($at) : null;
    }

    public function startedAt(): ?Carbon
    {
        $scan = $this->firstScanAt();

        return $scan && $this->scheduled_at && $scan->lt($this->scheduled_at)
            ? $scan
            : $this->scheduled_at;
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
                $schedule->schedule_code = BranchCode::make(
                    self::class,
                    'SCH',
                    'schedule_code',
                    Patient::whereKey($schedule->patient_id)->value('branch_id')
                );
            }
        });
    }
}
