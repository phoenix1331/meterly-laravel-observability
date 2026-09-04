<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\TraceContext;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Stamps every log record with the current request's trace ID, so a
 * line in Loki can be jumped to from its matching span in Tempo.
 *
 * Resolves TraceContext fresh from the container on every record rather
 * than taking it as a constructor dependency: under Octane, Laravel's
 * LogManager caches built channels (and therefore this processor) across
 * requests within the same worker, so a constructor-injected TraceContext
 * would keep the trace ID from whichever request first built the channel.
 */
class TraceIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: [
            ...$record->extra,
            'trace_id' => app(TraceContext::class)->id(),
        ]);
    }
}
