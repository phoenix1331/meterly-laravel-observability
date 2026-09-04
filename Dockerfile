FROM dunglas/frankenphp:php8.4

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pdo_mysql \
    redis \
    zip \
    opcache \
    intl \
    pcntl

COPY . /app

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
