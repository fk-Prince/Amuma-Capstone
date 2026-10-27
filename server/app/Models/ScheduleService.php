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

    protected $fillable = [
        'schedule_id',
        'service_id',
        'hours_booked',
        'status',
        'type',
        'activities',
    ];

    protected $casts = [
        'hours_booked' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($scheduleService) {
            if ($scheduleService->type === self::TYPE_ADL && empty($scheduleService->activities)) {
                $scheduleService->activities = BranchContract::ADL_ACTIVITIES;
            }
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
