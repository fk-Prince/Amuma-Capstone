<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasUuids;

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_RESOLVED = 'resolved';

    protected $primaryKey = 'inquiry_id';

    protected $fillable = [
        'branch_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'city',
        'message',
        'status',
        'reply',
        'replied_at',
        'replied_by',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
