# Usa una imagen oficial de PHP con Apache
FROM php:8.3-apache

# 1. Instala dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libpq-dev \
    libzip-dev \
    zlib1g-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath

# 2. Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Configura Apache para Laravel
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 4. Establece el directorio de trabajo
WORKDIR /var/www/html

# 5. Copia tu código y establece permisos
COPY . .
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 6. Instala dependencias de Composer y NPM
RUN composer install --optimize-autoloader --no-dev
RUN npm install && npm run build

# Expone el puerto 80
EXPOSE 80

# El comando de inicio ahora se gestionará desde Railway
# No se necesita CMD aquí