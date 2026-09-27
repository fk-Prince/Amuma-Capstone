<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasUuids;
    protected $primaryKey = 'agency_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

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
        'status',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    // `registered_by` is also a column, so reading it as a property gives the
    // user_id rather than the relation. This name resolves to the User.
    public function registrant()
    {
        return $this->belongsTo(User::class, 'registered_by', 'user_id');
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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'agency_id', 'agency_id');
    }
}
