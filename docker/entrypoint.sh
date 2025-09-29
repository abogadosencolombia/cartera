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

# Limpieza y caches
php artisan config:clear || true
php artisan cache:clear  || true
php artisan route:clear  || true
php artisan view:clear   || true

php artisan key:generate --force || true
php artisan migrate --force      || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Servir la app
exec php -S 0.0.0.0:8000 -t public
