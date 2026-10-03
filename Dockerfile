# syntax=docker/dockerfile:1

# ---- dependencies -----------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress \
        --ignore-platform-req=ext-pdo_pgsql
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
        --ignore-platform-req=ext-pdo_pgsql

# ---- runtime ----------------------------------------------------------------
FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql opcache \
    && a2enmod rewrite headers \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/hardening.conf \
    && a2enconf hardening \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=prod
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html

RUN mkdir -p var/cache var/log \
    && php bin/console cache:warmup --env=prod \
    && chown -R www-data:www-data var \
    && chmod +x docker/entrypoint.sh

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s \
    CMD curl -fsS http://localhost/api/health || exit 1

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
