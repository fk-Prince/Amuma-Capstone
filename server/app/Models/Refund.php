<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $primaryKey = 'refund_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_id',
        'amount',
        'refund_method',
        'reference_id',
        'status',
        'reason',
        'masked_card_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    protected static function booted()
    {
        static::creating(function ($refund) {
            if (!$refund->reference_id) {
                $lastRefund = self::lockForUpdate()
                    ->whereNotNull('reference_id')
                    ->orderByDesc('refund_id')
                    ->first();

                $nextNumber = $lastRefund
                    ? ((int) substr($lastRefund->reference_id, 7)) + 1
                    : 1;

                $refund->reference_id = 'REFUND-' . str_pad(
                    $nextNumber,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
