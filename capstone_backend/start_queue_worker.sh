#!/bin/bash

# Queue Worker Startup Script for Railway
# This ensures emails are processed

echo "Starting Laravel Queue Worker..."

# Clear any stuck jobs first
php artisan queue:clear

# Start the queue worker with proper settings
php artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600

echo "Queue worker stopped"
