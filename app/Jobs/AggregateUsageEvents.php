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

class AggregateUsageEvents implements ShouldQueue
{
    use Queueable;

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
