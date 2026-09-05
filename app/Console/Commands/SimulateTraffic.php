<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\Tenant;
use App\Support\FraudCheckSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Drives synthetic traffic against the metered endpoint with a diurnal
 * (sine wave) request rate, so dashboards have something worth looking
 * at. Partway through the run it scripts one incident: the stubbed
 * fraud-check dependency's error rate spikes for a stretch, giving the
 * alert (and the correlation triangle) a real failure to catch.
 */
#[Signature('app:simulate-traffic
    {--duration=600 : Total run time in seconds}
    {--cycle=300 : Length of one full diurnal cycle in seconds}
    {--peak-rps=5 : Requests per second at the peak of the cycle}
    {--incident-at=120 : Seconds into the run when the scripted incident starts}
    {--incident-duration=180 : How long the scripted incident lasts, in seconds. Must comfortably exceed the alert rule\'s "for" duration (2m), since the alert only fires once the error rate has been sustained that long}
    {--base-url= : Base URL of the app, defaults to APP_URL}')]
#[Description('Simulate diurnal traffic against the metered endpoint, with one scripted incident')]
class SimulateTraffic extends Command
{
    private const TENANT_COUNT = 5;

    public function handle(FraudCheckSettings $settings): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        $duration = (int) $this->option('duration');
        $cycle = (int) $this->option('cycle');
        $peakRps = (float) $this->option('peak-rps');
        $incidentAt = (int) $this->option('incident-at');
        $incidentDuration = (int) $this->option('incident-duration');

        $tokens = $this->simulatorTokens();

        $this->info("Simulating traffic against {$baseUrl} for {$duration}s (cycle {$cycle}s, peak {$peakRps} req/s).");

        $start = microtime(true);
        $incidentStarted = false;
        $incidentEnded = false;
        $interrupted = false;

        $this->trap([SIGINT, SIGTERM], function () use (&$interrupted): void {
            $interrupted = true;
        });

        while (! $interrupted && (microtime(true) - $start) < $duration) {
            $elapsed = microtime(true) - $start;

            if (! $incidentStarted && $elapsed >= $incidentAt) {
                $this->warn("Incident started at {$elapsed}s: fraud-check error rate spiking.");
                $settings->startIncident(latencyMs: 800, errorRate: 0.9, ttlSeconds: $incidentDuration + 30);
                $incidentStarted = true;
            }

            if ($incidentStarted && ! $incidentEnded && $elapsed >= $incidentAt + $incidentDuration) {
                $this->info("Incident ended at {$elapsed}s: fraud-check back to normal.");
                $settings->endIncident();
                $incidentEnded = true;
            }

            $rps = $this->diurnalRate($elapsed, $cycle, $peakRps);
            $intervalSeconds = $rps > 0 ? 1 / $rps : 1.0;

            $token = $tokens[array_rand($tokens)];

            // 201 success, 401 an occasional stale/deleted key, 429 quota
            // exceeded (expected once a tenant burns through a small plan),
            // 502 the scripted incident. Anything else is a real bug.
            Http::withToken($token, 'Bearer')
                ->timeout(5)
                ->post("{$baseUrl}/api/usage")
                ->throwUnlessStatus(fn (int $status) => in_array($status, [201, 401, 429, 502], true));

            usleep((int) ($intervalSeconds * 1_000_000));
        }

        if ($incidentStarted && ! $incidentEnded) {
            $settings->endIncident();
        }

        $this->info($interrupted ? 'Simulation interrupted.' : 'Simulation complete.');

        return self::SUCCESS;
    }

    /**
     * Requests per second at a point in the cycle, shaped like a diurnal
     * curve: a sine wave oscillating between roughly 10% and 100% of the
     * peak rate, never dropping to zero.
     */
    private function diurnalRate(float $elapsedSeconds, int $cycleSeconds, float $peakRps): float
    {
        $phase = ($elapsedSeconds / $cycleSeconds) * 2 * M_PI;
        $wave = (sin($phase - M_PI / 2) + 1) / 2;

        return $peakRps * (0.1 + 0.9 * $wave);
    }

    /**
     * @return list<string> plaintext bearer tokens for the simulator's own tenants
     */
    private function simulatorTokens(): array
    {
        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        $tokens = [];

        for ($i = 1; $i <= self::TENANT_COUNT; $i++) {
            $email = "simulator-{$i}@meterly.test";

            $tenant = Tenant::query()->firstOrCreate(
                ['email' => $email],
                ['plan_id' => $plan->id, 'name' => "Simulator Tenant {$i}"],
            );

            $existingKey = ApiKey::query()->where('tenant_id', $tenant->id)->first();

            if ($existingKey !== null) {
                // Plaintext tokens are never stored, so a key created by a
                // previous run can't be reused: replace it with a fresh one.
                $existingKey->delete();
            }

            $token = ApiKey::generateToken();

            ApiKey::create([
                'tenant_id' => $tenant->id,
                'name' => 'simulator key',
                'prefix' => $token['prefix'],
                'hash' => $token['hash'],
            ]);

            $tokens[] = $token['plaintext'];
        }

        return $tokens;
    }
}
