# Usa una imagen oficial de PHP con Apache
FROM php:8.3-apache

# 1. Instala dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    libpq-dev libzip-dev zlib1g-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath

# 1.5. Instala Node.js y npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 2. Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Configura Apache para Laravel
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 4. Establece el directorio de trabajo
WORKDIR /var/www/html

# ---- OPTIMIZACIÓN DE DEPENDENCIAS ----
# 5. Copia solo los archivos de dependencias primero
COPY composer.json composer.lock ./
# Instala dependencias de PHP
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Copia los archivos de dependencias de Node
COPY package.json package-lock.json ./
# Usa 'npm ci' que es más rápido y confiable para entornos de producción
RUN npm ci
# ------------------------------------

# 6. Copia el resto del código de la aplicación
COPY . .

# 7. Compila los assets de Vite
RUN npm run build

# 8. Establece permisos finales
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expone el puerto 80
EXPOSE 80