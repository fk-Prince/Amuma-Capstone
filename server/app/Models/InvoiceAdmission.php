<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdmission extends Model
{
    protected $table = 'invoice_admission';

    protected $primaryKey = 'invoice_admission_id';

    protected $fillable = [
        'invoice_id',
        'admission_period_id',
        'price',
        'description',
    ];

    public function admissionPeriod()
    {
        return $this->belongsTo(AdmissionPeriod::class, 'admission_period_id',   'admission_period_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class,  'invoice_id', 'invoice_id');
    }

    protected static function booted()
    {
        static::creating(function (self $line) {
            if ($line->description) {
                return;
            }

            $period = $line->admissionPeriod;
            $contract = $line->branchContract;

            $label = $period?->reason === AdmissionPeriod::REASON_EXTENDED
                ? 'EXTENSION'
                : 'ADMISSION';

            $line->description = collect([
                $label,
                $contract?->billing_cycle,
                $contract?->accommodation_type,
            ])->filter()->implode(' - ');
        });
    }

    public function getPatientAdmissionAttribute()
    {
        return $this->admissionPeriod?->patientAdmission;
    }

    public function getBranchContractAttribute()
    {
        return $this->admissionPeriod?->branchContract;
    }

    public function getBedAttribute()
    {
        return $this->admissionPeriod?->patientAdmission?->bed;
    }

    public function getStatusAttribute()
    {
        return $this->admissionPeriod?->status;
    }
}
