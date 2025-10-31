FROM php:8.3-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Рабочая директория
WORKDIR /app

# Копирование файлов проекта
COPY . /app

# Создание database.sqlite файла перед установкой зависимостей
RUN mkdir -p database && touch database/database.sqlite

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci
RUN npm run build

# Настройка прав
RUN chmod -R 775 storage bootstrap/cache

# Expose порт
EXPOSE 8000

# Команда запуска
CMD php artisan serve --host=0.0.0.0 --port=8000

