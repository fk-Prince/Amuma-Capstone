<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    //
    protected $primaryKey = 'location_id';

    public $timestamps = false;

    protected $fillable = [
        'street',
        'city',
        'province',
        'country',
        'full_address',
        'longitude',
        'latitude',
    ];

    protected static function booted(): void
    {
        static::saving(function (Location $location) {
            $parts = array_filter([
                $location->street,
                $location->city,
                $location->province,
                $location->country,
            ]);

            if ($parts && empty($location->attributes['full_address'])) {
                $location->attributes['full_address'] = implode(', ', $parts);
            }
        });
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }

                return implode(', ', array_filter([
                    $this->street,
                    $this->city,
                    $this->province,
                    $this->country,
                ]));
            }
        );
    }
    public function userLocations()
    {
        return $this->hasMany(User::class, 'location_id', 'location_id');
    }

    public function agencyLocations()
    {
        return $this->hasMany(Agency::class, 'location_id', 'location_id');
    }
    public function branchLocations()
    {
        return $this->hasMany(Branch::class, 'location_id', 'location_id');
    }

    public function patientLocations()
    {
        return $this->hasMany(Patient::class, 'location_id', 'location_id');
    }
}
