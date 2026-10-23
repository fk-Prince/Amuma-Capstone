<?php

use Illuminate\Support\Facades\Schedule;



Schedule::command('bookings:reject-expired')->everyMinute();
Schedule::command('schedules:mark-missed')->everyMinute();
Schedule::command('subscriptions:apply-pending-plans')->dailyAt('00:05');
Schedule::command('subscriptions:mark-expired')->dailyAt('01:00');
Schedule::command('sandbox:sync-locks')->everyTenMinutes();
Schedule::command('subscriptions:remind-renewals')->dailyAt('08:00');
Schedule::command('admissions:remind-discharge')->dailyAt('08:30');
Schedule::command('diagnosis-cases:bill-recurring')->dailyAt('06:00');
