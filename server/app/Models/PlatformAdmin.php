<?php

namespace App\Models;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformAdmin extends Model
{

    protected $primaryKey = 'platform_admin_id';

    protected $fillable = [
        'user_id',
        'location_id',
        'first_name',
        'middle_name',
        'last_name',
        'phone_number',
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
