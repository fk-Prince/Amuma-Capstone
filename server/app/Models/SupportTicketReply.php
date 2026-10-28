<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    protected $primaryKey = 'support_ticket_reply_id';

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'attachment',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id', 'support_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
