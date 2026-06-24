<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUuids;
    protected $primaryKey = 'branch_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_user_id',
        'agency_id',
        'name',
        'location_id',
        'description',
        'contact_number',
        'settings',
        'image',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function locations()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'branch_id', 'branch_id');
    }

    public function agencies()
    {
        return $this->belongsTo(Agency::class, 'agency_id', 'agency_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'branch_id', 'branch_id');
        //     'services.price' => function ($query) {
        //     $query->whereNull('until_from');
        // },
    }


    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_branches',
            'branch_id',
            'user_id'
        )->withPivot('type');
    }

    public function branch()
    {
        return $this->hasMany(Notification::class, 'branch_id', 'branch_id');
    }


    public function reviews()
    {
        return $this->hasMany(Review::class, 'branch_id', 'branch_id');
    }
}
