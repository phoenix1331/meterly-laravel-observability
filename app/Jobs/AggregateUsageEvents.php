<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\UsageAggregate;
use App\Models\UsageEvent;
use App\Support\JobHeartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Stands in for the brief's "nightly billing close": the one background
 * job in this thin slice, queued on Horizon and scheduled daily at
 * 00:05 (routes/console.php). Also runnable synchronously via
 * `php artisan app:aggregate-usage`.
 *
 * Calls JobHeartbeat::recordSuccess() on completion so
 * JobHeartbeatCollector can expose "seconds since this last succeeded"
 * as a Prometheus gauge, the metric UsageAggregationJobStale alerts on
 * when the close silently stops running, not just when it errors.
 */
class AggregateUsageEvents implements ShouldQueue
{
    use Queueable;

    /** Matches the label JobHeartbeatCollector reads back and the alert rule filters on. */
    public const HEARTBEAT_NAME = 'usage_aggregation';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ?Carbon $date = null,
    ) {}

    /**
     * Roll up raw usage events into a per-tenant daily total.
     * Upserts on (tenant_id, date), so re-running for the same
     * day recomputes rather than double-counts.
     */
    public function handle(TracerInterface $tracer, JobHeartbeat $heartbeat): void
    {
        $date = ($this->date ?? now())->startOfDay();

        $span = $tracer->spanBuilder('usage-aggregation.run')
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('aggregation.date', $date->toDateString())
            ->startSpan();

        $scope = $span->activate();

        try {
            $tenantCount = 0;

            Tenant::query()
                ->whereHas('usageEvents', function ($query) use ($date): void {
                    $query->whereBetween('created_at', [$date, $date->copy()->endOfDay()]);
                })
                ->each(function (Tenant $tenant) use ($date, &$tenantCount): void {
                    $count = UsageEvent::query()
                        ->where('tenant_id', $tenant->id)
                        ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                        ->count();

                    UsageAggregate::query()->updateOrCreate(
                        ['tenant_id' => $tenant->id, 'date' => $date->toDateString()],
                        ['request_count' => $count],
                    );

                    $tenantCount++;
                });

            $span->setAttribute('aggregation.tenant_count', $tenantCount);

            $heartbeat->recordSuccess(self::HEARTBEAT_NAME);
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
