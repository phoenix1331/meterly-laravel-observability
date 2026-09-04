<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Holds the current request's trace ID so every log line can carry it.
 *
 * Until real OpenTelemetry spans exist, this generates a request-scoped
 * correlation ID. Once tracing is wired up, the span's own trace ID
 * should be set here instead, so log-to-trace correlation in Grafana
 * keeps working without a log format change.
 */
class TraceContext
{
    private ?string $traceId = null;

    public function id(): string
    {
        return $this->traceId ??= (string) Str::ulid();
    }

    public function set(string $traceId): void
    {
        $this->traceId = $traceId;
    }
}
