#!/bin/sh
set -e

# --------------------------------------------------------------------------
# Este script solo ejecuta las optimizaciones de Laravel.
# Nixpacks se encarga de 'npm install', 'npm run build', y de iniciar Nginx.
# --------------------------------------------------------------------------

echo "Running Laravel optimizations..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link

echo "Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting PHP-FPM in foreground..."
# Esta es la última línea y debe ejecutar php-fpm en primer plano
# para mantener el contenedor activo.
php-fpm