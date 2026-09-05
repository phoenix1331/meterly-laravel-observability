<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

// The one metered endpoint in this thin slice. api-key resolves the
// tenant and rejects an unknown/revoked key (401); usage-quota then
// rejects an over-quota tenant (429) before the controller runs.
Route::middleware(['api-key', 'usage-quota'])->group(function (): void {
    Route::post('/usage', UsageController::class);
});
