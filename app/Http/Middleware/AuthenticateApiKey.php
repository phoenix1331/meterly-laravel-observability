<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First of two route-level middleware on /api/usage (aliased 'api-key',
 * registered before 'usage-quota' in routes/api.php, since EnforceUsageQuota
 * needs the tenant this resolves). Looks the bearer token up by its
 * SHA-256 hash, not the plaintext, since only the hash is ever stored.
 * Sets 'tenant' and 'apiKey' request attributes that every downstream
 * middleware and the controller read.
 */
class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return response()->json(['message' => 'API key required.'], 401);
        }

        $apiKey = ApiKey::query()
            ->with('tenant')
            ->where('hash', hash('sha256', $token))
            ->first();

        if ($apiKey === null || $apiKey->isRevoked()) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('tenant', $apiKey->tenant);
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
