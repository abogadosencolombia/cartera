FROM php:8.3-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=production

RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev libzip-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd bcmath zip pdo_pgsql opcache
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# vendor sin scripts (cache de capas)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

# código app
COPY . .

# autoload y discover sin romper build
RUN composer dump-autoload -o && php artisan package:discover --ansi || true

# entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
