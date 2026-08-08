<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasUuids;
    protected $primaryKey = 'agency_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'location_id',
        'registered_by',
        'email',
        'image'
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
