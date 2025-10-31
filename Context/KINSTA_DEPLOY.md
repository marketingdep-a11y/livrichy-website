# 🚀 Деплой на Kinsta через GitHub App

Отличный выбор! Kinsta имеет встроенную интеграцию с GitHub, которая значительно упрощает процесс деплоя.

## ✅ Преимущества Kinsta:

- ✅ Автоматический деплой при каждом push в GitHub
- ✅ Не нужно настраивать SSH ключи вручную
- ✅ Автоматическая установка зависимостей
- ✅ Автоматическая сборка assets
- ✅ Простое управление через панель
- ✅ Автоматические SSL сертификаты
- ✅ Встроенный мониторинг и логи

## 📋 Настройка деплоя на Kinsta

### Шаг 1: Подключение GitHub App в Kinsta

1. **Войдите в панель Kinsta** (MyKinsta)
2. Перейдите в ваш сайт → **Settings**
3. Найдите раздел **"Deployments"** или **"GitHub"**
4. Нажмите **"+ Add GitHub App"** или **"Connect GitHub"**
5. Следуйте инструкциям для авторизации GitHub App
6. Выберите репозиторий: `marketingdep-a11y/livrichy-website`
7. Выберите ветку: `main` или `master`

### Шаг 2: Настройка Build Settings в Kinsta

Kinsta автоматически определит, что это Laravel проект, но нужно настроить несколько параметров:

#### Build Command:
```bash
composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
```

#### Start Command:
```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

**Или для production:**
Обычно Kinsta автоматически использует правильные команды для Laravel.

#### Environment Variables:
В Kinsta панели добавьте переменные окружения (они будут использоваться вместо .env):

```
APP_NAME=Statamic
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:your-generated-key

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

STATAMIC_LICENSE_KEY=your-license-key
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full
```

### Шаг 3: Настройка структуры проекта

Важно: Kinsta ожидает, что Laravel проект находится в корне репозитория ИЛИ нужно указать путь.

**Вариант 1: Если проект в `mysite/` директории**

В Kinsta настройках укажите:
- **Root Directory:** `mysite`

**Вариант 2: Изменить структуру репозитория** (если хотите, чтобы проект был в корне)

### Шаг 4: Первый деплой

1. После подключения GitHub App, Kinsta автоматически запустит первый деплой
2. Или вручную: **Deployments → Deploy now**
3. Следите за процессом в панели Kinsta

### Шаг 5: Настройка базы данных в Kinsta

1. В панели Kinsta создайте базу данных MySQL (если еще нет)
2. Скопируйте данные подключения:
   - Host
   - Database name
   - Username
   - Password
3. Добавьте их в Environment Variables в Kinsta

### Шаг 6: После первого деплоя

После успешного деплоя выполните через SSH или через Kinsta Console:

```bash
# Сгенерируйте APP_KEY
php artisan key:generate

# Запустите миграции
php artisan migrate --force

# Очистите кэш
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan statamic:stache:refresh

# Оптимизируйте
php artisan optimize
```

## 🔄 Автоматический деплой

После настройки:

1. Вы делаете изменения в коде
2. Делаете `git commit` и `git push` в ветку `main`
3. **Kinsta автоматически:**
   - Обнаруживает новый commit
   - Клонирует репозиторий
   - Устанавливает зависимости (Composer + NPM)
   - Собирает assets
   - Запускает деплой
   - Обновляет ваш сайт

**Никаких дополнительных настроек GitHub Actions не требуется!** 🎉

## 📝 Важные настройки для Laravel/Statamic на Kinsta

### Root Directory
Если ваш проект в `mysite/`, укажите в Kinsta настройках:
- Root Directory: `mysite`

### Build Settings
```yaml
Build Command:
  composer install --no-dev --optimize-autoloader --no-interaction
  npm ci
  npm run build

Start Command:
  php artisan serve --host=0.0.0.0 --port=$PORT
```

### Environment Variables (важно!)
Добавьте все необходимые переменные в Kinsta панели, особенно:
- `APP_KEY` - сгенерируйте через `php artisan key:generate`
- `DB_*` - данные базы данных
- `STATAMIC_LICENSE_KEY`

## 🛠️ Доступ к серверу через Kinsta

### Через MyKinsta Console:
1. Ваш сайт → Tools → Open console
2. Вы получаете доступ к shell

### Через SSH:
```bash
ssh your-site@ssh.kinsta.cloud
```

## ⚙️ Настройка .htaccess или Nginx

Kinsta автоматически настраивает веб-сервер для Laravel. Но убедитесь что:

1. **Document Root** указывает на `public` директорию
2. В Kinsta настройках указан правильный Root Directory

## 🔍 Проверка после деплоя

1. Откройте ваш сайт в браузере
2. Проверьте логи в Kinsta панели (Tools → Logs)
3. Проверьте админ-панель Statamic: `https://your-domain.com/cp`

## 🆘 Решение проблем

### Проблема: Build fails
- Проверьте логи в Kinsta панели
- Убедитесь что все Environment Variables установлены
- Проверьте версии PHP и Node.js в настройках

### Проблема: Database connection error
- Проверьте данные БД в Environment Variables
- Убедитесь что база данных создана в Kinsta

### Проблема: 500 ошибка
- Проверьте `APP_DEBUG=true` временно для отладки
- Посмотрите логи в Kinsta панели
- Убедитесь что `APP_KEY` установлен

### Проблема: Assets не загружаются
- Убедитесь что `npm run build` выполняется в Build Command
- Проверьте что `public/build` не в .gitignore (или создается при билде)

## 📚 Полезные ссылки

- [Kinsta Laravel Documentation](https://kinsta.com/docs/application-hosting/laravel/)
- [Kinsta GitHub Integration](https://kinsta.com/docs/git/)
- [Kinsta Environment Variables](https://kinsta.com/help/environment-variables/)

## ✨ Преимущества перед ручной настройкой

| Параметр | Ручная настройка | Kinsta |
|----------|-----------------|--------|
| SSH ключи | Нужно настраивать | Автоматически |
| GitHub Actions | Нужно настраивать | Не нужны |
| Build процесс | Нужно настраивать | Автоматический |
| SSL сертификат | Нужно настраивать | Автоматический |
| Мониторинг | Нужно настраивать | Встроенный |
| Логи | Через SSH | В панели |

---

**Вывод:** Да, Kinsta значительно упрощает процесс! Просто подключите GitHub App и настройте Environment Variables. Остальное Kinsta сделает автоматически! 🚀


