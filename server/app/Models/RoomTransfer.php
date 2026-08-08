<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTransfer extends Model
{
    protected $table = 'room_transfers';
    protected $primaryKey = 'room_transfer_id';

    protected $fillable = [
        'patient_admission_id',
        'from_room_id',
        'to_room_id',
        'from_bed_id',
        'to_bed_id',
        'reason',
    ];

    public function admission()
    {
        return $this->belongsTo(PatientAdmission::class, 'patient_admission_id', 'patient_admission_id');
    }

    public function fromBed()
    {
        return $this->belongsTo(Bed::class, 'from_bed_id', 'bed_id');
    }

    public function toBed()
    {
        return $this->belongsTo(Bed::class, 'to_bed_id', 'bed_id');
    }

    public function fromRoom()
    {
        return $this->belongsTo(Room::class, 'from_room_id', 'room_id');
    }

    public function toRoom()
    {
        return $this->belongsTo(Room::class, 'to_room_id', 'room_id');
    }
}
