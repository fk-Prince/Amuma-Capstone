<?php

namespace App\Models;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformAdmin extends Model
{
    // The column really is named employee_permission_id on this table; pointing
    // this at the table name made getKey() null, so every update() silently
    // matched zero rows.
    protected $primaryKey = 'employee_permission_id';

    protected $fillable = [
        'user_id',
        'location_id',
        'first_name',
        'last_name',
        'avatar',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_id', 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
}
