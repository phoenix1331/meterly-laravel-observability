<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FraudCheckFailedException;
use App\Models\Tenant;
use App\Support\FraudCheckSettings;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Stands in for a third-party fraud-check API called before a usage
 * event is recorded. Latency and error rate are env-driven so the
 * traffic simulator can dial in a scripted incident without a code
 * change or restart.
 */
class FraudCheckService
{
    public function __construct(
        private readonly TracerInterface $tracer,
        private readonly FraudCheckSettings $settings,
    ) {}

    public function check(Tenant $tenant): bool
    {
        $span = $this->tracer->spanBuilder('fraud-check.check')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('tenant.id', $tenant->id)
            ->startSpan();

        $scope = $span->activate();

        try {
            $latencyMs = $this->settings->latencyMs();

            if ($latencyMs > 0) {
                usleep($latencyMs * 1_000);
            }

            $errorRate = $this->settings->errorRate();

            if ($errorRate > 0 && (mt_rand() / mt_getrandmax()) < $errorRate) {
                $exception = new FraudCheckFailedException("Fraud check failed for tenant {$tenant->id}.");

                $span->recordException($exception);
                $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());

                throw $exception;
            }

            return true;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
