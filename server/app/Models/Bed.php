<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    protected $primaryKey = 'bed_id';
    protected $fillable = [
        'bed_no',
        'status',
        'room_id'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function currentAdmission()
    {
        return $this->hasOne(PatientAdmission::class,   'bed_id', 'bed_id')->where('status', 'admitted');
    }
}
