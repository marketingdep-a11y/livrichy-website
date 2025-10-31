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

# Принудительно публикуем миграции Statamic eloquent driver (на случай если они не были опубликованы)
echo "📦 Publishing Statamic eloquent driver migrations..."
php artisan vendor:publish --tag=statamic-eloquent-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-entries-table-with-string-ids --force || true
php artisan vendor:publish --tag=statamic-eloquent-site-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-taxonomy-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-term-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-collection-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-collection-tree-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-blueprint-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-fieldset-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-form-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-form-submission-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-global-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-global-variables-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-navigation-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-navigation-tree-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-asset-container-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-asset-migrations --force || true
php artisan vendor:publish --tag=statamic-eloquent-token-migrations --force || true

# Проверяем, что миграции действительно существуют
echo "📋 Checking if migration files exist..."
if [ -d "database/migrations" ]; then
    MIGRATION_FILE_COUNT=$(find database/migrations -name "*.php" -type f 2>/dev/null | wc -l | tr -d ' ')
    echo "✅ Found $MIGRATION_FILE_COUNT migration files in database/migrations/"
    if [ "$MIGRATION_FILE_COUNT" -eq 0 ]; then
        echo "⚠️  Warning: No migration files found! This is a problem."
        echo "📋 Listing database/migrations directory:"
        ls -la database/migrations/ || echo "Directory does not exist!"
    else
        echo "📋 Sample migration files:"
        ls -1 database/migrations/*.php | head -5 || true
    fi
else
    echo "❌ Error: database/migrations directory does not exist!"
    echo "📋 Creating database/migrations directory..."
    mkdir -p database/migrations
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

# Синхронизируем таблицу migrations с существующими таблицами
# Если таблицы существуют, но миграции не зарегистрированы, записываем их
if command -v sqlite3 >/dev/null 2>&1; then
    MIGRATIONS_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM migrations;" 2>/dev/null || echo "0")
    
    # Маппинг таблиц к миграциям
    if [ "$MIGRATIONS_COUNT" -eq 0 ]; then
        echo "🔄 Syncing migrations table with existing tables..."
        
        # Проверяем существующие таблицы и записываем соответствующие миграции
        declare -A TABLE_MIGRATIONS=(
            ["asset_containers"]="2024_03_07_100000_create_asset_containers_table"
            ["assets_meta"]="2024_03_07_100000_create_asset_table"
            ["blueprints"]="2024_03_07_100000_create_blueprints_table"
            ["collections"]="2024_03_07_100000_create_collections_table"
            ["entries"]="2024_03_07_100000_create_entries_table_with_string_ids"
            ["fieldsets"]="2024_03_07_100000_create_fieldsets_table"
            ["form_submissions"]="2024_03_07_100000_create_form_submissions_table"
            ["forms"]="2024_03_07_100000_create_forms_table"
            ["global_set_variables"]="2024_03_07_100000_create_global_variables_table"
            ["global_sets"]="2024_03_07_100000_create_globals_table"
            ["trees"]="2024_03_07_100000_create_navigation_trees_table"
            ["navigations"]="2024_03_07_100000_create_navigations_table"
            ["taxonomies"]="2024_03_07_100000_create_taxonomies_table"
            ["taxonomy_terms"]="2024_03_07_100000_create_terms_table"
            ["tokens"]="2024_03_07_100000_create_tokens_table"
            ["sites"]="2024_07_16_100000_create_sites_table"
        )
        
        BATCH=1
        for TABLE in "${!TABLE_MIGRATIONS[@]}"; do
            MIGRATION="${TABLE_MIGRATIONS[$TABLE]}"
            if sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='$TABLE';" 2>/dev/null | grep -q "$TABLE"; then
                echo "  ✅ $TABLE exists - marking $MIGRATION as run"
                sqlite3 database/database.sqlite "INSERT OR IGNORE INTO migrations (migration, batch) VALUES ('$MIGRATION', $BATCH);" 2>/dev/null || true
            fi
        done
        
        # Также записываем миграцию модификации form_submissions если таблица существует
        if sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='form_submissions';" 2>/dev/null | grep -q "form_submissions"; then
            sqlite3 database/database.sqlite "INSERT OR IGNORE INTO migrations (migration, batch) VALUES ('2024_05_15_100000_modify_form_submissions_id', $BATCH);" 2>/dev/null || true
        fi
        
        echo "✅ Migrations table synced with existing tables"
    fi
fi

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
    
    # Проверяем наличие критически важных таблиц
    echo "🔍 Checking critical tables:"
    CRITICAL_TABLES=("asset_containers" "fieldsets" "trees" "terms" "global_variables")
    MISSING_TABLES=0
    for TABLE in "${CRITICAL_TABLES[@]}"; do
        if sqlite3 database/database.sqlite "SELECT name FROM sqlite_master WHERE type='table' AND name='$TABLE';" 2>/dev/null | grep -q "$TABLE"; then
            echo "  ✅ $TABLE exists"
        else
            echo "  ❌ $TABLE MISSING"
            MISSING_TABLES=$((MISSING_TABLES + 1))
        fi
    done
    
    # Проверяем, сколько таблиц существует и сколько миграций записано
    EXISTING_TABLES_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT IN ('sqlite_sequence', 'migrations');" 2>/dev/null || echo "0")
    MIGRATIONS_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM migrations;" 2>/dev/null || echo "0")
    
    echo "📊 Existing tables: $EXISTING_TABLES_COUNT"
    echo "📊 Recorded migrations: $MIGRATIONS_COUNT"
    
    # Если критически важные таблицы отсутствуют, но миграции отмечены как выполненные,
    # нужно очистить таблицу migrations и запустить миграции заново
    if [ "$MISSING_TABLES" -gt 0 ]; then
        echo "⚠️  Found $MISSING_TABLES missing critical tables."
        
        # Проверяем, существуют ли хотя бы некоторые таблицы
        if [ "$EXISTING_TABLES_COUNT" -gt 0 ]; then
            echo "⚠️  Some tables exist, but critical ones are missing."
            echo "⚠️  This might cause migration conflicts. Proceeding carefully..."
            # Пробуем запустить миграции с --force, Laravel попытается создать только недостающие
            echo "🗄️  Running migrations (will skip existing tables)..."
            php artisan migrate --force || echo "⚠️  Some migrations failed (this is expected if tables exist)"
        else
            echo "🔄 No tables exist - clearing migrations table and running all migrations..."
            sqlite3 database/database.sqlite "DELETE FROM migrations;" 2>/dev/null || true
            echo "🗄️  Running all migrations..."
            php artisan migrate --force
        fi
        
        echo "📊 Migration status after re-run:"
        php artisan migrate:status || true
    elif [ "$EXISTING_TABLES_COUNT" -gt 0 ] && [ "$MIGRATIONS_COUNT" -eq 0 ]; then
        # Таблицы существуют, но миграции не зарегистрированы - это проблема
        # Нужно записать миграции как выполненные или использовать migrate:status для синхронизации
        echo "⚠️  Tables exist but no migrations are recorded."
        echo "⚠️  This might cause issues. The migrations table is out of sync."
        echo "💡 Note: Laravel will try to run migrations and may fail if tables exist."
        echo "🔄 Attempting to run migrations - they should be skipped for existing tables..."
        # Используем --pretend для проверки, но на самом деле просто запускаем
        # Laravel должен пропустить миграции для существующих таблиц
        php artisan migrate --force 2>&1 | grep -v "already exists" || true
    fi
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
# Удаляем существующую ссылку если она есть (может быть неверной)
if [ -L "public/storage" ]; then
    echo "  Removing existing storage symlink..."
    rm -f public/storage || true
fi
# Создаем новую ссылку
php artisan storage:link || true

# Проверяем что символическая ссылка создана правильно
if [ -L "public/storage" ]; then
    echo "  ✅ Storage symlink exists"
    ls -la public/storage | head -1
else
    echo "  ⚠️  Storage symlink not found - trying to create manually..."
    ln -sf ../storage/app/public public/storage || true
fi

# Проверяем наличие директории assets
echo "📁 Checking assets directory..."
if [ ! -d "public/assets" ]; then
    echo "  Creating public/assets directory..."
    mkdir -p public/assets
fi

# Проверяем наличие директории storage/app/public
if [ ! -d "storage/app/public" ]; then
    echo "  Creating storage/app/public directory..."
    mkdir -p storage/app/public
fi

# Настраиваем права (критично для изображений!)
echo "🔒 Setting permissions..."
chmod -R 775 storage bootstrap/cache || true
chmod -R 775 public/assets || true

# Проверяем права доступа
echo "📋 Checking permissions..."
ls -ld public/storage || echo "  ⚠️  public/storage not accessible"
ls -ld storage/app/public || echo "  ⚠️  storage/app/public not accessible"
ls -ld public/assets || echo "  ⚠️  public/assets not accessible"

# Проверяем наличие файлов в assets
echo "📁 Checking assets directory..."
ASSET_COUNT=$(find public/assets -type f 2>/dev/null | wc -l | tr -d ' ')
echo "  ✅ Found $ASSET_COUNT files in public/assets"

# Проверяем права доступа к файлам в assets
echo "🔒 Checking asset file permissions..."
if [ -d "public/assets" ]; then
    # Устанавливаем права на чтение для всех файлов в assets
    chmod -R 644 public/assets/* 2>/dev/null || true
    find public/assets -type d -exec chmod 755 {} \; 2>/dev/null || true
    find public/assets -type f -exec chmod 644 {} \; 2>/dev/null || true
    echo "  ✅ Asset file permissions set"
fi

# Синхронизируем assets с базой данных (если файлы существуют, но записи в БД нет)
echo "🔄 Syncing assets with database..."
php artisan statamic:eloquent:sync-assets || echo "  ⚠️  Asset sync failed (may not be critical)"

# Проверяем синхронизацию assets (диагностика)
if command -v sqlite3 >/dev/null 2>&1; then
    ASSET_COUNT=$(sqlite3 database/database.sqlite "SELECT COUNT(*) FROM assets_meta;" 2>/dev/null || echo "0")
    echo "  📊 Assets in database: $ASSET_COUNT"
    
    # Проверяем наличие конкретного файла из URL (beach-pros-realty-inc..jpg)
    if [ -f "public/assets/properties/beach-pros-realty-inc..jpg" ]; then
        echo "  ✅ Test file exists: public/assets/properties/beach-pros-realty-inc..jpg"
    else
        echo "  ⚠️  Test file NOT found: public/assets/properties/beach-pros-realty-inc..jpg"
        echo "  📁 Listing public/assets/properties/:"
        ls -la public/assets/properties/ 2>/dev/null | head -5 || echo "    Directory not found"
    fi
fi

# Создаем и настраиваем директорию кэша Glide (критично для обработки изображений!)
echo "🖼️  Setting up Glide image cache..."
# Glide использует storage/statamic/glide для кэша
mkdir -p storage/statamic/glide
mkdir -p storage/statamic/glide/tmp
mkdir -p storage/framework/cache/glide

# Устанавливаем права на директории Glide
chmod -R 775 storage/statamic 2>/dev/null || true
chmod -R 775 storage/framework/cache 2>/dev/null || true

# Проверяем, что директории созданы
if [ -d "storage/statamic/glide" ]; then
    echo "  ✅ Glide cache directory exists"
else
    echo "  ❌ Failed to create Glide cache directory"
fi

# Очищаем кэш изображений Glide перед запуском (на случай проблемных файлов)
echo "🧹 Clearing Glide image cache..."
php artisan statamic:glide:clear || echo "  ⚠️  Glide clear failed (may not be critical)"
if [ -d "storage/statamic/glide" ]; then
    rm -rf storage/statamic/glide/* 2>/dev/null || true
fi
echo "  ✅ Glide cache cleared"

# Проверяем расширение GD (критично для обработки изображений)
echo "🔍 Checking GD extension..."
php -r "if (extension_loaded('gd')) { 
    echo '  ✅ GD extension is loaded\n'; 
    \$info = gd_info(); 
    echo '  - GD Version: ' . \$info['GD Version'] . '\n'; 
    echo '  - JPEG Support: ' . (isset(\$info['JPEG Support']) && \$info['JPEG Support'] ? 'Yes' : 'No') . '\n'; 
    echo '  - PNG Support: ' . (isset(\$info['PNG Support']) && \$info['PNG Support'] ? 'Yes' : 'No') . '\n'; 
    echo '  - WebP Support: ' . (function_exists('imagewebp') ? 'Yes' : 'No') . '\n';
    echo '  - FreeType Support: ' . (isset(\$info['FreeType Support']) && \$info['FreeType Support'] ? 'Yes' : 'No') . '\n';
} else { 
    echo '  ❌ GD extension NOT loaded!\n'; 
    exit(1); 
}" || echo "  ⚠️  GD check failed"

# Создаем директорию для логов (если не существует)
echo "📝 Setting up logging..."
mkdir -p storage/logs
chmod -R 775 storage/logs 2>/dev/null || true

# Проверяем доступность логов
if [ -d "storage/logs" ] && [ -w "storage/logs" ]; then
    echo "  ✅ Logs directory is writable"
else
    echo "  ⚠️  Logs directory may not be writable"
fi

# Проверяем, что APP_DEBUG установлен правильно для диагностики
echo "🔧 Checking error reporting..."
php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; echo '  - APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false') . PHP_EOL; echo '  - Error Reporting: ' . (ini_get('display_errors') ? 'On' : 'Off') . PHP_EOL;" 2>&1 | head -2 || echo "  ⚠️  Could not check error reporting"

echo "✅ Initialization completed!"

# Запускаем сервер
echo "🌐 Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8000

