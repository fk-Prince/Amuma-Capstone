<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchContract extends Model
{
    protected $table = 'branch_contracts';

    protected $primaryKey = 'branch_contract_id';

    protected $fillable = [
        'branch_id',
        'category',
        'accommodation_type',
        'price',
        'billing_cycle',
        'is_active',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
