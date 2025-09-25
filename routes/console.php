<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dtr:send-reminders')->hourly(); // ->hourly();->everyMinute();

Schedule::command('backup:run')->daily()->at('01:00'); // set preferred time

// Payroll cutoff reminder (new one)
Schedule::command('payroll:send-cutoff-reminder')
    ->dailyAt('12:00')
    ->timezone('Asia/Manila');

//Schedule::command('payroll:send-cutoff-reminder')->everyMinute();
