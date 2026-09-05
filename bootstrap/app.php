<?php

use App\Http\Middleware\AttachTraceId;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceUsageQuota;
use App\Http\Middleware\RecordRequestMetrics;
use App\Http\Middleware\TraceRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api-key' => AuthenticateApiKey::class,
            'usage-quota' => EnforceUsageQuota::class,
        ]);

        $middleware->appendToGroup('api', TraceRequest::class);
        $middleware->appendToGroup('api', AttachTraceId::class);
        $middleware->appendToGroup('api', RecordRequestMetrics::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
