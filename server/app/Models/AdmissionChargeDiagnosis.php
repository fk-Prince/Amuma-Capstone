<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionChargeDiagnosis extends Model
{
    protected $table = 'admission_charge_diagnosis';

    protected $primaryKey = 'admission_charge_diagnosis_id';

    protected $fillable = [
        'patient_admission_id',
        'admission_period_id',
        'invoice_id',
        'patient_diagnosis_id',
        'diagnosis_case_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // The case price is monthly. A yearly period bills twelve of them.
    public static function multiplier(?string $billingCycle): int
    {
        return strtoupper(trim((string) $billingCycle)) === 'YEARLY' ? 12 : 1;
    }

    public function patientAdmission()
    {
        return $this->belongsTo(PatientAdmission::class, 'patient_admission_id', 'patient_admission_id');
    }

    public function admissionPeriod()
    {
        return $this->belongsTo(AdmissionPeriod::class, 'admission_period_id', 'admission_period_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    public function patientDiagnosis()
    {
        return $this->belongsTo(PatientDiagnosis::class, 'patient_diagnosis_id', 'patient_diagnosis_id');
    }

    public function diagnosisCase()
    {
        return $this->belongsTo(DiagnosisCase::class, 'diagnosis_case_id', 'diagnosis_case_id');
    }

    // What this charge costs per month, whatever length of period it billed.
    public function monthlyAmount(): float
    {
        $cycle = $this->admissionPeriod?->branchContract?->billing_cycle;

        return round((float) $this->amount / self::multiplier($cycle), 2);
    }

    // The shape an additional charge has when an invoice lists its charges.
    public function asCharge(): array
    {
        return [
            'additional_charge_id' => null,
            'patient_admission_id' => $this->patient_admission_id,
            'type' => 'diagnosis_case',
            'type_label' => $this->type_label,
            'description' => $this->line_description,
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    // Shown next to additional charges on invoices.
    public function getTypeLabelAttribute(): string
    {
        return 'Diagnosis Case';
    }

    public function getLineDescriptionAttribute(): string
    {
        return $this->description
            ?: trim(($this->diagnosisCase?->title ?? 'Diagnosis case')
                . ($this->patientDiagnosis?->diagnosis ? ' — ' . $this->patientDiagnosis->diagnosis : ''));
    }
}
