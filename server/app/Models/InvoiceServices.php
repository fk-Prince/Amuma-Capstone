<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceServices extends Model
{
    protected $fillable = [
        'schedule_services_id',
        'invoice_id',
        'price',
        'description',
        'note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function scheduleService()
    {
        return $this->belongsTo(
            ScheduleService::class,
            'schedule_services_id',
            'schedule_services_id'
        );
    }

    protected static function booted()
    {
        static::creating(function (self $line) {
            if ($line->description) {
                return;
            }

            $scheduleService = $line->scheduleService;

            $line->description = $scheduleService?->service_id === null
                ? 'Activities of Daily Living (ADL)'
                : $scheduleService?->service?->service_name;
        });
    }
}
