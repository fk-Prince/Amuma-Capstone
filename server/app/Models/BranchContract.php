<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchContract extends Model
{
    protected $table = 'branch_contracts';

    public const CAREGORY_HOMECARE = 'Homecare';
    public const CAREGORY_FACILITY = 'Facility';

    public const ACCOMMODATION_TYPE_ADL = 'ADL';
    public const ACCOMMODATION_TYPE_VIP = 'VIP';
    public const ACCOMMODATION_TYPE_COMMON = 'Common';

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

    public function invoices()
    {
        return $this->hasMany(InvoiceFacility::class, 'branch_contract_id', 'branch_contract_id');
    }
}
