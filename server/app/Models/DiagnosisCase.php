<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DiagnosisCase extends Model
{
    use HasUuids;

    protected $table = 'diagnosis_cases';

    protected $primaryKey = 'diagnosis_case_id';

    protected $fillable = [
        'branch_id',
        'title',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getKeyName()
    {
        return 'diagnosis_case_id';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function additionalCharges()
    {
        return $this->belongsToMany(
            AdditionalCharge::class,
            'additional_charge_diagnosis',
            'diagnosis_case_id',
            'additional_charge_id'
        )->withPivot('patient_diagnosis_id')->withTimestamps();
    }
}
