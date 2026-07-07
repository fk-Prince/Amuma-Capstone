<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    //
    use HasUuids;
    protected $primaryKey = 'booking_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reference_id',
        'user_id',
        'branch_id',
        'booking_data',
        'status',
        'category',
        'valid_until',
    ];
    public function uniqueIds()
    {
        return ['reference_id'];
    }

    protected $casts = [
        'booking_data' => 'array',
    ];
}
