<?php

namespace App\Models;

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
        'first_name',
        'last_name',
        'location_id',
        'phone_number',
        'email',
        'password',
        'provider',
        'provider_id',
        'is_verified',
        'is_active',
        'uuid',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )
            ->using(UserRole::class)
            ->withPivot('is_active', 'branch_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }


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
}
