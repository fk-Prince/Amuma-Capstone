<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    //
    protected $primaryKey = 'price_id';
    public $timestamps = false;
    protected $fillable = [
        'price',
        'valid_from',
        'valid_to',
    ];
}
