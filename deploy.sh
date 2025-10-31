#!/bin/bash

# Deployment script for Laravel/Statamic application
# This script should be run on the production server

set -e

echo "🚀 Starting deployment..."

# Navigate to project directory
cd "$(dirname "$0")"

# Backup current version
echo "📦 Creating backup..."
if [ -d "../backup" ]; then
  rm -rf ../backup
fi
mkdir -p ../backup
cp -r . ../backup/ 2>/dev/null || true

# Pull latest changes from Git
echo "📥 Pulling latest changes..."
git pull origin main || git pull origin master

# Install/Update Composer dependencies
echo "📚 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install/Update NPM dependencies
echo "📦 Installing NPM dependencies..."
if [ -f package.json ]; then
  npm ci --production=false
fi

# Build assets
echo "🔨 Building assets..."
if [ -f package.json ]; then
  npm run build
fi

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "⚙️  Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Refresh Statamic Stache
echo "🔄 Refreshing Statamic Stache..."
php artisan statamic:stache:refresh

# Set proper permissions
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache
if [ -n "$USER" ]; then
  chown -R $USER:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Clear application cache
echo "🧹 Clearing cache..."
php artisan cache:clear
php artisan optimize:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan optimize

echo "✅ Deployment completed successfully!"

