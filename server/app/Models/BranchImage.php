<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchImage extends Model
{
    // use HasFactory;
    protected $primaryKey = 'branch_image_id';

    protected $fillable = [
        'branch_id',
        'image_url',
        'type',
        'description',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
