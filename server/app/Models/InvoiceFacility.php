<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceFacility extends Model
{
    protected $table = 'invoice_facilities';

    protected $primaryKey = 'invoice_facility_id';

    protected $fillable = [
        'branch_contract_id',
        'patient_admission_id',
        'invoice_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function branchContract(): BelongsTo
    {
        return $this->belongsTo(
            BranchContract::class,
            'branch_contract_id',
            'branch_contract_id'
        );
    }

    public function patientAdmission(): BelongsTo
    {
        return $this->belongsTo(
            PatientAdmission::class,
            'patient_admission_id',
            'patient_admission_id'
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class,
            'invoice_id',
            'invoice_id'
        );
    }
}
