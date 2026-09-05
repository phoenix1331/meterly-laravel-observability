<?php

declare(strict_types=1);

namespace App\Prometheus\Collectors;

use App\Models\Tenant;
use Spatie\Prometheus\Collectors\Collector;
use Spatie\Prometheus\Facades\Prometheus;

/**
 * Fraction of the monthly quota each tenant has burnt, from 0.0 to 1.0+.
 * Labelled by tenant ID and plan slug: both are bounded by the number of
 * tenants and plans, so cardinality stays safe.
 *
 * Deliberately calls Tenant::usageThisMonth(), the same method
 * EnforceUsageQuota uses to block a request, so this gauge and that
 * real-time check can never disagree about what "burnt" means.
 */
class QuotaBurnCollector implements Collector
{
    public function register(): void
    {
        Prometheus::addGauge('Quota burn')
            ->name('tenant_quota_burn_ratio')
            ->helpText('Fraction of the monthly quota burnt per tenant.')
            ->labels(['tenant_id', 'plan'])
            ->value(function () {
                return Tenant::query()
                    ->with('plan')
                    ->get()
                    ->map(function (Tenant $tenant) {
                        $quota = $tenant->plan->monthly_quota;
                        $ratio = $quota > 0 ? $tenant->usageThisMonth() / $quota : 0.0;

                        return [$ratio, [(string) $tenant->id, $tenant->plan->slug]];
                    })
                    ->all();
            });
    }
}
