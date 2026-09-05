<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PrometheusServiceProvider;
use App\Providers\TracingServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    PrometheusServiceProvider::class,
    TracingServiceProvider::class,
];
