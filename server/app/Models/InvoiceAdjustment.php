<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdjustment extends Model
{
    protected $table = 'invoice_adjustments';

    protected $primaryKey = 'invoice_adjustment_id';

    public const TYPE_REFUND = 'refund';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_TERMINATION_FEE = 'termination_fee';


    protected $fillable = [
        'invoice_id',
        'type',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class,
            'invoice_id',
            'invoice_id'
        );
    }
}
