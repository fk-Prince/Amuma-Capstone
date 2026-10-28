<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'open';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_ANSWERED, self::STATUS_RESOLVED];

    public const CATEGORIES = ['billing', 'verification', 'technical', 'account', 'other'];

    protected $primaryKey = 'support_ticket_id';

    protected $fillable = [
        'branch_id',
        'user_id',
        'category',
        'subject',
        'status',
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

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'support_ticket_id', 'support_ticket_id');
    }

    public function isPublic(): bool
    {
        return $this->branch_id === null;
    }

    public function getSubmitterNameAttribute(): ?string
    {
        $profile = $this->user?->employee ?? $this->user?->client;

        return trim(($profile?->first_name ?? '') . ' ' . ($profile?->last_name ?? '')) ?: null;
    }

    public function getTicketCodeAttribute(): string
    {
        return 'SUP-' . str_pad((string) $this->support_ticket_id, 5, '0', STR_PAD_LEFT);
    }
}
