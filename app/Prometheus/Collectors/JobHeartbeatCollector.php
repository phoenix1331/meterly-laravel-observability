<?php

declare(strict_types=1);

namespace App\Prometheus\Collectors;

use App\Jobs\AggregateUsageEvents;
use App\Support\JobHeartbeat;
use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;

/**
 * Seconds since each named recurring job last completed successfully.
 * A large, ever-growing value is what a staleness alert should catch:
 * a job that has silently stopped running rather than one that is
 * failing loudly.
 *
 * Always emits a value for every known job, including ones that have
 * never run (using a large sentinel). The underlying Prometheus client
 * has no way to remove a gauge's stored value for a given label set,
 * so a series that was once present but stops being set just freezes
 * at its last value instead of disappearing — an absent() alert would
 * never fire once a job had run at least once. Emitting unconditionally
 * avoids relying on that and keeps the metric a true "time since last
 * success" the whole time the app has been up.
 */
class JobHeartbeatCollector implements Collector
{
    private const JOBS = [
        AggregateUsageEvents::HEARTBEAT_NAME,
    ];

    /**
     * Larger than any realistic staleness threshold, so a job that has
     * never run reads as "very stale" rather than being missing.
     */
    private const NEVER_RUN_SENTINEL_SECONDS = 999_999;

    public function register(): void
    {
        Prometheus::addGauge('Job heartbeat age')
            ->name('job_heartbeat_seconds_since_success')
            ->helpText('Seconds since a named recurring job last completed successfully.')
            // "job" is a reserved Prometheus label auto-injected from the
            // scrape config, so a custom label with that name gets silently
            // renamed to "exported_job" instead of matching queries as
            // expected. Using "job_name" avoids the collision entirely.
            ->labels(['job_name'])
            ->value(function () {
                $heartbeat = app(JobHeartbeat::class);

                return collect(self::JOBS)
                    ->map(fn (string $job) => [
                        $heartbeat->secondsSinceLastSuccess($job) ?? self::NEVER_RUN_SENTINEL_SECONDS,
                        [$job],
                    ])
                    ->all();
            });
    }
}
