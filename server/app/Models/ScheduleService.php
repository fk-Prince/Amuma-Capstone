<?php

namespace App\Models;

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
        'type'
    ];

    protected $casts = [
        'hours_booked' => 'decimal:2',
    ];

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
