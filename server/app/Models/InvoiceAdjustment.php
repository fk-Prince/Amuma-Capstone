<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdjustment extends Model
{
    protected $table = 'invoice_adjustments';

    protected $primaryKey = 'invoice_adjustment_id';

    public const UPDATED_AT = null;

    public const TYPE_REFUND = 'refund';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_VOID = 'void';


    protected $fillable = [
        'invoice_id',
        'type',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }
}
