# 🚀 Настройка автоматического деплоя

> 💡 **Деплой на хостинг:**
> - **Coolify?** Смотрите: **[COOLIFY_DEPLOY.md](COOLIFY_DEPLOY.md)** ⭐ (рекомендуется!)
> - **Kinsta?** Смотрите: **[KINSTA_DEPLOY.md](KINSTA_DEPLOY.md)**
> - **Хостингер?** Смотрите: **[HOSTINGER_SETUP.md](HOSTINGER_SETUP.md)**

## Что было сделано

Я подготовил ваш проект к автоматическому деплою на production сервер. Вот что создано:

### ✅ Созданные файлы:

1. **`.env.example`** - шаблон для production окружения с правильными настройками
2. **`.github/workflows/deploy.yml`** - GitHub Actions workflow для автоматического деплоя
3. **`mysite/deploy.sh`** - скрипт для ручного деплоя (альтернатива)
4. **`DEPLOYMENT.md`** - подробная документация на русском языке

## 📋 Что нужно сделать сейчас

### Шаг 1: Подготовка сервера

1. **Зайдите на ваш хостинг/server** и выполните:
   ```bash
   # Клонируйте репозиторий (если еще не сделали)
   git clone https://github.com/your-username/your-repo.git /var/www/html/mysite
   cd /var/www/html/mysite/mysite
   
   # Создайте .env файл
   cp .env.example .env
   nano .env  # Отредактируйте под ваши настройки
   
   # Сгенерируйте APP_KEY
   php artisan key:generate
   
   # Установите зависимости
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   
   # Запустите миграции
   php artisan migrate --force
   
   # Настройте права
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Шаг 2: Настройка GitHub Secrets

Вам нужно добавить секреты в GitHub:

1. Перейдите в ваш репозиторий на GitHub
2. Settings → Secrets and variables → Actions → New repository secret
3. Добавьте следующие секреты:

   | Название | Значение | Пример |
   |----------|----------|--------|
   | `HOST` | IP или домен сервера | `123.45.67.89` или `example.com` |
   | `USERNAME` | SSH пользователь | `root` или `www-data` |
   | `SSH_KEY` | Приватный SSH ключ | См. инструкцию ниже |
   | `PORT` | SSH порт (опционально) | `22` |
   | `DEPLOY_PATH` | Путь к проекту на сервере | `/var/www/html/mysite/mysite` |

#### 🔑 Как создать SSH ключ:

```bash
# На вашем локальном компьютере:
ssh-keygen -t ed25519 -C "github-actions"

# Скопируйте приватный ключ (для SSH_KEY в GitHub):
cat ~/.ssh/id_ed25519

# Скопируйте публичный ключ на сервер:
ssh-copy-id -i ~/.ssh/id_ed25519.pub username@your-server.com
```

### Шаг 3: Первая настройка .env на production

На сервере откройте файл `.env` и обязательно укажите:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# База данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Statamic
STATAMIC_LICENSE_KEY=your-license-key-here
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full
```

## 🎯 Как это работает

После настройки:

1. Вы делаете изменения в коде
2. Делаете `git commit` и `git push` в ветку `main` или `master`
3. GitHub Actions **автоматически**:
   - Устанавливает зависимости
   - Собирает assets (Vite)
   - Отправляет файлы на сервер через SSH
   - Запускает миграции
   - Оптимизирует приложение
   - Очищает кэш

4. Ваш сайт обновляется автоматически! 🎉

## ⚠️ Важные замечания

1. **Никогда не коммитьте `.env` файл** - он уже в `.gitignore`
2. **Всегда проверяйте `APP_DEBUG=false`** на production
3. **Убедитесь что `STATAMIC_LICENSE_KEY`** установлен
4. **Проверьте права доступа** к `storage` и `bootstrap/cache`
5. **Регулярно делайте бэкапы** базы данных

## 🔧 Проблемы?

Если что-то не работает:

1. Проверьте логи GitHub Actions: репозиторий → вкладка Actions
2. Проверьте SSH подключение:
   ```bash
   ssh -i ~/.ssh/your_key username@your-server.com
   ```
3. Проверьте логи на сервере:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 📚 Дополнительная информация

Подробная документация находится в файле **`DEPLOYMENT.md`** - там есть все детали про настройку веб-сервера, решение проблем и многое другое.

---

**Вопросы?** Проверьте `DEPLOYMENT.md` для подробных инструкций!

