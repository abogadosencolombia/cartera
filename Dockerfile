FROM php:8.3-cli-bullseye

# Libs para extensiones
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev libzip-dev \
 && rm -rf /var/lib/apt/lists/*

# Extensiones requeridas
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) gd bcmath pdo_pgsql opcache

# Redis opcional si lo usas
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Dependencias de PHP (usa el lock ya actualizado a 8.3)
RUN composer install --no-dev --optimize-autoloader

# Permisos Laravel
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

# Puerto y arranque simple
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
