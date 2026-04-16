<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Auto-Sync Every Minute ──────────────────────────────────────────────────
// Only runs if this machine is marked as a Local POS
if (filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN)) {
    
    Schedule::command('pos:sync --tenant=2')
        ->everyMinute()
        ->withoutOverlapping() // Prevents running twice if previous sync is slow
        ->appendOutputTo(storage_path('logs/sync-auto.log')); // Keeps a log history
}




