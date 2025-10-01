#!/usr/bin/env bash
set -e

# Permisos de runtime
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ---- Diagnóstico a STDERR (aparece en Deploy Logs) ----
php -r 'foreach(["APP_ENV","APP_URL","DB_HOST","DB_DATABASE","DB_USERNAME"] as $k){fwrite(STDERR,$k."=".getenv($k).PHP_EOL);}';
php -m | grep -E 'pdo_pgsql|gd|zip|bcmath' || true
php artisan about || true
# -------------------------------------------------------

# Instalar dependencias y compilar assets
echo "Installing NPM dependencies..."
npm install
echo "Building assets for production..."
npm run build

# Comandos de Laravel
echo "Running Laravel optimizations..."
php artisan about
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Servir la app
exec php -S 0.0.0.0:8000 -t public
