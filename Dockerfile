# --- Stage 1: construir assets con Node ---
FROM node:20-alpine AS assets
WORKDIR /app

# deps JS
COPY package.json package-lock.json* ./
RUN npm ci

# código frontend y config de Vite/Tailwind
COPY resources ./resources
COPY vite.config.js ./
COPY tailwind.config.js postcss.config.js ./

# compilar -> genera public/build/manifest.json
RUN npm run build

# --- Stage 2: PHP + Laravel ---
FROM php:8.3-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=production

# paquetes del sistema
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev libzip-dev zlib1g-dev \
 && rm -rf /var/lib/apt/lists/*

# extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd bcmath zip pdo_pgsql opcache
RUN pecl install redis && docker-php-ext-enable redis

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# dependencias PHP (sin scripts para cachear capa)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts

# código Laravel
COPY . .

# assets compilados desde el stage de Node
COPY --from=assets /app/public/build ./public/build

# autoload y discovery
RUN composer dump-autoload -o && php artisan package:discover --ansi || true

# entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/entrypoint.sh"]
