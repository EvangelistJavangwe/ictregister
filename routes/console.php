<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule overdue reminders daily at 8am
Schedule::command('ict:send-overdue-reminders')->dailyAt('08:00');

// Automatic database backup every night at midnight
Schedule::command('ict:auto-backup')->dailyAt('00:00');

// Catch-up: if the VM/MySQL was off at midnight, this checks every minute
// and runs the missed backup as soon as the database becomes reachable again.
// --only-if-missed makes it a no-op once today's real backup has happened.
Schedule::command('ict:auto-backup --only-if-missed')
    ->everyMinute()
    ->skip(fn () => now()->format('H:i') === '00:00'); // avoid racing the dailyAt(00:00) run above
