<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-subscription-reminders')->dailyAt('08:00');
Schedule::command('backup:clean')->daily();
Schedule::command('backup:run')->daily();
