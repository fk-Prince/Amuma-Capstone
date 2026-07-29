<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientAccess extends Model
{
    protected $primaryKey = 'patient_access_id';

    protected $fillable = [
        'client_id',
        'patient_id',
        'have_access',
        'relationship_type',
    ];

    protected $casts = [
        'have_access' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(
            Client::class,
            'client_id',
            'client_id'
        );
    }

    public function patient()
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id',
            'patient_id'
        );
    }
}
