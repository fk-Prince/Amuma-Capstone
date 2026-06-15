<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'to_user_id',
        'from_user_id',
        'branch_id',
        'message_type',
        'message',
        'has_read',
    ];

    protected $casts = [
        'has_read' => 'boolean',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'to_user_id', 'user_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id', 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
