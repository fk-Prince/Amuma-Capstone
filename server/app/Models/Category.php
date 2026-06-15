<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'branch_id',
        'category_name',
    ];

    /**
     * Branch that owns the category.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
