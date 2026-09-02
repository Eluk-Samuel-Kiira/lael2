<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ── Auto-Sync Every Minute ──────────────────────────────────────────────────
if (filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN)) {
    Schedule::command('pos:sync --tenant=2')
        ->everyMinute()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/sync-auto.log'));
}

// ── Deactivate Expired Promotions ────────────────────────────────────────
Schedule::command('promotions:deactivate-expired')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/promotions-deactivate.log'));

// ── Daily Report at 8pm EAT ───────────────────────────────────────────────
Schedule::command('report:send:daily')
    ->dailyAt('17:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports-daily.log'));

// ── Weekly Report every Sunday at 8pm EAT ────────────────────────────────
Schedule::command('report:send:weekly')
    ->weeklyOn(7, '17:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports-weekly.log'));

// ── Monthly Report on 1st at 8pm EAT ─────────────────────────────────────
Schedule::command('report:send:monthly')
    ->monthlyOn(1, '17:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reports-monthly.log'));

// Runs every 3 days at 9am EAT (6am UTC)
Schedule::command('stock:check-low')
    ->cron('0 6 */3 * *') // At 06:00 UTC every 3 days
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/stock-low-check.log'));




// ── Send Report on Demand (Optional) ─────────────────────────────────────
// You can also run these manually with:
// php artisan report:send:daily
// php artisan report:send:weekly
// php artisan report:send:monthly