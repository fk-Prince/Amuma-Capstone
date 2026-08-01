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
        'note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function scheduleService(): BelongsTo
    {
        return $this->belongsTo(
            ScheduleService::class,
            'schedule_services_id',
            'schedule_services_id'
        );
    }
}
