<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Patient;
use App\Observers\BookingObserver;
use App\Observers\ClientObserver;
use App\Observers\PatientObserver;
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


    public function boot(): void {}
}
