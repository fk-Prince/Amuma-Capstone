<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleService extends Model
{
    use HasFactory;

    protected $primaryKey = 'schedule_services_id';

    public const TYPE_MEDICAL = 'Medical';
    public const TYPE_ADL = 'ADL';

    public const BILLING_HOURLY = 'hourly';
    public const BILLING_MONTHLY = 'monthly';

    protected $fillable = [
        'schedule_id',
        'service_id',
        'duration',
        'status',
        'type',
        'billing_cycle',
        'activities',
    ];

    protected $casts = [
        'duration' => 'decimal:2',
    ];

    public function isMonthly(): bool
    {
        return $this->type === self::TYPE_ADL
            && $this->billing_cycle === self::BILLING_MONTHLY;
    }

    public function billedMonths(): ?int
    {
        if (!$this->isMonthly() || !$this->schedule?->scheduled_at) {
            return null;
        }

        $start = $this->schedule->scheduled_at->copy()->startOfDay();
        $end = $start->copy()->addMinutes((int) round((float) $this->duration * 60));

        return max(1, (int) round($start->diffInMonths($end)));
    }

    public function billedQuantity(): float
    {
        if ($this->service_id !== null) {
            return 1;
        }

        return $this->isMonthly()
            ? (float) ($this->billedMonths() ?? 1)
            : (float) ($this->duration ?? 0);
    }

    protected static function booted()
    {
        static::creating(function ($scheduleService) {
            if ($scheduleService->type !== self::TYPE_ADL) {
                return;
            }

            if (empty($scheduleService->activities)) {
                $scheduleService->activities = BranchContract::ADL_ACTIVITIES;
            }

            $scheduleService->billing_cycle ??= self::BILLING_HOURLY;
        });
    }

    protected function activities(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value !== null
                ? json_decode($value, true)
                : ($this->type === self::TYPE_ADL ? BranchContract::ADL_ACTIVITIES : null),
            set: fn($value) => $value === null ? null : json_encode(array_values($value)),
        );
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id',   'schedule_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function invoiceServices()
    {
        return $this->hasMany(InvoiceServices::class, 'schedule_services_id', 'schedule_services_id');
    }

    public function assigned()
    {
        return $this->hasMany(ScheduleAssigned::class, 'schedule_services_id', 'schedule_services_id');
    }
}
