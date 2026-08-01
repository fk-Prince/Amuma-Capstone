<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientAdmission extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_admission_id';

    protected $fillable = [
        'bed_id',
        'patient_id',
        'status',
        'note',
        'admitted_at',
        'end_date',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function bed()
    {
        return $this->belongsTo(Bed::class, 'bed_id', 'bed_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
    public function invoiceAdmission()
    {
        return $this->hasMany(InvoiceFacility::class, 'patient_admission_id', 'patient_admission_id');
    }
}
