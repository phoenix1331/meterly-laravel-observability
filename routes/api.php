<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api-key')->group(function (): void {
    Route::get('/ping', function (Request $request) {
        return response()->json([
            'tenant' => $request->attributes->get('tenant')->name,
        ]);
    });
});
