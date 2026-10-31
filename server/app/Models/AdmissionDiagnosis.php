<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionDiagnosis extends Model
{
    public const BILLED_MONTHLY = 'monthly';

    protected $table = 'admission_diagnosis';

    protected $primaryKey = 'admission_diagnosis_id';

    protected $fillable = [
        'admission_charge_diagnosis_id',
        'patient_diagnosis_id',
        'diagnosis_case_id',
        'description',
        'amount',
        'billed',
        'is_active',
        'date_billed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'date_billed' => 'date',
    ];

    public function admissionPeriod()
    {
        return $this->hasOneThrough(
            AdmissionPeriod::class,
            AdmissionChargeDiagnosis::class,
            'admission_charge_diagnosis_id',
            'admission_period_id',
            'admission_charge_diagnosis_id',
            'admission_period_id'
        );
    }

    public function charge()
    {
        return $this->belongsTo(
            AdmissionChargeDiagnosis::class,
            'admission_charge_diagnosis_id',
            'admission_charge_diagnosis_id'
        );
    }

    public function patientDiagnosis()
    {
        return $this->belongsTo(PatientDiagnosis::class, 'patient_diagnosis_id', 'patient_diagnosis_id');
    }

    public function diagnosisCase()
    {
        return $this->belongsTo(DiagnosisCase::class, 'diagnosis_case_id', 'diagnosis_case_id');
    }

    public function getBilledAmountAttribute(): float
    {
        return round((float) $this->amount, 2);
    }

    public function getPatientAdmissionAttribute(): ?PatientAdmission
    {
        return $this->admissionPeriod?->patientAdmission;
    }

    public function getPatientAdmissionIdAttribute(): ?int
    {
        return $this->admissionPeriod?->patient_admission_id;
    }

    public function asCharge(): array
    {
        return [
            'additional_charge_id' => null,
            'patient_admission_id' => $this->patient_admission_id,
            'type' => 'diagnosis_case',
            'type_label' => $this->type_label,
            'description' => $this->line_description,
            'amount' => $this->billed_amount,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return 'Diagnosis Case';
    }

    public function getLineDescriptionAttribute(): string
    {
        $title = $this->diagnosisCase?->title;

        return $title ? 'DIAGNOSIS CASE - ' . $title : 'DIAGNOSIS CASE';
    }
}
