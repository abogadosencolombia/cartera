FROM php:8.3-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    nodejs \
    npm

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar composer files
COPY composer.json composer.lock ./

# Instalar dependencias PHP
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copiar código fuente
COPY . .

# Instalar dependencias npm y compilar assets
RUN npm install
RUN npm run build

# Generar autoloader optimizado
RUN composer dump-autoload --optimize

# Configurar permisos
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar configuración de Apache
COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Puerto
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]