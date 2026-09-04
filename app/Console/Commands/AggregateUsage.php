<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AggregateUsageEvents;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:aggregate-usage {--date= : Date to aggregate, defaults to today (Y-m-d)}')]
#[Description('Roll up raw usage events into per-tenant daily totals')]
class AggregateUsage extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date');
        $target = $date !== null ? Carbon::parse($date) : now();

        AggregateUsageEvents::dispatchSync($target);

        $this->info("Aggregated usage for {$target->toDateString()}.");

        return self::SUCCESS;
    }
}
