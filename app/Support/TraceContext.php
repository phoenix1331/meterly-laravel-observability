<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Holds the current request's trace ID so every log line can carry it
 * (see TraceIdProcessor) and so it can be echoed back on the response
 * (see AttachTraceId).
 *
 * TraceRequest sets the real OpenTelemetry trace ID here as soon as it
 * opens the request's root span. The lazy id()/Str::ulid() fallback
 * below only fires for code paths that log without ever going through
 * TraceRequest: an artisan command, a queue worker outside a request.
 * So a log line still gets *some* stable correlation ID even without a
 * trace behind it.
 *
 * Bound scoped() in AppServiceProvider, so each Octane request gets its
 * own instance. Must be resolved fresh per read, never cached in a
 * constructor that outlives the request: see TraceIdProcessor's
 * docblock for what goes wrong if you do that.
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
