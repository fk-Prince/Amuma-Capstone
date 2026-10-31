<?php

namespace App\Models;

use App\Models\Message as ModelsMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;

class MessageReaction extends Model
{
    protected $primaryKey = 'message_reaction_id';

    protected $fillable = [
        'message_id',
        'user_id',
        'emoji',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id', 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}