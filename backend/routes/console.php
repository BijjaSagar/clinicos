<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file defines Artisan commands and scheduled tasks for ClinicOS.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Run the Laravel scheduler every minute via cron:
// * * * * * php /path/to/clinicos/artisan schedule:run >> /dev/null 2>&1

Schedule::command('queue:work --stop-when-empty')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Send automated WhatsApp reminders:
// - 24h / 2h appointment reminders
// - follow-up due reminders
// - pending payment reminders
// - birthday greetings
Schedule::command('whatsapp:send-reminders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Reconcile Razorpay payments with local invoice records (daily at 2am)
Schedule::command('razorpay:reconcile')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Database backup daily at 3am
Schedule::command('backup:database --keep=30')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

// Archive old audit logs monthly
Schedule::command('activitylog:clean --days=365')
    ->monthly();
