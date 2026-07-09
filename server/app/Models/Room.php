<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
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
}
