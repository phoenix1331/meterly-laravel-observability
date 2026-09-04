<?php

declare(strict_types=1);

use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-key')->group(function (): void {
    Route::post('/usage', UsageController::class);
});
