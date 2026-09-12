<?php

namespace App\Observers;

use App\Models\InvoiceAdjustment;
use App\Service\RefundService;

class InvoiceAdjustmentObserver
{
    public function created(InvoiceAdjustment $adjustment): void
    {
        app(RefundService::class)->creditFromAdjustment($adjustment);
    }
}
