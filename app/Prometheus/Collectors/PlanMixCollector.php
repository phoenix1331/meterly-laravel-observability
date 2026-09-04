<?php

declare(strict_types=1);

namespace App\Prometheus\Collectors;

use App\Models\Plan;
use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;

/**
 * Tenant count per plan. Labelled by plan slug, a small fixed set.
 */
class PlanMixCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('Plan mix')
            ->name('tenants_by_plan')
            ->helpText('Number of tenants on each plan.')
            ->labels(['plan'])
            ->value(function () {
                return Plan::query()
                    ->withCount('tenants')
                    ->get()
                    ->map(fn (Plan $plan) => [$plan->tenants_count, [$plan->slug]])
                    ->all();
            });
    }
}
