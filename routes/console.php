<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checks daily; the command itself only notifies on the day 3 days before
// month-end, and only if that month has no expenses recorded yet.
Schedule::command('expenses:check-monthly-input')->dailyAt('08:00');

// Checks daily; the command itself only notifies for ongoing projects that
// have gone 5+ days without a material usage log entry, repeating every
// 5 days until a new log is recorded.
Schedule::command('materials:check-usage-logging')->dailyAt('08:15');

// Runs only on Saturdays; skipped entirely if salary was already recorded
// for the current (Monday-start) pay period.
Schedule::command('salary:check-recorded')->saturdays()->at('09:00');
