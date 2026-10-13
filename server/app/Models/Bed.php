<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use Auditable;

    protected string $auditModule = 'rooms';

    protected $primaryKey = 'bed_id';
    protected $fillable = [
        'bed_no',
        'status',
        'room_id'
    ];

    public const STATUS_AVAILABLE = 'Available';
    public const STATUS_RESERVED = 'Reserved';
    public const STATUS_OCCUPIED = 'Occupied';
    public const STATUS_MAINTENANCE = 'Maintenance';

    public function auditBranchId(): ?int
    {
        $branchId = $this->relationLoaded('room')
            ? $this->room?->branch_id
            : Room::whereKey($this->room_id)->value('branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function roomTransfers()
    {
        return $this->hasMany(RoomTransfer::class, 'to_bed_id', 'bed_id');
    }

    public function currentAdmission()
    {
        return $this->hasOne(PatientAdmission::class, 'bed_id', 'bed_id')
            ->where('status', PatientAdmission::STATUS_ADMITTED);
    }

    public function reservedAdmission()
    {
        return $this->hasOne(PatientAdmission::class, 'bed_id', 'bed_id')
            ->where('status', PatientAdmission::STATUS_WAITING);
    }
}
