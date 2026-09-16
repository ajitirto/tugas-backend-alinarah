# =========================
# 1. Composer dependencies
# =========================
FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist


# =========================
# 2. PHP runtime
# =========================
FROM php:8.4-cli-alpine

WORKDIR /var/www/html

# Runtime + build dependencies
RUN apk add --no-cache \
        libpq \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        postgresql-dev \
        libzip-dev \
        linux-headers \
        autoconf \
        make \
        gcc \
        g++ \
    \
    # PostgreSQL
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
    \
    # Redis
    && pecl install redis \
    && docker-php-ext-enable redis \
    \
    # Cleanup
    && apk del .build-deps \
    && rm -rf /tmp/pear


# =========================
# 3. Vendor
# =========================
COPY --from=vendor /app/vendor ./vendor


# =========================
# 4. Application
# =========================
COPY . .


# =========================
# 5. Permissions
# =========================
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache


EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
