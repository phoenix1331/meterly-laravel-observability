<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FraudCheckFailedException;
use App\Models\Tenant;

/**
 * Stands in for a third-party fraud-check API called before a usage
 * event is recorded. Latency and error rate are env-driven so the
 * traffic simulator can dial in a scripted incident without a code
 * change or restart.
 */
class FraudCheckService
{
    public function check(Tenant $tenant): bool
    {
        $latencyMs = config('services.fraud_check.latency_ms');

        if ($latencyMs > 0) {
            usleep($latencyMs * 1_000);
        }

        $errorRate = config('services.fraud_check.error_rate');

        if ($errorRate > 0 && (mt_rand() / mt_getrandmax()) < $errorRate) {
            throw new FraudCheckFailedException("Fraud check failed for tenant {$tenant->id}.");
        }

        return true;
    }
}
