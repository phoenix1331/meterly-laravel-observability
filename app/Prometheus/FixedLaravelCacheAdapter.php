<?php

declare(strict_types=1);

namespace App\Prometheus;

use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\MetricFamilySamples;
use Prometheus\Summary;
use Spatie\Prometheus\Adapters\LaravelCacheAdapter;

/**
 * spatie/laravel-prometheus 1.6.1's LaravelCacheAdapter::collect() fetches
 * each metric type from the cache but never assigns the result back onto
 * $this->counters/$this->gauges/$this->histograms/$this->summaries, so
 * parent::collect() always sees empty in-process storage and every scrape
 * is empty. This subclass fixes that by wiring the fetch results before
 * delegating, matching how the package's own update*() methods already do it.
 *
 * @return MetricFamilySamples[]
 */
class FixedLaravelCacheAdapter extends LaravelCacheAdapter
{
    public function collect(bool $sortMetrics = true): array
    {
        $this->counters = $this->fetch(Counter::TYPE);
        $this->gauges = $this->fetch(Gauge::TYPE);
        $this->histograms = $this->fetch(Histogram::TYPE);
        $this->summaries = $this->fetch(Summary::TYPE);

        return parent::collect($sortMetrics);
    }
}
