<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use Auditable;

    protected string $auditModule = 'rooms';

    protected $primaryKey = 'room_id';
    protected $fillable = [
        'branch_id',
        'room_no',
        'floor',
        'room_type',
        'capacity',
        'status',
    ];

    public function branchRoom()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function beds()
    {
        return $this->hasMany(Bed::class, 'room_id', 'room_id');
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'room_id', 'room_id');
    }

    public function availableBeds()
    {
        return $this->hasMany(Bed::class, 'room_id', 'room_id')
            ->whereDoesntHave('currentAdmission');
    }
}
