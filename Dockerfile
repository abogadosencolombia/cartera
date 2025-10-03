# Etapa 1: Instalar dependencias de Composer
FROM php:8.3-cli AS composer_builder

# Instalar dependencias del sistema necesarias para GD
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copiar archivos de composer
COPY composer.json composer.lock ./

# Instalar dependencias de Composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copiar el resto de la aplicación
COPY . .

# Generar autoloader optimizado
RUN composer dump-autoload --optimize

# Etapa 2: Build de assets (Node.js)
FROM node:20 AS node_builder

WORKDIR /app

# Copiar archivos de dependencias
COPY package*.json ./

# Limpiar y reinstalar dependencias (soluciona problema de rollup)
RUN rm -rf node_modules package-lock.json && \
    npm cache clean --force && \
    npm install

# Copiar vendor desde composer (necesario para Ziggy)
COPY --from=composer_builder /app/vendor ./vendor

# Copiar el resto de archivos necesarios para build
COPY . .

# Compilar assets (Vite con Vue y Tailwind)
RUN npm run build

# Etapa 3: Imagen final PHP con Apache
FROM php:8.3-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP requeridas por Laravel
RUN docker-php-ext-install pdo_mysql mysqli zip exif pcntl bcmath gd pdo_pgsql pgsql

# Habilitar módulos de Apache
RUN a2enmod rewrite headers

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar DocumentRoot de Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY --chown=www-data:www-data . .

# Copiar vendor desde composer builder
COPY --from=composer_builder --chown=www-data:www-data /app/vendor ./vendor

# Copiar assets compilados desde node builder
COPY --from=node_builder --chown=www-data:www-data /app/public/build ./public/build

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chmod -R 755 /var/www/html/bootstrap/cache

# Crear archivo .htaccess si no existe
RUN if [ ! -f /var/www/html/public/.htaccess ]; then \
    echo '<IfModule mod_rewrite.c>\n\
    <IfModule mod_negotiation.c>\n\
        Options -MultiViews -Indexes\n\
    </IfModule>\n\
\n\
    RewriteEngine On\n\
\n\
    # Handle Authorization Header\n\
    RewriteCond %{HTTP:Authorization} .\n\
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\
\n\
    # Redirect Trailing Slashes If Not A Folder...\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteCond %{REQUEST_URI} (.+)/$\n\
    RewriteRule ^ %1 [L,R=301]\n\
\n\
    # Send Requests To Front Controller...\n\
    RewriteCond %{REQUEST_FILENAME} !-d\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^ index.php [L]\n\
</IfModule>' > /var/www/html/public/.htaccess; \
fi

# Exponer puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]