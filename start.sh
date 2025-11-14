#!/usr/bin/env bash
set -e

# Laravel backend entrypoint for Nixpacks / Railway
# Starts the PHP API (admin, charity, donor flows) in the capstone_backend app.

# Default environment for container
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"

# Railpack/Railway usually injects PORT; fall back if missing
PORT="${PORT:-8080}"
export APP_URL="${APP_URL:-http://0.0.0.0:${PORT}}"

# Move into the Laravel backend
cd capstone_backend

# Run migrations and seed admin user (at runtime when DB is accessible)
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force || true

# Optimize Laravel for production (safe if already done)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run HTTP server
php artisan serve --host=0.0.0.0 --port="${PORT}"