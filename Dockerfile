FROM dunglas/frankenphp:php8.4

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# pdo_mysql/redis: the app's own DB and cache/queue connections.
# pcntl: Octane needs it for worker signal handling (SIGINT/SIGTERM),
# and SimulateTraffic's own trap() call needs it too.
RUN install-php-extensions \
    pdo_mysql \
    redis \
    zip \
    opcache \
    intl \
    pcntl

COPY . /app

# --log-level is required, not cosmetic: without it, octane:frankenphp
# intercepts and reformats all stderr output looking for Caddy's own
# JSON log shape, and silently swallows the app's structured JSON logs
# (config/logging.php's stderr channel) instead of passing them through
# to Docker's log stream where Alloy can tail them.
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--log-level=info"]
