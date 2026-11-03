<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Message extends Model
{
    public const SENDER_CLIENT = 'client';
    public const SENDER_STAFF = 'staff';

    public const AUDIO_EXTENSIONS = ['webm', 'ogg', 'oga', 'm4a', 'mp3', 'wav', 'aac'];

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'reply_to_message_id',
        'sender_user_id',
        'sender_type',
        'body',
        'attachment_url',
        'attachment_name',
        'attachment_duration',
        'is_forwarded',
        'delivered_at',
        'read_at',
        'pinned_at',
        'pinned_by_user_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'delivered_at' => 'datetime',
        'pinned_at' => 'datetime',
        'is_forwarded' => 'boolean',
    ];

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id', 'message_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class, 'message_id', 'message_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'conversation_id');
    }

    public function sender(): BelongsTo
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
            'type' => match (true) {
                $extension === 'pdf' => 'pdf',
                in_array($extension, self::AUDIO_EXTENSIONS, true) => 'audio',
                default => 'image',
            },
            'duration' => $this->attachment_duration,
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
            'audio' => 'Sent a voice message',
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
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'is_forwarded' => (bool) $this->is_forwarded,
            'reply_to' => $this->replyPreview($viewerUserId),
            'reactions' => $this->reactionList(),
            'is_pinned' => $this->pinned_at !== null,
            'pinned_at' => $this->pinned_at?->toIso8601String(),
        ];
    }

    public function replyPreview(?int $viewerUserId = null): ?array
    {
        $original = $this->replyTo;

        if (!$original) {
            return null;
        }

        return [
            'message_id' => $original->message_id,
            'sender_user_id' => $original->sender_user_id,
            'sender_type' => $original->sender_type,
            'is_mine' => $original->sender_user_id === $viewerUserId,
            'body' => $original->body ? Str::limit($original->body, 140) : null,
            'preview' => $original->preview(),
            'attachment_type' => $original->attachment()['type'] ?? null,
        ];
    }

    public function reactionList(): array
    {
        return $this->reactions
            ->map(fn($reaction) => [
                'user_id' => $reaction->user_id,
                'emoji' => $reaction->emoji,
            ])
            ->values()
            ->all();
    }
}