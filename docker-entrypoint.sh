#!/bin/bash
set -e

echo "🚀 Starting Laravel/Statamic application initialization..."

# Переходим в рабочую директорию
cd /app

# Проверяем существование базы данных
if [ ! -f database/database.sqlite ]; then
    echo "📝 Creating database.sqlite file..."
    mkdir -p database
    touch database/database.sqlite
fi

# Запускаем миграции
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Импортируем коллекции из файлов в базу данных
echo "📦 Importing Statamic collections..."
php artisan statamic:eloquent:import-collections --force

# Импортируем записи из файлов в базу данных
echo "📝 Importing Statamic entries..."
php artisan statamic:eloquent:import-entries --force

# Кэшируем конфигурацию
echo "⚙️  Optimizing application..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Настраиваем права
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache || true

echo "✅ Initialization completed!"

# Запускаем сервер
echo "🌐 Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8000

