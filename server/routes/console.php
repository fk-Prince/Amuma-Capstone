<?php

use Illuminate\Support\Facades\Schedule;



Schedule::command('bookings:reject-expired')
    ->everyFourHours();

Schedule::command('schedules:mark-missed')->everyMinute();
