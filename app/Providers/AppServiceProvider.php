<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\TraceContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // scoped(), not singleton(): Octane reuses the worker process
        // across requests, so a singleton would leak one request's trace
        // ID into the next. scoped() bindings are flushed by Octane
        // between requests (see TraceIdProcessor's docblock for what
        // happens when something *doesn't* get re-resolved per request).
        $this->app->scoped(TraceContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
