#!/bin/bash
set -e

echo "🚀 Starting Laravel/Statamic application initialization..."

# Проект находится прямо в /app (Base Directory = /)
APP_DIR="/app"

# Переходим в рабочую директорию
cd "$APP_DIR"
echo "📂 Working directory: $APP_DIR"

# Очищаем кэш перед началом инициализации
echo "🧹 Clearing cache..."
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan optimize:clear || true

# Проверяем существование базы данных
if [ ! -f database/database.sqlite ]; then
    echo "📝 Creating database.sqlite file..."
    mkdir -p database
    touch database/database.sqlite
fi

# Проверяем, инициализирована ли база данных
# Проверяем размер файла базы данных и наличие таблицы migrations
DB_FILE_SIZE=$(stat -f%z database/database.sqlite 2>/dev/null || stat -c%s database/database.sqlite 2>/dev/null || echo "0")

# Проверяем наличие таблицы migrations через SQLite CLI (если доступен) или используем размер файла
if command -v sqlite3 >/dev/null 2>&1; then
    DB_HAS_MIGRATIONS=$(sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations';" 2>/dev/null | grep -q "migrations" && echo "yes" || echo "no")
else
    # Если sqlite3 недоступен, используем только размер файла (если < 10KB, считаем базу пустой)
    DB_HAS_MIGRATIONS="no"
fi

# Если файл базы данных пустой или таблица migrations отсутствует, значит база не инициализирована
if [ "$DB_FILE_SIZE" -lt 10000 ] || [ "$DB_HAS_MIGRATIONS" != "yes" ]; then
    # База данных пустая или не инициализирована
    echo "🗄️  Database is empty or not initialized - running migrations..."
    php artisan migrate --force
    
    # Импортируем данные из файлов только при первом запуске
    echo "🔄 First run detected - importing Statamic content from files..."
    
    # Импортируем сайты из файлов в базу данных (важно сделать первым)
    echo "🌍 Importing Statamic sites..."
    php artisan statamic:eloquent:import-sites

    # Импортируем asset containers из файлов в базу данных (ДО entries, так как entries могут ссылаться на assets!)
    echo "📁 Importing Statamic asset containers..."
    php artisan statamic:eloquent:import-assets --force --only-asset-containers || true

    # Импортируем assets из файлов в базу данных (ДО entries!)
    echo "🖼️  Importing Statamic assets..."
    php artisan statamic:eloquent:import-assets --force --only-assets || true

    # Импортируем blueprints из файлов в базу данных (критично - ДО entries!)
    echo "📋 Importing Statamic blueprints..."
    php artisan statamic:eloquent:import-blueprints --force --only-blueprints || true

    # Импортируем fieldsets из файлов в базу данных (критично - ДО entries!)
    echo "📄 Importing Statamic fieldsets..."
    php artisan statamic:eloquent:import-blueprints --force --only-fieldsets || true

    # Импортируем коллекции из файлов в базу данных (ДО entries!)
    echo "📦 Importing Statamic collections..."
    php artisan statamic:eloquent:import-collections --force

    # Импортируем деревья коллекций из файлов в базу данных (ДО entries!)
    echo "🌲 Importing Statamic collection trees..."
    php artisan statamic:eloquent:import-collections --force --only-collection-trees || true

    # Импортируем таксономии из файлов в базу данных (ДО entries!)
    echo "🏷️  Importing Statamic taxonomies..."
    php artisan statamic:eloquent:import-taxonomies --force --only-taxonomies || true

    # Импортируем термины таксономий из файлов в базу данных
    echo "📌 Importing Statamic taxonomy terms..."
    php artisan statamic:eloquent:import-taxonomies --force --only-terms || true

    # Импортируем записи из файлов в базу данных (ПОСЛЕ blueprints и collections!)
    echo "📝 Importing Statamic entries..."
    php artisan statamic:eloquent:import-entries

    # Импортируем навигации из файлов в базу данных
    echo "🧭 Importing Statamic navigations..."
    php artisan statamic:eloquent:import-navs --force --only-navs || true

    # Импортируем деревья навигаций из файлов в базу данных
    echo "🌳 Importing Statamic navigation trees..."
    php artisan statamic:eloquent:import-navs --force --only-nav-trees || true

    # Импортируем глобальные наборы из файлов в базу данных
    echo "🌐 Importing Statamic global sets..."
    php artisan statamic:eloquent:import-globals --force --only-global-sets || true

    # Импортируем глобальные переменные из файлов в базу данных
    echo "🔧 Importing Statamic global variables..."
    php artisan statamic:eloquent:import-globals --force --only-global-variables || true

    # Импортируем формы из файлов в базу данных
    echo "📝 Importing Statamic forms..."
    php artisan statamic:eloquent:import-forms --force --only-forms || true

    # Импортируем submissions форм (опционально, обычно не нужно при первом деплое)
    echo "📋 Importing Statamic form submissions (if any)..."
    php artisan statamic:eloquent:import-forms --force --only-form-submissions || true

    # Импортируем revisions (опционально, только если включены)
    echo "📚 Importing Statamic revisions (if enabled)..."
    php artisan statamic:eloquent:import-revisions || true

    # Очищаем кэш после импорта
    echo "🧹 Clearing cache after import..."
    php artisan cache:clear || true
    php artisan statamic:stache:clear || true

    # Обновляем Statamic Stache (перестраиваем кэш после импорта всех данных)
    echo "🔄 Refreshing Statamic Stache..."
    php artisan statamic:stache:refresh || true
else
    echo "✅ Database is initialized - skipping import (preserving existing data)"
    
    # Только запускаем миграции для обновления структуры (без потери данных)
    echo "🗄️  Running database migrations (if needed)..."
    php artisan migrate --force
    
    # Очищаем кэш для обновления конфигурации
    echo "🧹 Refreshing cache..."
    php artisan cache:clear || true
    php artisan statamic:stache:refresh || true
fi

# Кэшируем конфигурацию для продакшена
echo "⚙️  Optimizing application..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Создаем символическую ссылку для storage (критично для изображений!)
echo "🔗 Creating storage symlink..."
php artisan storage:link || true

# Настраиваем права
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache || true

echo "✅ Initialization completed!"

# Запускаем сервер
echo "🌐 Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8000

