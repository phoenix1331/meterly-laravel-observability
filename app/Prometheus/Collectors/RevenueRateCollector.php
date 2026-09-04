<?php

declare(strict_types=1);

namespace App\Prometheus\Collectors;

use App\Models\Plan;
use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;

/**
 * Monthly recurring revenue in pence, from each tenant's plan price.
 * Labelled by plan slug so the dashboard can break revenue down by tier
 * as well as show the total.
 */
class RevenueRateCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('Revenue rate')
            ->name('revenue_rate_pence')
            ->helpText('Monthly recurring revenue in pence, by plan.')
            ->labels(['plan'])
            ->value(function () {
                return Plan::query()
                    ->withCount('tenants')
                    ->get()
                    ->map(fn (Plan $plan) => [
                        $plan->tenants_count * $plan->price_pence,
                        [$plan->slug],
                    ])
                    ->all();
            });
    }
}
