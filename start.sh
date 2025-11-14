#!/usr/bin/env bash
set -e

# Laravel backend entrypoint for Railpack / Nixpacks
# This script starts the PHP API that contains all 3 role-based flows
# (admin, charity, donor) inside the capstone_backend Laravel app.

# Default environment for container
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"

# Railpack/Railway usually injects PORT; fall back if missing
PORT="${PORT:-8080}"
export APP_URL="${APP_URL:-http://0.0.0.0:${PORT}}"

# Move into the Laravel backend
cd capstone_backend

# Install PHP dependencies if vendor is missing
if [ ! -d "vendor" ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Optimize Laravel for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run HTTP server
php artisan serve --host=0.0.0.0 --port="${PORT}"