<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TraceContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exposes the request's trace ID on the response so a caller can quote
 * it back when reporting an issue, and it shows up in access logs.
 *
 * Second of three middleware appended to the 'api' group (see
 * TraceRequest). Runs inside TraceRequest's span, so the ID it reads
 * here is already the real one, not the ULID fallback TraceContext
 * would otherwise generate.
 */
class AttachTraceId
{
    public function __construct(
        private readonly TraceContext $traceContext,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Trace-Id', $this->traceContext->id());

        return $response;
    }
}
