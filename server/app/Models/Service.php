<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'service_id';

    protected $fillable = [
        'category_id',
        'branch_id',
        'price',
        'service_name',
        'maximum_duration',
        'is_available',
        'type',
    ];
    public function uniqueIds()
    {
        return ['service_uuid'];
    }

    protected $casts = [
        'is_available' => 'boolean',
        'maximum_duration' => 'datetime:H:i:s',
    ];

    /**
     * Branch that owns the service.
     */
    public function branches()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function categories()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }



    public function staff()
    {
        return $this->belongsToMany(
            User::class,
            'staff_services',
            'service_id',
            'user_id'
        )->withPivot('is_active');
    }
}
