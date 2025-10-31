# 🔍 Как проверить структуру директорий в контейнере

## Через Terminal в Coolify:

### 1. Подключитесь к контейнеру
- Откройте Terminal в Coolify
- Выберите ваш контейнер приложения
- Нажмите "Connect"

### 2. Выполните эти команды для проверки:

```bash
# Проверить текущую директорию
pwd

# Проверить что находится в /app
ls -la /app

# Проверить существует ли /app/mysite
ls -la /app/mysite

# Проверить структуру проекта
find /app -maxdepth 2 -type d -name "mysite" 2>/dev/null

# Проверить где находится Laravel (composer.json)
find /app -name "composer.json" -type f 2>/dev/null

# Проверить где находится база данных
find /app -name "database.sqlite" -type f 2>/dev/null

# Проверить где находится docker-entrypoint.sh
find /app -name "docker-entrypoint.sh" -type f 2>/dev/null

# Показать полную структуру (первые 2 уровня)
tree -L 2 /app 2>/dev/null || find /app -maxdepth 2 -type d | sort
```

### 3. Интерпретация результатов:

**Если видите:**
```
/app/
  mysite/
    composer.json
    package.json
    database/
    ...
```
→ Структура: `/app/mysite/` - значит Base Directory = `/`

**Если видите:**
```
/app/
  composer.json
  package.json
  database/
  ...
```
→ Структура: `/app/` - значит Base Directory пустой или проект в корне

**Если база данных:**
```
/app/mysite/database/database.sqlite
```
→ Persistent Storage должен быть: `/app/mysite/database`

**Если база данных:**
```
/app/database/database.sqlite
```
→ Persistent Storage должен быть: `/app/database`

### 4. Проверка в entrypoint скрипте:

Также можно проверить логи при запуске:
```bash
# Посмотреть последние логи при старте контейнера
# В Coolify: "Logs" → смотрите вывод entrypoint скрипта

# Там должно быть:
# "📂 Working directory: /app/mysite" или "📂 Working directory: /app"
```

### 5. Проверка через файловую систему:

```bash
# Проверить монтирование Persistent Storage
mount | grep database

# Проверить существует ли Persistent Volume
ls -la /app/mysite/database/
ls -la /app/database/

# Проверить содержимое базы данных (если она есть)
file /app/mysite/database/database.sqlite 2>/dev/null || file /app/database/database.sqlite 2>/dev/null
```


