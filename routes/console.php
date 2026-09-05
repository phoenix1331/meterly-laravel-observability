<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The "nightly billing close": see AggregateUsageEvents' docblock for
// why this specific job is what UsageAggregationJobStale alerts on.
// Only fires if a live process is running `php artisan schedule:work`
// (the scheduler service in docker-compose.yml). Laravel's scheduler
// does nothing on its own without one.
Schedule::command('app:aggregate-usage')->dailyAt('00:05');
