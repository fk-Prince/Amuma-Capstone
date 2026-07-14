<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformAdmin extends Model
{
    protected $primaryKey = 'platform_admins';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'avatar',
        'is_active',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_id', 'user_id');
    }
}
