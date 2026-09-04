<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\FraudCheckFailedException;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Tenant;
use App\Models\UsageEvent;
use App\Services\FraudCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UsageController extends Controller
{
    public function __construct(
        private readonly FraudCheckService $fraudCheck,
    ) {}

    /**
     * Record a usage event for the authenticated tenant.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Tenant $tenant */
        $tenant = $request->attributes->get('tenant');

        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        try {
            $this->fraudCheck->check($tenant);
        } catch (FraudCheckFailedException $exception) {
            Log::warning('Fraud check failed for usage event.', [
                'tenant_id' => $tenant->id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Upstream fraud check failed.'], 502);
        }

        $event = UsageEvent::create([
            'tenant_id' => $tenant->id,
            'api_key_id' => $apiKey->id,
            'endpoint' => $request->path(),
            'created_at' => now(),
        ]);

        Log::info('Usage event recorded.', [
            'tenant_id' => $tenant->id,
            'usage_event_id' => $event->id,
            'endpoint' => $event->endpoint,
        ]);

        return response()->json([
            'id' => $event->id,
            'tenant' => $tenant->name,
            'recorded_at' => $event->created_at,
        ], 201);
    }
}
