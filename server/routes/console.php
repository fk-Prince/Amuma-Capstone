<?php

use Illuminate\Support\Facades\Schedule;



Schedule::command('bookings:reject-expired')->everyMinute();
Schedule::command('schedules:mark-missed')->everyMinute();
