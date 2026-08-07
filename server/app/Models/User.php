<?php

namespace App\Models;

use App\Models\PlatformAdmin;
use App\Models\Branch;
use App\Models\Location;
use App\Models\Service;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Override;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'email',
        'password',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'employee',
        'client',
        'systemOwner',
    ];



    #[Override]
    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function locations()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }
    public function services()
    {
        return $this->belongsToMany(
            Service::class,
            'staff_services',
            'user_id',
            'service_id'
        )->withPivot('is_active');
    }

    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'user_branches',
            'user_id',
            'branch_id'
        )->withPivot('type');
    }

    public function recipient()
    {
        return $this->hasMany(Notifiable::class, 'user_id', 'to_user_id');
    }

    public function sender()
    {
        return $this->hasMany(Notifiable::class, 'user_id', 'from_user_id');
    }

    protected $appends = [
        'isEmployee',
        'isClient',
        'isSystemOwner',
        'hasBooking',
        'first_name',
        'last_name',
        'avatar',
    ];

    public function getHasBookingAttribute(): bool
    {
        return $this->bookings()->exists();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'user_id');
    }
    public function client()
    {
        return $this->hasOne(Client::class, 'user_id', 'user_id');
    }
    public function systemOwner()
    {
        return $this->hasOne(PlatformAdmin::class, 'user_id', 'user_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    public function getIsEmployeeAttribute(): bool
    {
        return $this->relationLoaded('employee')
            ? $this->employee !== null
            : $this->employee()->exists();
    }

    public function getIsClientAttribute(): bool
    {
        return $this->relationLoaded('client')
            ? $this->client !== null
            : $this->client()->exists();
    }

    public function getIsSystemOwnerAttribute(): bool
    {
        return $this->relationLoaded('systemOwner')
            ? $this->systemOwner !== null
            : $this->systemOwner()->exists();
    }

    public function getFirstNameAttribute(): ?string
    {
        return $this->employee?->first_name
            ?? $this->client?->first_name
            ?? $this->systemOwner?->first_name
            ?? null;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->employee?->last_name
            ?? $this->client?->last_name
            ?? $this->systemOwner?->last_name
            ?? null;
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->employee?->avatar
            ?? $this->client?->avatar
            ?? $this->systemOwner?->avatar
            ?? null;
    }
}
