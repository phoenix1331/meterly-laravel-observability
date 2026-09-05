<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Runtime-overridable settings for the stubbed fraud-check dependency.
 *
 * Falls back to the env-configured defaults in config/services.php.
 * The cache override exists so the traffic simulator can script an
 * incident (a latency or error-rate spike) without restarting the
 * app container, and clear it again once the incident is over.
 */
class FraudCheckSettings
{
    private const CACHE_KEY = 'fraud_check:incident_override';

    public function latencyMs(): int
    {
        return $this->override()['latency_ms'] ?? config('services.fraud_check.latency_ms');
    }

    public function errorRate(): float
    {
        return $this->override()['error_rate'] ?? config('services.fraud_check.error_rate');
    }

    public function isIncidentActive(): bool
    {
        return $this->override() !== null;
    }

    /**
     * @param  int|null  $latencyMs  Null to leave the default latency in place.
     * @param  float|null  $errorRate  Null to leave the default error rate in place.
     * @param  int  $ttlSeconds  Safety net: the override expires on its own after
     *                           this long, in case whatever started it never calls
     *                           endIncident() (a killed process, a lost signal).
     */
    public function startIncident(?int $latencyMs = null, ?float $errorRate = null, int $ttlSeconds = 900): void
    {
        Cache::put(self::CACHE_KEY, [
            'latency_ms' => $latencyMs,
            'error_rate' => $errorRate,
        ], $ttlSeconds);
    }

    public function endIncident(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{latency_ms: int|null, error_rate: float|null}|null
     */
    private function override(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }
}
