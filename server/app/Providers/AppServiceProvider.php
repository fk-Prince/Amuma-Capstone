<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Observers\InvoiceAdjustmentObserver;
use App\Observers\InvoiceObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        InvoiceAdjustment::observe(InvoiceAdjustmentObserver::class);
    }
}
