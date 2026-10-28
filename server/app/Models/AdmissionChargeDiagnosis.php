<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AdmissionChargeDiagnosis extends Model
{
    protected $table = 'admission_charge_diagnosis';

    protected $primaryKey = 'admission_charge_diagnosis_id';

    protected $fillable = [
        'admission_period_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function admissionPeriod()
    {
        return $this->belongsTo(AdmissionPeriod::class, 'admission_period_id', 'admission_period_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function diagnoses()
    {
        return $this->hasMany(
            AdmissionDiagnosis::class,
            'admission_charge_diagnosis_id',
            'admission_charge_diagnosis_id'
        );
    }
}
