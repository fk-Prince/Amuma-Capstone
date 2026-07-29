<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $primaryKey = 'invoice_id';

    public $timestamps = false;

    protected $fillable = [
        'total',
        'status',
        'is_collected',
        'branch_id',
        'invoice_code'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'is_collected' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(InvoiceService::class, 'invoice_id', 'invoice_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->invoice_code =
                'INV-' . strtoupper(Str::random(8));
        });
    }
}
