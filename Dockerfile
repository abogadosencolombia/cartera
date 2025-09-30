# --- Stage 1: construir assets con Node ---
FROM node:20-alpine AS assets
WORKDIR /app

# instalar dependencias JS
COPY package.json package-lock.json* ./
RUN npm ci

# copiar código fuente de frontend
COPY resources ./resources
COPY vite.config.js ./
COPY tailwind.config.js postcss.config.js ./ 2>/dev/null || true

# compilar assets
RUN npm run build

# --- Stage 2: PHP + Laravel ---
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

# instalar dependencias PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

# copiar el resto del proyecto Laravel
COPY . .

# copiar los assets compilados del stage anterior
COPY --from=assets /app/public/build ./public/build

# verificar que manifest.json exista (útil en logs)
RUN ls -la public/build

RUN composer dump-autoload -o && php artisan package:discover --ansi || true

# entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
