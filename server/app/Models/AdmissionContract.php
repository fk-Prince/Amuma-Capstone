<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionContract extends Model
{
    use HasFactory;

    protected $primaryKey = 'admission_contract_id';

    protected $fillable = [
        'branch_contract_id',
        'patient_admission_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function branchContract()
    {
        return $this->belongsTo(
            BranchContract::class,
            'branch_contract_id',
            'branch_contract_id'
        );
    }

    public function patientAdmission()
    {
        return $this->belongsTo(
            PatientAdmission::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }
}
