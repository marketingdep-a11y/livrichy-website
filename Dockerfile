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

# Копирование файлов проекта
# При Base Directory = / в Coolify, структура будет /app/mysite/...
COPY . /app

# Определяем директорию проекта (может быть /app или /app/mysite)
# Если Base Directory = /, то проект в /app/mysite
RUN if [ -d "/app/mysite" ]; then \
        WORK_DIR="/app/mysite"; \
    else \
        WORK_DIR="/app"; \
    fi && \
    cd "$WORK_DIR" && \
    mkdir -p database && \
    composer install --no-dev --optimize-autoloader --no-interaction && \
    rm -rf node_modules package-lock.json && \
    npm install && \
    npm run build && \
    chmod -R 775 storage bootstrap/cache

# Рабочая директория будет определена в entrypoint
WORKDIR /app

# Копирование entrypoint скрипта
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose порт
EXPOSE 8000

# Использование entrypoint скрипта
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

