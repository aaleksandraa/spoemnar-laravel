<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')
    ->when(fn (): bool => (bool) config('backup.enabled', true))
    ->dailyAt((string) config('backup.schedule.daily_at', '00:30'))
    ->timezone((string) config('backup.schedule.timezone', config('app.timezone', 'UTC')))
    ->withoutOverlapping(180);

Schedule::command('memorial-candles:expire')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);
