FROM php:8.3-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production

# Paquetes del sistema
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev libzip-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/*

# Extensiones
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) gd bcmath zip pdo_pgsql opcache
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 1) Instalar vendor sin scripts para aprovechar cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

# 2) Copiar el resto del código (incluye artisan)
COPY . .

# 3) Ahora sí ejecutar scripts/artisan
RUN composer dump-autoload -o \
 && php artisan package:discover --ansi || true

# Permisos Laravel
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
