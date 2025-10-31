# 🚀 Настройка проекта на Хостингере (Hostinger)

## Шаг 1: Получение SSH доступа на Хостингере

1. **Войдите в панель управления Хостингера** (hPanel)
2. Перейдите в **Advanced → SSH Access**
3. Если SSH доступ не включен, включите его
4. Запомните или создайте SSH пользователя и пароль (или настройте SSH ключ)

## Шаг 2: Определение пути для проекта

На Хостингере обычно структура такая:
- Главная директория: `/home/u123456789` (где u123456789 - ваш username)
- Для сайтов: `/home/u123456789/public_html` или `/home/u123456789/domains/yourdomain.com/public_html`

**Важно:** Ваш Laravel/Statamic проект должен находиться в `/home/u123456789/public_html` (или в соответствующей папке домена)

## Шаг 3: Подключение к серверу через SSH

На вашем локальном компьютере выполните:

```bash
ssh username@your-server.hosting.com
# или
ssh username@your-ip-address
```

Введите пароль (или используйте SSH ключ).

**Найти данные для подключения:**
- SSH Host: указан в панели Хостингера (Advanced → SSH Access)
- Username: ваш SSH username (обычно `u123456789`)
- Port: обычно `65002` или `22` (проверьте в панели)

## Шаг 4: Клонирование репозитория

После подключения к серверу выполните:

### Вариант 1: Использование SSH (рекомендуется для приватных репозиториев)

```bash
# На сервере создайте SSH ключ для GitHub
ssh-keygen -t ed25519 -C "hostinger-deploy"

# Покажите публичный ключ
cat ~/.ssh/id_ed25519.pub
```

**Добавьте ключ в GitHub:**
1. Скопируйте вывод команды выше (публичный ключ)
2. Перейдите в GitHub → Settings → SSH and GPG keys → New SSH key
3. Вставьте ключ и сохраните

**Затем клонируйте через SSH:**
```bash
cd /home/u123456789/public_html

# Если там уже есть файлы, переместите их
mv public_html public_html_old 2>/dev/null || true

# Клонируйте через SSH
git clone git@github.com:your-username/your-repo-name.git public_html

# Перейдите в директорию проекта
cd public_html/mysite
```

### Вариант 2: Использование Personal Access Token (для HTTPS)

**Создайте токен в GitHub:**
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token (classic)
3. Выберите scope: `repo` (для приватных репозиториев)
4. Скопируйте токен (он показывается только один раз!)

**Клонируйте с токеном:**
```bash
cd /home/u123456789/public_html

# При запросе пароля используйте токен вместо пароля
git clone https://github.com/your-username/your-repo-name.git public_html

# При запросе:
# Username: ваш_github_username
# Password: вставьте_ваш_токен (не пароль GitHub!)
```

### Вариант 3: Публичный репозиторий (простой способ)

Если репозиторий публичный, можно клонировать без аутентификации:

```bash
cd /home/u123456789/public_html
git clone https://github.com/your-username/your-repo-name.git public_html
cd public_html/mysite
```

### ⚠️ Решение проблемы 403 "Write access to repository not granted"

Эта ошибка возникает потому что:
- Репозиторий приватный и требуется аутентификация
- Используется устаревший способ аутентификации (пароль GitHub не работает с HTTPS)

**Решение:**
1. Используйте SSH (Вариант 1) - самый надежный способ
2. Или используйте Personal Access Token (Вариант 2)
3. Проверьте, что у вас есть доступ к репозиторию

## Шаг 5: Установка зависимостей на Хостингере

```bash
cd /home/u123456789/public_html/mysite

# Проверьте версию PHP (нужна 8.2+)
php -v

# Если нужно, установите нужную версию через панель Хостингера
# Advanced → PHP Version → выберите 8.2

# Установите Composer зависимости
composer install --no-dev --optimize-autoloader --no-interaction

# Если composer не установлен, установите его:
# curl -sS https://getcomposer.org/installer | php
# mv composer.phar /usr/local/bin/composer

# Установите Node.js и NPM (если не установлены)
# Через панель Хостингера: Advanced → Node.js Selector

# Установите NPM зависимости
npm install

# Соберите assets
npm run build
```

## Шаг 6: Настройка .env файла

```bash
cd /home/u123456789/public_html/mysite

# Скопируйте .env.example
cp .env.example .env

# Откройте для редактирования
nano .env
```

**Важные настройки для .env на Хостингере:**

```env
APP_NAME="Statamic"
APP_ENV=production
APP_KEY=  # Сгенерируйте командой ниже
APP_DEBUG=false
APP_URL=https://your-domain.com

# База данных (данные из панели Хостингера)
DB_CONNECTION=mysql
DB_HOST=localhost  # или указанный в панели
DB_PORT=3306
DB_DATABASE=your_database_name  # из панели Хостингера
DB_USERNAME=your_database_user  # из панели Хостингера
DB_PASSWORD=your_database_password  # из панели Хостингера

# Кэш и сессии
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Statamic
STATAMIC_LICENSE_KEY=your-license-key
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full

# Логи
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Сгенерируйте APP_KEY:**
```bash
php artisan key:generate
```

## Шаг 7: Настройка базы данных на Хостингере

1. В панели Хостингера перейдите в **Databases → MySQL Databases**
2. Создайте базу данных (если еще не создана)
3. Запомните:
   - Имя базы данных
   - Имя пользователя
   - Пароль
   - Хост (обычно `localhost`)

4. Запустите миграции:
```bash
php artisan migrate --force
```

## Шаг 8: Настройка прав доступа

```bash
cd /home/u123456789/public_html/mysite

# Установите права
chmod -R 755 storage bootstrap/cache
chmod -R 755 public

# Установите владельца (ваш username на хостинге)
chown -R u123456789:u123456789 storage bootstrap/cache
```

## Шаг 9: Настройка веб-сервера (Apache)

На Хостингере обычно используется Apache. Вам нужно настроить `.htaccess` или создать конфигурацию.

### Вариант 1: Использовать существующий public/.htaccess

Убедитесь, что файл `public/.htaccess` существует. Laravel обычно его создает.

### Вариант 2: Настроить Document Root

В панели Хостингера:
1. Перейдите в **Domains → Manage → General Settings**
2. Измените **Document Root** на: `/home/u123456789/public_html/mysite/public`
3. Сохраните изменения

Или создайте файл `.htaccess` в корне `public_html`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ mysite/public/$1 [L]
</IfModule>
```

## Шаг 10: Настройка для GitHub Actions (автоматический деплой)

После того как репозиторий склонирован, настройте GitHub Secrets:

### Получение SSH данных для GitHub Actions

1. **HOST**: 
   - В панели Хостингера: Advanced → SSH Access
   - Обычно: `your-domain.com` или IP адрес

2. **USERNAME**:
   - Ваш SSH username (обычно `u123456789`)

3. **SSH_KEY**:
   - Создайте SSH ключ на вашем локальном компьютере:
   ```bash
   ssh-keygen -t ed25519 -C "github-actions-hostinger"
   ```
   - Скопируйте приватный ключ:
   ```bash
   cat ~/.ssh/id_ed25519
   ```
   - Добавьте в GitHub Secrets как `SSH_KEY`
   
   - Скопируйте публичный ключ на Хостингер:
   ```bash
   ssh-copy-id -i ~/.ssh/id_ed25519.pub username@your-domain.com
   ```
   Или вручную добавьте в `~/.ssh/authorized_keys` на сервере

4. **PORT**:
   - Обычно `65002` на Хостингере (проверьте в панели)

5. **DEPLOY_PATH**:
   - `/home/u123456789/public_html/mysite` (полный путь к директории `mysite`)

### Добавление в GitHub Secrets:

1. Перейдите в ваш репозиторий на GitHub
2. Settings → Secrets and variables → Actions → New repository secret
3. Добавьте все секреты как указано выше

## Шаг 11: Первый деплой

После настройки GitHub Secrets, сделайте:

```bash
# На локальном компьютере
git add .
git commit -m "Initial production setup"
git push origin main
```

GitHub Actions автоматически задеплоит на Хостингер!

## Проверка после установки

1. **Откройте сайт в браузере**: `https://your-domain.com`
2. **Проверьте логи** (если есть ошибки):
   ```bash
   tail -f /home/u123456789/public_html/mysite/storage/logs/laravel.log
   ```
3. **Проверьте админ-панель Statamic**: `https://your-domain.com/cp`

## Решение проблем

### Проблема: "Permission denied" при клонировании
```bash
# Убедитесь что у вас есть права на директорию
chmod 755 /home/u123456789/public_html
```

### Проблема: PHP версия неправильная
- В панели Хостингера: Advanced → PHP Version → выберите 8.2

### Проблема: Composer не найден
```bash
# Установите Composer глобально
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### Проблема: Node.js не найден
- В панели Хостингера: Advanced → Node.js Selector
- Установите последнюю LTS версию

### Проблема: SSH подключение не работает
- Проверьте SSH доступ в панели: Advanced → SSH Access
- Используйте правильный порт (обычно `65002` на Хостингере)
- Проверьте firewall настройки в панели

## Важные примечания для Хостингера

1. **Путь к проекту**: Обычно `/home/u123456789/public_html/mysite`
2. **PHP версия**: Должна быть 8.2+ (настройте в панели)
3. **База данных**: Создайте через панель и используйте `localhost` как хост
4. **SSH порт**: Обычно `65002`, а не стандартный `22`
5. **Document Root**: Должен указывать на `mysite/public`

---

**Готово!** Теперь ваш проект настроен на Хостингере и готов к автоматическому деплою через GitHub Actions! 🎉

