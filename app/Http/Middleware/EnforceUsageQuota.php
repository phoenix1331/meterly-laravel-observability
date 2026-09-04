<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceUsageQuota
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        if ($tenant->hasExceededQuota()) {
            return response()->json([
                'message' => 'Monthly quota exceeded.',
                'quota' => $tenant->plan->monthly_quota,
                'used' => $tenant->usageThisMonth(),
            ], 429);
        }

        return $next($request);
    }
}
