<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Records when a named recurring job last completed successfully, so a
 * Prometheus gauge can expose "seconds since last success" and an
 * absence alert can fire when a job silently stops running, rather
 * than only alerting on jobs that actively fail.
 */
class JobHeartbeat
{
    private const CACHE_PREFIX = 'job_heartbeat:';

    public function recordSuccess(string $jobName): void
    {
        Cache::forever(self::CACHE_PREFIX.$jobName, Carbon::now()->timestamp);
    }

    public function secondsSinceLastSuccess(string $jobName): ?int
    {
        $timestamp = Cache::get(self::CACHE_PREFIX.$jobName);

        if ($timestamp === null) {
            return null;
        }

        return Carbon::now()->timestamp - $timestamp;
    }
}
