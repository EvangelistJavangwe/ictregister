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
