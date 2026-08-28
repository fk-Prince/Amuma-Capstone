<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $primaryKey = 'conversation_id';

    public const TYPE_FAMILY = 'family';
    public const TYPE_STAFF = 'staff';

    protected $fillable = [
        'branch_id',
        'type',
        'client_id',
        'patient_id',
        'employee_one_id',
        'employee_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class,
            'conversation_id',
            'conversation_id'
        );
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(
            Message::class,
            'conversation_id',
            'conversation_id'
        )->latestOfMany('message_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function employeeOne(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_one_id', 'employee_id');
    }

    public function employeeTwo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_two_id', 'employee_id');
    }

    public function isStaffThread(): bool
    {
        return $this->type === self::TYPE_STAFF;
    }

    /**
     * On a staff thread both sides send as "staff", so unread is counted by
     * who sent it rather than which side they are on.
     */
    public function unreadForUser(int $userId): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', $userId)
            ->count();
    }
}
