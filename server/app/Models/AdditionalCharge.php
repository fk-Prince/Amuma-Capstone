<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalCharge extends Model
{
    public const TYPE_MEDICATION = 'medication';
    public const TYPE_SUPPLIES = 'supplies';
    public const TYPE_ADDITIONAL = 'additional_charges';

    public const TYPES = [
        self::TYPE_MEDICATION,
        self::TYPE_SUPPLIES,
        self::TYPE_ADDITIONAL,
    ];

    protected $primaryKey = 'additional_charge_id';

    protected $fillable = [
        'patient_admission_id',
        'invoice_id',
        'type',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function patientAdmission()
    {
        return $this->belongsTo(PatientAdmission::class, 'patient_admission_id', 'patient_admission_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_MEDICATION => 'Medication',
            self::TYPE_SUPPLIES => 'Supplies',
            default => 'Additional Charges',
        };
    }
}
