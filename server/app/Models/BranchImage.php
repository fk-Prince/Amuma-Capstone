<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchImage extends Model
{
    use Auditable;

    protected string $auditModule = 'branch_settings';

    protected $primaryKey = 'branch_image_id';

    public const IMAGE_VIP_ROOM = 'vip_room';
    public const IMAGE_COMMON_ROOM = 'common_room';
    public const IMAGE_BRANCH = 'branch';
    public const IMAGE_OTHER = 'other';
    public const IMAGE_COVER = 'cover';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'branch_id',
        'image_url',
        'type',
        'visibility',
        'description',
    ];

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}