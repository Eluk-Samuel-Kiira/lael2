<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('pos:sync')
        ->everyThirtySeconds()
        ->withoutOverlapping(2)
        ->runInBackground();

// Disable-ScheduledTask -TaskName "LaravelPOSScheduler"
// Enable-ScheduledTask -TaskName "LaravelPOSScheduler"



