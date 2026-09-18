<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const SENDER_CLIENT = 'client';
    public const SENDER_STAFF = 'staff';

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'sender_type',
        'body',
        'attachment_url',
        'attachment_name',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id', 'user_id');
    }

    public function attachment(): ?array
    {
        if (!$this->attachment_url) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($this->attachment_url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return [
            'url' => $this->attachment_url,
            'name' => $this->attachment_name,
            'type' => $extension === 'pdf' ? 'pdf' : 'image',
        ];
    }

    public function preview(): ?string
    {
        if ($this->body) {
            return $this->body;
        }

        return match ($this->attachment()['type'] ?? null) {
            'image' => 'Sent a photo',
            'pdf' => 'Sent a file',
            default => null,
        };
    }

    public function toChat(?int $viewerUserId = null): array
    {
        return [
            'message_id' => $this->message_id,
            'sender_type' => $this->sender_type,
            'sender_user_id' => $this->sender_user_id,
            'is_mine' => $this->sender_user_id === $viewerUserId,
            'body' => $this->body,
            'attachment' => $this->attachment(),
            'preview' => $this->preview(),
            'created_at' => $this->created_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
        ];
    }
}
