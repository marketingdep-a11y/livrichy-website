# 🚀 Деплой на Coolify

Coolify - это мощная open-source платформа для деплоя приложений. Отлично подходит для Laravel/Statamic проектов!

## ✅ Преимущества Coolify:

- ✅ Автоматический деплой при каждом push в GitHub
- ✅ Простая интеграция с GitHub App
- ✅ Автоматическая установка зависимостей
- ✅ Автоматическая сборка assets
- ✅ Встроенный мониторинг и логи
- ✅ Простое управление переменными окружения

## 📋 Настройка деплоя на Coolify

### Шаг 1: Подключение GitHub App в Coolify

1. **Войдите в панель Coolify**
2. Создайте новый проект или откройте существующий
3. Нажмите **"New Resource"** → **"Application"**
4. Выберите **"GitHub"** как источник
5. Нажмите **"+ Add GitHub App"** или **"Connect GitHub"**
6. Авторизуйтесь в GitHub и разрешите доступ к репозиторию
7. Выберите репозиторий: `marketingdep-a11y/livrichy-website`
8. Выберите ветку: `main` или `master`

### Шаг 1.5: Настройка приложения в форме создания

После подключения GitHub App, вы увидите форму настройки:

1. **Repository:** Должен быть выбран `livrichy-website` ✅
2. **Branch:** ⚠️ **ВАЖНО!** Выберите `main` или `master` (не dev/feature ветки)
3. **Build Pack:** Можно оставить `Nixpacks` (автоматически определит Laravel)
4. **Base Directory:** ⚠️ **КРИТИЧЕСКИ ВАЖНО!** Укажите: `mysite`
   - Это папка где находится ваш Laravel проект
   - Без этого Coolify не найдет ваш проект!
   - **НЕ указывайте `/` - это корень репозитория, а проект в подпапке!**
5. **Port:** Можно оставить `3000` или оставить пустым (Coolify сам назначит)
6. **Is it a static site?:** ❌ Оставьте **не отмеченным** (Laravel - это динамическое приложение)

### Шаг 1.6: Настройка в разделе Configuration

После создания приложения, в разделе **"Configuration" → "General"** проверьте:

1. **Base Directory:** Должно быть `mysite` (БЕЗ `/` в начале!)
   - ❌ Неправильно: `/mysite` (со слешем)
   - ✅ Правильно: `mysite` (без слеша)
   - ⚠️ **Важно:** Coolify может автоматически добавлять `/`, но нужно чтобы было просто `mysite`
   - Измените на `mysite` (без `/` в начале) и нажмите "Save"
   
2. **Build Command:** 
   - ✅ **Рекомендуется:** Укажите явно:
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
   ```
   - Или оставьте пустым - Nixpacks определит Laravel автоматически, но лучше указать явно
   - ⚠️ **Важно:** НЕ добавляйте `cd mysite &&` - команды уже выполняются в Base Directory

3. **Install Command:** Оставьте пустым (не требуется)

4. **Start Command:** Оставьте пустым - Nixpacks определит автоматически для Laravel

5. **Pre-deployment Command:** Добавьте (если нужно):
   ```bash
   php artisan config:clear && php artisan cache:clear
   ```

6. **Post-deployment Command:** Добавьте:
   ```bash
   php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan statamic:stache:refresh && php artisan optimize
   ```

5. **Publish Directory:** Должно быть `/` или `/public` (для Laravel обычно `/`)

В разделе **"Advanced"**:
- **Ports Exposes:** `3000` (можно оставить)
- **Ports Mappings:** `3000:3000` (можно оставить)

### Шаг 2: Настройка типа приложения

Coolify должен автоматически определить Laravel, но проверьте:

1. **Type:** `PHP` или `Laravel`
2. **PHP Version:** `8.2` или выше
3. **Root Directory:** `mysite` (так как ваш проект в этой папке)

### Шаг 3: Настройка Build Settings

#### Build Command:
```bash
cd mysite && composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
```

#### Start Command:
Coolify обычно автоматически настраивает это для Laravel, но можно указать:
```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

**Или если Coolify использует PHP-FPM:**
Настройка происходит автоматически, просто убедитесь что:
- **Document Root:** `mysite/public`

### Шаг 4: Настройка Environment Variables

В Coolify панели добавьте переменные окружения (Environment Variables):

#### ⚡ Быстрый способ (рекомендуется):

1. Откройте раздел **"Environment Variables"** в Coolify
2. Найдите большое текстовое поле **"Production Environment Variables"**
3. **Для SQLite:** Скопируйте переменные из файла `COOLIFY_ENV_VARS_SQLITE.txt`
   **Для MySQL:** Скопируйте переменные из файла `COOLIFY_ENV_VARS.txt`
4. Вставьте в текстовое поле - формат: `KEY=VALUE`, каждая переменная на новой строке
5. **ВАЖНО:** Заполните реальные значения для:
   - `APP_URL` - укажите ваш домен из Coolify
   - `APP_KEY` - сгенерируйте после первого деплоя или оставьте пустым
   - **Для SQLite:** `DB_DATABASE` должен указывать на `/app/mysite/database/database.sqlite` (путь в контейнере)
   - **Для MySQL:** `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - данные из базы данных в Coolify
   - `STATAMIC_LICENSE_KEY` - ваш ключ лицензии Statamic
6. Нажмите **"Save All Environment Variables"**

#### 📝 Особенности для SQLite на Coolify:

Если вы используете **SQLite**, важно понять разницу путей:

**На вашем компьютере (локально):**
- Путь: `mysite/database/database.sqlite`
- Полный путь: `/Users/paveltraskou/Documents/Livrichy/mysite/database/database.sqlite`

**В Docker контейнере Coolify:**
- Путь: `/app/mysite/database/database.sqlite`
- `/app` - это корень где находится весь код приложения
- `mysite` - это Base Directory, который вы указали в настройках
- `database/database.sqlite` - относительный путь от Base Directory

**Важно настроить:**
- `DB_CONNECTION=sqlite`
- `DB_DATABASE=/app/mysite/database/database.sqlite` (путь в контейнере!)
- Persistent Storage для папки `/app/mysite/database` чтобы данные сохранялись
- Убедитесь что папка `database` имеет права на запись (обычно автоматически)

#### Обязательные переменные (минимум):

```env
APP_NAME=Statamic
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-coolify-domain.com
APP_KEY=  # Сгенерируйте после первого деплоя

DB_CONNECTION=mysql
DB_HOST=  # Укажите хост БД из Coolify
DB_PORT=3306
DB_DATABASE=  # Имя базы данных
DB_USERNAME=  # Пользователь БД
DB_PASSWORD=  # Пароль БД

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

STATAMIC_LICENSE_KEY=your-license-key
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full
STATAMIC_API_ENABLED=false
STATAMIC_GRAPHQL_ENABLED=false
```

#### Как добавить переменные в Coolify:

1. В настройках приложения найдите **"Environment Variables"** или **"Env"**
2. Нажмите **"+ Add"** или **"New Variable"**
3. Добавьте каждую переменную по отдельности
4. Сохраните изменения

### Шаг 5: Настройка базы данных

#### Если используете SQLite (рекомендуется для начала):

1. **Важно: Настройте Persistent Storage для сохранения данных!**
   - В Coolify откройте раздел **"Persistent Storage"** в настройках приложения
   - Добавьте Persistent Volume:
     - **Path в контейнере:** `/app/mysite/database`
     - **Mount Path:** можно оставить пустым или указать имя volume
     - Это сохранит файл базы данных между перезапусками контейнера
   
2. **Локальный путь:** `mysite/database/database.sqlite` (на вашем компьютере)
   **Путь в контейнере:** `/app/mysite/database/database.sqlite` (внутри Docker контейнера)
   
3. SQLite использует файл базы данных:
   - Если файл уже есть в репозитории - он будет использован
   - Если файла нет - он создастся автоматически при первом деплое и миграциях
   
4. В Environment Variables укажите:
   - `DB_CONNECTION=sqlite`
   - `DB_DATABASE=/app/mysite/database/database.sqlite` (путь в контейнере Coolify)
   - ⚠️ Это путь ВНУТРИ контейнера, а не на вашем компьютере!

⚠️ **Без Persistent Storage данные базы будут теряться при каждом перезапуске контейнера!**

#### Если используете MySQL:

1. В Coolify создайте базу данных MySQL:
   - **Resources** → **Databases** → **Add Database**
   - Выберите **MySQL**
   - Создайте базу данных
2. Скопируйте данные подключения:
   - Host (может быть внутреннее имя сервиса, например `mysql-service`)
   - Database name
   - Username
   - Password
   - Port
3. Добавьте эти данные в Environment Variables вашего приложения

### Шаг 6: Настройка Root Directory

Поскольку ваш Laravel проект находится в `mysite/` директории:

1. В настройках приложения найдите **"Root Directory"** или **"Build Path"**
2. Укажите: `mysite`
3. Или в Build Command добавьте `cd mysite &&` перед командами

**Важно:** Убедитесь что Coolify знает где находится ваш проект!

### Шаг 7: Первый деплой

1. После всех настроек нажмите **"Deploy"** или **"Save & Deploy"**
2. Coolify автоматически:
   - Склонирует репозиторий
   - Установит зависимости (Composer + NPM)
   - Соберет assets
   - Запустит приложение
3. Следите за процессом в логах деплоя

### Шаг 8: После первого деплоя

После успешного деплоя выполните команды через Coolify Console:

1. В панели найдите ваше приложение
2. Откройте **"Exec"** или **"Shell"** или **"Console"**
3. Выполните команды:

```bash
# Сгенерируйте APP_KEY
php artisan key:generate

# Запустите миграции
php artisan migrate --force

# Очистите и закэшируйте конфигурацию
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Обновите Statamic Stache
php artisan statamic:stache:refresh

# Оптимизируйте приложение
php artisan optimize
```

## 🔄 Автоматический деплой

После настройки:

1. Вы делаете изменения в коде
2. Делаете `git commit` и `git push` в ветку `main`
3. **Coolify автоматически:**
   - Обнаруживает новый commit через GitHub webhook
   - Клонирует репозиторий
   - Устанавливает зависимости
   - Собирает assets
   - Перезапускает приложение
   - Обновляет ваш сайт

**Автоматический деплой включен по умолчанию после подключения GitHub App!** 🎉

## 📝 Важные настройки для Laravel/Statamic

### Port Configuration
Coolify автоматически назначает порт. В Environment Variables:
- `PORT` устанавливается автоматически

### Public Directory
Убедитесь что:
- **Document Root** или **Public Directory** указывает на `mysite/public`

### Storage Permissions
После первого деплоя проверьте права (через Console):
```bash
chmod -R 775 storage bootstrap/cache
```

## 🔍 Проверка после деплоя

1. Откройте ваш сайт в браузере (домен из Coolify)
2. Проверьте логи в Coolify панели (Logs)
3. Проверьте админ-панель Statamic: `https://your-domain.com/cp`
4. Проверьте что assets загружаются корректно

## 🆘 Решение проблем

### Проблема: Build fails

**Ошибка: "Nixpacks failed to detect the application type"**

Эта ошибка возникает когда Nixpacks не может определить тип приложения автоматически. **Решение:**

#### ✅ Решение 1: Использовать Dockerfile (РЕКОМЕНДУЕТСЯ)

Если Nixpacks все еще не работает, лучше использовать Dockerfile:

1. **Dockerfile уже создан и в git:**
   - Файл `mysite/Dockerfile` содержит всю необходимую конфигурацию
   - Уже закоммичен в репозиторий
   
2. **В Coolify проверьте настройки:**
   - Configuration → General → Build Pack
   - Должно быть: `Dockerfile` (НЕ Nixpacks!)
   - Base Directory: `mysite` (без `/`)
   - Сохраните
   
3. **Если Build Pack уже = Dockerfile, но всё равно не работает:**
   - Убедитесь что Base Directory = `mysite` (без `/`)
   - Проверьте что последний коммит в git содержит Dockerfile
   - Попробуйте очистить Build Command и Install Command (оставить пустыми)
   - Проверьте порт: должно быть `8000` (как в Dockerfile), НЕ `3000`
   
4. **Преимущества Dockerfile:**
   - Полный контроль над процессом сборки
   - Не зависит от автоопределения Nixpacks
   - Более надежный способ деплоя

#### ✅ Решение 2: Улучшить nixpacks.toml

Если хотите попробовать Nixpacks еще раз:

1. **Файл `nixpacks.toml` обновлен** с явным указанием провайдера:
   - Добавлен `[providers] php = true` для явного указания PHP провайдера

2. **Добавьте в git:**
   ```bash
   git add mysite/nixpacks.toml
   git commit -m "Update nixpacks.toml with explicit provider"
   git push
   ```

3. **В Coolify:**
   - Убедитесь что Build Pack = `Nixpacks`
   - Base Directory = `mysite` (без `/`)
   - Build Command можно оставить пустым
   - Запустите деплой

**Рекомендация:** Используйте Dockerfile (Решение 1) - это более надежный способ.

#### Другие проверки:

1. **Проверьте Base Directory:**
   - Должно быть: `mysite` (НЕ `/mysite` и НЕ `/`)

2. **Проверьте что в `mysite/` есть файлы:**
   - `composer.json`
   - `artisan` (Laravel)
   - `package.json`
   - `nixpacks.toml` (теперь должен быть)

3. **Проверьте логи в Coolify панели (Deployment Logs)**
4. **Проверьте что все Environment Variables установлены**
5. **Убедитесь что PHP версия правильная (8.2+)**

### Проблема: Database connection error

**Для SQLite:**
- Убедитесь что `DB_DATABASE` указывает на правильный путь: `/app/mysite/database/database.sqlite`
- Проверьте права доступа к файлу базы данных и папке `database`
- Убедитесь что файл существует или будет создан при миграциях
- В Coolify настройте Persistent Storage для папки `mysite/database` чтобы данные сохранялись

**Для MySQL:**
- Проверьте данные БД в Environment Variables
- Убедитесь что база данных создана в Coolify
- Проверьте что Host правильный (может быть внутреннее имя сервиса)

### Проблема: 500 ошибка
- Установите `APP_DEBUG=true` временно для отладки
- Посмотрите логи в Coolify (Logs раздел)
- Убедитесь что `APP_KEY` сгенерирован
- Проверьте права доступа к `storage` и `bootstrap/cache`

### Проблема: Assets не загружаются
- Убедитесь что `npm run build` выполняется в Build Command
- Проверьте что `public/build` существует
- Проверьте пути в `vite.config.js`

## 📚 Полезные ссылки

- [Coolify Documentation](https://coolify.io/docs)
- [Coolify GitHub](https://github.com/coollabsio/coolify)
- [Coolify GitHub App Setup](https://coolify.io/docs/knowledge-base/git/github/manually-setup-github-app)

---

**Вывод:** Coolify отлично подходит для Laravel/Statamic проектов! Просто подключите GitHub App, настройте Root Directory (`mysite`), добавьте Environment Variables и всё готово! 🚀

