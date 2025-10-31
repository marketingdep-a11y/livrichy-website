FROM php:8.3-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Настройка Git для Composer (для работы с GitHub репозиториями)
RUN git config --global --add safe.directory /app

# Рабочая директория
WORKDIR /app

# Копирование файлов проекта
# Проект находится прямо в /app (Base Directory = /)
COPY . /app

# Создаем директорию для базы данных и файл базы данных (нужен для composer install)
RUN mkdir -p database && touch database/database.sqlite

# Установка зависимостей
# Используем --prefer-dist для более быстрой установки из GitHub
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
RUN rm -rf node_modules package-lock.json && npm install
RUN npm run build

# Создаем необходимые директории для Statamic и Glide
RUN mkdir -p storage/statamic/glide \
    && mkdir -p storage/statamic/glide/tmp \
    && mkdir -p storage/framework/cache/glide \
    && mkdir -p public/assets

# Настройка прав
RUN chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 storage/statamic \
    && chmod -R 775 storage/framework/cache \
    && chmod -R 775 public/assets

# Копирование entrypoint скрипта
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose порт
EXPOSE 8000

# Использование entrypoint скрипта
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

