# Инструкция по развертыванию (Deployment Guide)

Это руководство поможет вам настроить автоматический деплой вашего Laravel/Statamic приложения на production сервер при каждом push в GitHub.

## 📋 Что нужно сделать для подготовки к production

### 1. Настройка .env файла на production сервере

Создайте файл `.env` на вашем production сервере на основе `.env.example`. Важные настройки для production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Cache & Sessions
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Statamic
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full
```

### 2. Настройка GitHub Secrets

Для работы автоматического деплоя вам нужно добавить следующие секреты в GitHub:

1. Перейдите в ваш репозиторий на GitHub
2. Settings → Secrets and variables → Actions
3. Добавьте следующие секреты:

   - `HOST` - IP адрес или домен вашего сервера (например: `123.45.67.89` или `example.com`)
   - `USERNAME` - имя пользователя для SSH подключения (например: `root` или `www-data`)
   - `SSH_KEY` - приватный SSH ключ для подключения к серверу
   - `PORT` - порт SSH (обычно `22`, можно не указывать если стандартный)
   - `DEPLOY_PATH` - путь к директории на сервере где находится проект (например: `/var/www/html/mysite`)

#### Как создать SSH ключ:

1. На вашем локальном компьютере выполните:
   ```bash
   ssh-keygen -t ed25519 -C "github-actions"
   ```

2. Скопируйте приватный ключ (файл без `.pub`):
   ```bash
   cat ~/.ssh/id_ed25519
   ```
   Скопируйте весь вывод и добавьте в GitHub Secret `SSH_KEY`

3. Скопируйте публичный ключ на сервер:
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519.pub username@your-server.com
   ```
   Или вручную добавьте содержимое `~/.ssh/id_ed25519.pub` в файл `~/.ssh/authorized_keys` на сервере

### 3. Первоначальная настройка на сервере

Перед первым деплоем вам нужно:

1. **Клонировать репозиторий на сервер:**
   ```bash
   git clone https://github.com/your-username/your-repo.git /var/www/html/mysite
   cd /var/www/html/mysite/mysite
   ```

2. **Создать .env файл:**
   ```bash
   cp .env.example .env
   nano .env  # Отредактируйте под ваши настройки
   ```

3. **Сгенерировать APP_KEY:**
   ```bash
   php artisan key:generate
   ```

4. **Установить зависимости:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install
   npm run build
   ```

5. **Запустить миграции:**
   ```bash
   php artisan migrate --force
   ```

6. **Настроить права доступа:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### 4. Настройка веб-сервера

Убедитесь, что ваш веб-сервер (Apache/Nginx) настроен правильно:

#### Для Apache:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/mysite/mysite/public

    <Directory /var/www/html/mysite/mysite/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

#### Для Nginx:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/mysite/mysite/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🚀 Как работает автоматический деплой

После настройки:

1. Когда вы делаете `git push` в ветку `main` или `master`
2. GitHub Actions автоматически:
   - Устанавливает зависимости (Composer и NPM)
   - Собирает assets (Vite)
   - Создает пакет для деплоя
   - Отправляет файлы на сервер через SSH
   - Выполняет команды на сервере (миграции, оптимизация, кэширование)
   - Очищает временные файлы

## 🔧 Ручной деплой (альтернатива)

Если вы предпочитаете деплоить вручную или через git hook на сервере, используйте скрипт `deploy.sh`:

```bash
chmod +x deploy.sh
./deploy.sh
```

## ⚠️ Важные замечания

1. **Никогда не коммитьте .env файл** - он уже в .gitignore
2. **Всегда проверяйте APP_DEBUG=false** на production
3. **Убедитесь что STATAMIC_LICENSE_KEY** установлен в .env
4. **Проверьте права доступа** к storage и bootstrap/cache
5. **Регулярно делайте бэкапы базы данных** перед деплоем

## 📝 Проверка после деплоя

После успешного деплоя проверьте:

1. Сайт открывается по вашему домену
2. Нет ошибок в логах: `tail -f storage/logs/laravel.log`
3. Админ-панель Statamic работает: `https://your-domain.com/cp`
4. Статические файлы загружаются корректно
5. Миграции применены успешно

## 🆘 Решение проблем

### Если деплой не работает:

1. Проверьте логи GitHub Actions в репозитории → Actions
2. Проверьте SSH подключение вручную:
   ```bash
   ssh -i ~/.ssh/your_key username@your-server.com
   ```
3. Убедитесь что все пути правильные в GitHub Secrets
4. Проверьте права доступа на сервере

### Если сайт не работает после деплоя:

1. Проверьте логи: `php artisan log:show` или `tail -f storage/logs/laravel.log`
2. Очистите кэш: `php artisan cache:clear && php artisan config:clear`
3. Проверьте права: `ls -la storage bootstrap/cache`
4. Убедитесь что .env файл существует и настроен правильно

## 📞 Поддержка

Если у вас возникли проблемы, проверьте:
- [Laravel Documentation](https://laravel.com/docs)
- [Statamic Documentation](https://statamic.com/docs)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)


