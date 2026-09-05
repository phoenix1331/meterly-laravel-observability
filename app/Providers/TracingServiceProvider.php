<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\SpanExporterFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

/**
 * Wires the OpenTelemetry PHP SDK to Tempo, via Alloy's OTLP/HTTP
 * receiver. Spans export synchronously (SimpleSpanProcessor) rather
 * than batched in the background, since Octane's worker lifecycle
 * makes a queued batch exporter's "flush later" timing unreliable to
 * reason about for a teaching app where seeing the trace matters more
 * than shaving export latency.
 */
class TracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TracerProvider::class, function () {
            $resource = ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create([
                ResourceAttributes::SERVICE_NAME => config('app.name', 'meterly'),
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => config('app.env', 'local'),
            ])));

            $exporter = (new SpanExporterFactory)->create();
            $processor = new SimpleSpanProcessor($exporter);

            return new TracerProvider($processor, null, $resource);
        });

        $this->app->singleton(TracerInterface::class, fn ($app) => $app->make(TracerProvider::class)
            ->getTracer('meterly'));
    }
}
