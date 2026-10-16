<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionChargeDiagnosis extends Model
{
    protected $table = 'admission_charge_diagnosis';

    protected $primaryKey = 'admission_charge_diagnosis_id';

    protected $fillable = [
        'patient_admission_id',
        'patient_diagnosis_id',
        'diagnosis_case_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function patientAdmission()
    {
        return $this->belongsTo(PatientAdmission::class, 'patient_admission_id', 'patient_admission_id');
    }

    public function patientDiagnosis()
    {
        return $this->belongsTo(PatientDiagnosis::class, 'patient_diagnosis_id', 'patient_diagnosis_id');
    }

    public function diagnosisCase()
    {
        return $this->belongsTo(DiagnosisCase::class, 'diagnosis_case_id', 'diagnosis_case_id');
    }
}
