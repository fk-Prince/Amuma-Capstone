<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasUuids;

    protected $primaryKey = 'camera_id';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PUBLIC = 'public';

    protected $fillable = [
        'branch_id',
        'room_id',
        'username',
        'ip_address',
        'password',
        'visibility',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function getKeyName()
    {
        return 'camera_id';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }
}
