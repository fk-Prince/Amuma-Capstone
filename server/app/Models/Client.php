<?php

namespace App\Models;

use App\Models\Location;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'client_id';


    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'location_id',
        'phone_number',
        'is_verified',
        'avatar',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_id', 'user_id');
    }

    public function locations()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
}
