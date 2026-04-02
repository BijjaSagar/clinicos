#!/bin/bash
# ============================================
# ClinicOS Deployment Script
# ============================================
# Usage: bash scripts/deploy.sh
# Run this on the production server after git pull

set -e

echo "╔══════════════════════════════════════╗"
echo "║    ClinicOS Deployment Starting      ║"
echo "╚══════════════════════════════════════╝"

cd /var/www/clinicos/backend || { echo "ERROR: Backend directory not found"; exit 1; }

echo ""
echo "→ Step 1: Pulling latest code..."
git pull origin main

echo ""
echo "→ Step 2: Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo ""
echo "→ Step 3: Running database migrations..."
php artisan migrate --force

echo ""
echo "→ Step 4: Clearing and rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ""
echo "→ Step 5: Optimizing..."
php artisan optimize

echo ""
echo "→ Step 6: Restarting queue workers (if any)..."
php artisan queue:restart 2>/dev/null || true

echo ""
echo "→ Step 7: Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo ""
echo "╔══════════════════════════════════════╗"
echo "║    Deployment Complete! ✓            ║"
echo "╚══════════════════════════════════════╝"
echo ""
echo "Post-deployment checklist:"
echo "  • Verify: curl -s https://clinic0s.com | head -1"
echo "  • Check logs: tail -f storage/logs/laravel.log"
echo "  • Run backup: php artisan backup:database"
echo ""
