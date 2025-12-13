#!/usr/bin/env bash
set -e

APP_DIR="/var/www/html"
PATCH_DIR="/opt/laravel-patches"

echo "[php] init start"

if [ ! -f "$APP_DIR/artisan" ]; then
  echo "[php] creating laravel skeleton"
  composer create-project --no-interaction --prefer-dist laravel/laravel:^11 "$APP_DIR"
  cp "$APP_DIR/.env.example" "$APP_DIR/.env" || true
  sed -i 's|APP_NAME=Laravel|APP_NAME=ISSOSDR|g' "$APP_DIR/.env" || true
  php "$APP_DIR/artisan" key:generate || true
fi

if [ -d "$PATCH_DIR" ]; then
  echo "[php] applying patches"
  rsync -a "$PATCH_DIR/" "$APP_DIR/"
fi

# Обновляем .env с правильными настройками БД
if [ -f "$APP_DIR/.env" ]; then
  echo "[php] updating .env"
  sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=pgsql|g" "$APP_DIR/.env" || true
  sed -i "s|DB_HOST=.*|DB_HOST=db|g" "$APP_DIR/.env" || true
  sed -i "s|DB_PORT=.*|DB_PORT=5432|g" "$APP_DIR/.env" || true
  sed -i "s|DB_DATABASE=.*|DB_DATABASE=monolith|g" "$APP_DIR/.env" || true
  sed -i "s|DB_USERNAME=.*|DB_USERNAME=monouser|g" "$APP_DIR/.env" || true
  sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=monopass|g" "$APP_DIR/.env" || true
fi

chown -R www-data:www-data "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || true

# Запускаем миграции если нужно
if [ -f "$APP_DIR/artisan" ]; then
  echo "[php] running migrations"
  php "$APP_DIR/artisan" migrate --force || true
fi

echo "[php] starting php-fpm"
exec php-fpm -F
