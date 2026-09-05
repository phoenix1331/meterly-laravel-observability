<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a request counter and a latency histogram for every request.
 *
 * Labels are kept to a bounded set on purpose: method, route pattern,
 * status, and tenant ID (bounded by the number of tenants). A request
 * ID or any other unbounded value must never become a label here, or
 * it will blow up cardinality in Prometheus.
 *
 * Third of three middleware appended to the 'api' group (see
 * TraceRequest). Runs innermost, so $duration below covers only the
 * actual route handling, not the other two middleware's own overhead.
 */
class RecordRequestMetrics
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $start;

        $labels = [
            $request->method(),
            $request->route()?->uri() ?? 'unmatched',
            (string) $response->getStatusCode(),
            $this->tenantLabel($request),
        ];

        $this->registry->getOrRegisterCounter(
            namespace: config('prometheus.default_namespace'),
            name: 'http_requests_total',
            help: 'Total number of HTTP requests.',
            labels: ['method', 'route', 'status', 'tenant_id'],
        )->inc($labels);

        $this->registry->getOrRegisterHistogram(
            namespace: config('prometheus.default_namespace'),
            name: 'http_request_duration_seconds',
            help: 'HTTP request latency in seconds.',
            labels: ['method', 'route', 'status', 'tenant_id'],
            buckets: [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        )->observe($duration, $labels);

        return $response;
    }

    private function tenantLabel(Request $request): string
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant');

        return $tenant?->id !== null ? (string) $tenant->id : 'none';
    }
}
