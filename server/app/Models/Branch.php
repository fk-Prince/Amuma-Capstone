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
        'agency_id',
        'name',
        'location_id',
        'description',
        'is_verified',
        'contact_number',
        'settings',
        'image',
        'status',
        'email'
    ];

    public function hasFacilitySubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->whereHas('plans', function ($q) {
                $q->whereIn('plan_code', ['B', 'C']);
            })
            ->exists();
    }

    protected $casts = [
        'settings' => 'array',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function contracts()
    {
        return $this->hasMany(BranchContract::class, 'branch_id', 'branch_id');
    }

    public function location()
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
    }


    public function rooms()
    {
        return $this->hasMany(Room::class, 'branch_id', 'branch_id');
    }
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'branch_id', 'branch_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'branch_id', 'branch_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'branch_id', 'branch_id');
    }

    public function employees()
    {
        return $this->hasMany(EmployeeBranch::class, 'branch_id', 'branch_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'branch_id', 'branch_id');
    }

    public function availableBeds()
    {
        return $this->hasMany(Bed::class, 'room_id', 'room_id')
            ->where('status', 'Available');
    }
}
