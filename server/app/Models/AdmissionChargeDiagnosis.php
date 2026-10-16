<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One charge for diagnosis cases: the admission period and invoice it is on,
// and what it comes to.
// The diagnoses it is for are the admission_diagnosis rows under it.
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

    // The case price is monthly. A yearly period bills twelve of them.
    public static function multiplier(?string $billingCycle): int
    {
        return strtoupper(trim((string) $billingCycle)) === 'YEARLY' ? 12 : 1;
    }

    public function months(): int
    {
        $monthly = (float) $this->diagnoses()->sum('amount');

        return $monthly > 0 ? (int) round((float) $this->amount / $monthly) : 0;
    }

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
