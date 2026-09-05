<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TraceContext;
use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens the root span for the request, so every span created downstream
 * (the fraud check, the queued aggregation job when dispatched from a
 * request) nests underneath it in the same trace.
 */
class TraceRequest
{
    public function __construct(
        private readonly TracerInterface $tracer,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $span = $this->tracer->spanBuilder($request->method().' '.($request->route()?->uri() ?? $request->path()))
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttribute('http.method', $request->method())
            ->setAttribute('http.route', $request->route()?->uri())
            ->setAttribute('http.target', $request->path())
            ->startSpan();

        app(TraceContext::class)->set($span->getContext()->getTraceId());

        $scope = $span->activate();

        try {
            $response = $next($request);

            $span->setAttribute('http.status_code', $response->getStatusCode());

            if ($response->getStatusCode() >= 500) {
                $span->setStatus(StatusCode::STATUS_ERROR);
            }

            return $response;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
