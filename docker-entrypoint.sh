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

# Проверяем и устанавливаем таблицу migrations, если её нет
echo "🗄️  Checking migration status..."
if command -v sqlite3 >/dev/null 2>&1; then
    # Проверяем существование файла базы данных
    if [ ! -f database/database.sqlite ]; then
        echo "❌ Database file not found at database/database.sqlite"
    else
        echo "✅ Database file exists at database/database.sqlite"
        DB_FILE_SIZE=$(stat -f%z database/database.sqlite 2>/dev/null || stat -c%s database/database.sqlite 2>/dev/null || echo "0")
        echo "📊 Database file size: $DB_FILE_SIZE bytes"
    fi
    
    HAS_MIGRATIONS_TABLE=$(sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='migrations';" 2>/dev/null | grep -q "migrations" && echo "yes" || echo "no")
    HAS_ENTRIES_TABLE=$(sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='entries';" 2>/dev/null | grep -q "entries" && echo "yes" || echo "no")
    
    echo "📋 Migrations table exists: $HAS_MIGRATIONS_TABLE"
    echo "📋 Entries table exists: $HAS_ENTRIES_TABLE"
    
    if [ "$HAS_MIGRATIONS_TABLE" != "yes" ]; then
        echo "📋 Migration table not found. Installing migration table..."
        php artisan migrate:install --force || true
    else
        # Проверяем, какие миграции записаны в таблицу
        echo "📋 Registered migrations:"
        sqlite3 database/database.sqlite "SELECT migration FROM migrations ORDER BY id;" 2>/dev/null || echo "Could not read migrations"
        
        # Проверяем, сколько миграций найдено Laravel
        echo "📋 Available migration files:"
        ls -1 database/migrations/*.php 2>/dev/null | wc -l || echo "0"
        echo "   (checking if Laravel can find them)"
    fi
    
    # Если таблица migrations существует, но таблица entries отсутствует,
    # значит миграции не выполнились корректно - нужно пересоздать migrations
    if [ "$HAS_MIGRATIONS_TABLE" = "yes" ] && [ "$HAS_ENTRIES_TABLE" != "yes" ]; then
        echo "⚠️  Migration table exists but entries table is missing. Resetting migrations..."
        
        # Проверяем, сколько миграций записано
        MIGRATION_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM migrations;" 2>/dev/null || echo "0")
        echo "📊 Migrations recorded in database: $MIGRATION_COUNT"
        
        if [ "$MIGRATION_COUNT" -gt 0 ]; then
            echo "🔄 Clearing migrations table (found $MIGRATION_COUNT migrations but tables missing)..."
            sqlite3 database/database.sqlite "DELETE FROM migrations;" 2>/dev/null || true
            echo "✅ Migrations table cleared. Ready to run migrations."
        fi
    fi
fi

# Проверяем статус миграций перед запуском
echo "📊 Migration status before running:"
php artisan migrate:status || true

# Всегда выполняем миграции - Laravel сам определит, какие миграции уже выполнены
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Проверяем результат миграций
echo "📊 Migration status after running:"
php artisan migrate:status || true

# Проверяем, какие таблицы были созданы
if command -v sqlite3 >/dev/null 2>&1; then
    echo "📋 Tables in database:"
    sqlite3 database/database.sqlite ".tables" 2>/dev/null || echo "Could not list tables"
fi

# Проверяем, инициализирована ли база данных (есть ли данные Statamic)
# Проверяем наличие таблицы entries - это главный признак того, что миграции Statamic выполнены
if command -v sqlite3 >/dev/null 2>&1; then
    DB_HAS_ENTRIES=$(sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='entries';" 2>/dev/null | grep -q "entries" && echo "yes" || echo "no")
    
    # Если таблицы entries нет после миграций, значит миграции не выполнились корректно
    if [ "$DB_HAS_ENTRIES" != "yes" ]; then
        echo "⚠️  Warning: Table 'entries' was not found after migrations. This might indicate an issue with migrations."
        
        # Проверяем, что находится в таблице migrations
        echo "📋 Checking migrations table content:"
        sqlite3 database/database.sqlite "SELECT * FROM migrations;" 2>/dev/null || echo "Could not read migrations table"
        
        # Пробуем очистить кэш и запустить миграции снова
        echo "🔄 Clearing cache and re-running migrations..."
        php artisan config:clear || true
        php artisan cache:clear || true
        
        # Пробуем принудительно запустить все миграции
        echo "🔄 Re-running migrations with verbose output..."
        php artisan migrate --force -vvv || true
        
        DB_HAS_ENTRIES=$(sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='entries';" 2>/dev/null | grep -q "entries" && echo "yes" || echo "no")
        
        if [ "$DB_HAS_ENTRIES" != "yes" ]; then
            echo "❌ Error: Table 'entries' still not found after multiple migration attempts."
            echo "💡 Trying to manually check migration files..."
            
            # Проверяем наличие файла миграции
            if [ -f "database/migrations/2024_03_07_100000_create_entries_table_with_string_ids.php" ]; then
                echo "✅ Migration file exists: create_entries_table_with_string_ids.php"
            else
                echo "❌ Migration file NOT found: create_entries_table_with_string_ids.php"
            fi
            
            # Пробуем выполнить миграции из конкретного пути
            echo "🔄 Attempting to run migrations from specific path..."
            php artisan migrate --path=database/migrations --force || true
        fi
    fi
else
    # Если sqlite3 недоступен, предполагаем, что миграции выполнились
    DB_HAS_ENTRIES="yes"
fi

# Проверяем, есть ли данные в таблице entries (для определения, нужно ли импортировать)
DB_HAS_DATA="no"
if [ "$DB_HAS_ENTRIES" = "yes" ] && command -v sqlite3 >/dev/null 2>&1; then
    ENTRY_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM entries;" 2>/dev/null || echo "0")
    if [ "$ENTRY_COUNT" -gt 0 ]; then
        DB_HAS_DATA="yes"
    fi
fi

# Если данных нет, импортируем из файлов
if [ "$DB_HAS_DATA" != "yes" ]; then
    echo "🔄 Database is empty - importing Statamic content from files..."
    
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
    echo "✅ Database already contains data - skipping import (preserving existing data)"
    
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

