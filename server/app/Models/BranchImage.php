<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchImage extends Model
{
    // use HasFactory;
    protected $primaryKey = 'branch_image_id';

    public const IMAGE_VIP_ROOM = 'vip_room';
    public const IMAGE_COMMON_ROOM = 'common_room';
    public const IMAGE_BRANCH = 'branch';
    public const IMAGE_OTHER = 'other';

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
