<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasUuids;
    protected $primaryKey = 'agency_id';

    protected $fillable = [
        'name',
        'description',
        'location_id',
        'registered_by',
        'email',
        'image',
        'id_front',
        'id_back',
        'document',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function registered_by()
    {
        return $this->belongsTo(User::class, 'registered_by', 'user_id');
    }

    public function locations()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'agency_id', 'agency_id');
    }
}
