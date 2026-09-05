# Meterly

<img width="3172" height="1676" alt="Screenshot 2026-09-05 131338" src="https://github.com/user-attachments/assets/0371564c-ae26-4e0e-b3dd-7e3953a8841f" />


A usage-metered API platform on Laravel 13 that demonstrates production observability end to end. Metrics, logs, and traces flow into Prometheus, Loki, and Tempo with correlated trace IDs, so a scripted incident can be followed from a Grafana alert to the exact trace and log line, all running locally with one command.

## What this demonstrates

Most observability tutorials stop at "here is a request counter on a chart". Meterly demonstrates the full loop that actually matters in production: starting from an alert, narrowing to a trace, and landing on the log line that explains it.

The domain is a multi-tenant API product on purpose: customers hold an API key, call a metered endpoint against a plan quota, and are billed on usage. That shape naturally produces both technical signals (latency, error rate, queue depth) and business signals (quota burn, revenue rate, plan mix), and it has realistic failure modes: a customer hits a hard limit, a dependency starts timing out, a nightly job silently stops running.

Each of the following has a visible payoff in the running system:

1. Why metrics, logs, and traces are separate stores, and the cost/detail tradeoff between them
2. Instrumenting Laravel: middleware counters, histograms, and what belongs on a label versus a log field
3. Cardinality, and why a tenant ID is safe on a metric but a request ID will destroy it
4. Wiring the correlation triangle: exemplars, derived fields, and span-to-log links
5. Designing alerts people will not mute: sustained-rate thresholds and absence alerting for jobs that fail to run
6. Dashboards and alert rules as code, so nothing is configured by clicking
7. Reading it all under load, using the traffic simulator's scripted incident as a test for the alerts

## What's implemented (version one)

This is a deliberately thin, complete slice, not the full brief. Everything below is wired end to end and verified working, not just scaffolded:

- API key authentication, one metered endpoint (`POST /api/usage`), per-tenant monthly quota enforcement (429 once burnt)
- One background job (`AggregateUsageEvents`) on Horizon, scheduled daily and runnable on demand, with a heartbeat used for absence alerting
- One stubbed external dependency (`FraudCheckService`) with env-configured latency/error rate, plus a runtime override so the traffic simulator can script an incident without restarting anything
- Metrics (Prometheus), logs (Loki via Alloy), and traces (Tempo via OpenTelemetry) all flowing, with the same trace ID on the HTTP response header, every log line, and the trace itself
- The correlation triangle: Prometheus exemplars (via Tempo's span-metrics), Grafana derived fields (log to trace), and Tempo's trace-to-logs (trace to log), all three legs verified by hand against real data, not just configured
- A traffic simulator with a diurnal (sine-wave) request rate and one scripted incident, wired into `make demo`
- One provisioned Grafana dashboard covering technical and business signals, one provisioned alert group (sustained error-rate + job-staleness/absence), both as code under `docker/observability/`

**Not implemented** (see [Extending it](#extending-it) below): Sentry/GlitchTip error tracking, outbound webhooks with retries, Stripe usage sync, 30-day backfilled history, Mimir and object storage. These are the brief's "after the thin slice" chapters, each adding a genuinely new observability lesson rather than just more application surface, so they're deliberately deferred rather than half-built.

## Tech stack

- **Laravel 13.17** on PHP 8.4, served by **Octane** on **FrankenPHP**
- **MySQL 8.4**, **Redis 7** (queues via **Horizon**, cache)
- **OpenTelemetry PHP SDK** for tracing, exported via OTLP/HTTP to **Alloy**
- **spatie/laravel-prometheus** for the metrics endpoint (with a local patch, see [Architecture notes](#architecture-notes))
- **Prometheus**, **Loki**, **Tempo**, **Alloy** (collector), **Grafana**, all provisioned as code
- **Docker Compose** for the full local stack; no cloud accounts or paid tiers

## Getting started

### Prerequisites

- Docker and Docker Compose
- [`osv-scanner`](https://github.com/google/osv-scanner) installed locally (`go install github.com/google/osv-scanner/cmd/osv-scanner@latest`), required by the pre-commit hook
- `make`

### First run

```bash
git clone https://github.com/phoenix1331/meterly-laravel-observability.git
cd meterly-laravel-observability
cp .env.example .env
make demo
```

`make demo` brings up all ten containers, runs migrations, seeds a realistic tenant/plan mix plus fixed demo API keys, and starts the traffic simulator in the background (10 minute run, one scripted incident ~2 minutes in). Open Grafana at `http://localhost:3000` (anonymous admin access, no login) and watch the incident happen.

### Other commands

| Command | What it does |
|---|---|
| `make up` | Start the stack in the background |
| `make down` | Stop the stack |
| `make build` | Rebuild the app image |
| `make shell` | Open a shell in the app container |
| `make migrate` | Run database migrations |
| `make seed` | Seed the database |
| `make fresh` | Drop all tables, re-migrate, and re-seed |
| `make test` | Run the test suite |
| `make logs` | Tail the app container logs |
| `make horizon-logs` | Tail the Horizon queue worker logs |
| `make demo` | Full cold-start demo: up, migrate, seed, start the simulator |

All artisan/composer commands run inside the container. `npm install` is not required for the app itself; the Husky git hooks (`.husky/`) are Node-based and need `npm install` once before your first commit.

### Where to look

| URL | What |
|---|---|
| `http://localhost:8000` | The app |
| `http://localhost:8000/api/usage` | The metered endpoint (see `request.http`) |
| `http://localhost:8000/horizon` | Horizon dashboard |
| `http://localhost:8000/prometheus` | Raw metrics scrape endpoint |
| `http://localhost:3000` | Grafana - "Meterly" dashboard, anonymous access |
| `http://localhost:9090` | Prometheus |
| `http://localhost:3100` | Loki |
| `http://localhost:3200` | Tempo |

## Environment variables

| Key | Default | Description |
|---|---|---|
| `APP_URL` | `http://localhost:8000` | Base URL used by the traffic simulator and links |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306` | MySQL connection (host port 3307, container port 3306) |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | `meterly` / `meterly` / `meterly` | Local dev credentials only, not for production |
| `REDIS_HOST` | `redis` | Used for both cache and queues |
| `QUEUE_CONNECTION` | `redis` | Horizon requires this |
| `LOG_CHANNEL` | `stderr` | JSON logs to stderr, picked up by Alloy via the Docker socket |
| `FRAUD_CHECK_LATENCY_MS` | `50` | Simulated latency (ms) for the stubbed dependency |
| `FRAUD_CHECK_ERROR_RATE` | `0.0` | Simulated failure rate (0.0–1.0) for the stubbed dependency |
| `OTEL_EXPORTER_OTLP_ENDPOINT` | `http://alloy:4318` | Where traces are exported (OTLP/HTTP) |
| `OTEL_EXPORTER_OTLP_PROTOCOL` | `http/protobuf` | Must match what Alloy's receiver expects |

`FRAUD_CHECK_*` set the *default* latency/error rate; the traffic simulator overrides them at runtime via a cache-backed setting (`App\Support\FraudCheckSettings`) to script its incident without restarting the container.

## Endpoints / request.http

`request.http` at the project root covers three cases against `POST /api/usage`: a successful call (using a fixed, seeded demo key), an unauthenticated call (401), and a quota-exceeded call (429). The demo keys are intentionally committed plaintext, since they're fixtures seeded by `DemoSeeder`, not secrets, so the file is runnable immediately after `make fresh` with no setup.

Real API keys are never retrievable after creation: `ApiKey::generateToken()` returns the plaintext once, only a SHA-256 hash and a short prefix are stored.

## Architecture notes

**Why Octane needs `--log-level` set explicitly.** The `octane:frankenphp` dev command intercepts and reformats stderr looking for Caddy's own JSON log shape. The app's own structured JSON logs (Monolog's shape) don't match that pattern and get silently swallowed into a generic "unknown error" line otherwise. The Dockerfile passes `--log-level=info` to disable that interception so real app logs reach Loki.

**Why `TraceIdProcessor` resolves its dependency lazily.** Laravel's `LogManager` caches built Monolog channels for the life of an Octane worker, not per-request. A constructor-injected `TraceContext` would keep the first request's trace ID forever; the processor resolves it fresh from the container on every log line instead.

**Why the Prometheus histogram doesn't carry exemplars directly.** `promphp/prometheus_client_php` (used by `spatie/laravel-prometheus`) has no exemplar support in its histogram or text-exposition renderer. The correlation triangle uses Tempo's span-metrics (`traces_spanmetrics_latency_bucket`, derived from real spans and remote-written to Prometheus with exemplars) instead, an independent, fully working path to the same chart-to-trace click-through.

**`FixedLaravelCacheAdapter`.** `spatie/laravel-prometheus` 1.6.1's Redis/database-cache storage adapter has a bug: `collect()` fetches stored metrics but never assigns them back before rendering, so every scrape came back empty. `app/Prometheus/FixedLaravelCacheAdapter.php` is a small subclass that fixes this, rebound in `PrometheusServiceProvider`.

**Prometheus label naming.** `job` is reserved by Prometheus's scrape config; a custom metric label with that name gets silently renamed to `exported_job` at ingestion. The job-heartbeat gauge uses `job_name` to avoid the collision.

**Gauges don't disappear.** The underlying Prometheus client has no way to remove a gauge's stored value for a label set once it's been set, so a job that stops running just freezes at its last value rather than vanishing, which makes a pure `absent()` alert unreliable after the first successful run. `JobHeartbeatCollector` always emits a value (a large sentinel for "never run"), and the alert rule checks staleness rather than absence.

## Extending it

The brief's "after the thin slice" chapters, in the order they'd add the most:

1. **Sentry / GlitchTip error tracking**: add `sentry/sentry-laravel` pointed at a local GlitchTip instance (four containers, ~512MB, API/DSN-compatible with Sentry). Propagate the same trace ID already flowing through `TraceContext` so an exception in Sentry links straight to its Tempo trace and Loki logs; the wiring for this already exists, only the Sentry SDK integration is missing.
2. **Outbound webhooks with retries and a dead letter queue**: a new queued job pattern, giving a second, more realistic failure mode (retry-storm dashboards) to instrument the same way `AggregateUsageEvents` is instrumented now.
3. **Stripe usage sync**: a second scheduled job following the `JobHeartbeat` pattern already used for the aggregation job, giving a second absence-alerting example tied to a business-critical (revenue) failure mode.
4. **30-day backfilled history**: seed `UsageAggregate` rows across a longer date range to make week-over-week dashboard panels meaningful; the aggregation job already supports backfilling a specific date via `--date`.
5. **Mimir and object storage**: swap Prometheus's local storage for Mimir once local disk becomes the constraint; out of scope for a laptop-sized demo.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
