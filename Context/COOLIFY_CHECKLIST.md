# ✅ Чеклист проверки настроек Coolify

## 🔍 Проверка настроек перед деплоем

### 1. Configuration → General

#### Base Directory ⚠️ КРИТИЧЕСКИ ВАЖНО
- ✅ Должно быть: `mysite` (без `/` в начале)
- ❌ Неправильно: `/mysite`
- ❌ Неправильно: `/`
- **Действие:** Если указано `/mysite`, измените на `mysite` (уберите `/` в начале)

#### Build Command
- ✅ Рекомендуется указать:
  ```
  composer install --no-dev --optimize-autoloader --no-interaction && npm ci && npm run build
  ```
- Или оставить пустым (Nixpacks определит автоматически)

#### Install Command
- ✅ Оставить пустым

#### Start Command
- ✅ Оставить пустым (Nixpacks определит автоматически)

#### Publish Directory
- ✅ Должно быть: `/` или `/public`
- Для Laravel обычно `/` достаточно

#### Build Pack
- ✅ Должно быть: `Nixpacks`

#### Is it a static site?
- ❌ Должно быть НЕ отмечено (Laravel - динамическое приложение)

---

### 2. Environment Variables

#### Обязательные переменные для SQLite:

```env
APP_NAME=Statamic
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=  # Можно оставить пустым, сгенерируется при первом деплое

DB_CONNECTION=sqlite
DB_DATABASE=/app/mysite/database/database.sqlite
DB_FOREIGN_KEYS=true

STATAMIC_LICENSE_KEY=your-license-key
STATAMIC_STACHE_WATCHER=false
STATAMIC_ANTLERS_DEBUGBAR=false
STATAMIC_STATIC_CACHING_STRATEGY=full
```

#### Проверка путей:
- ✅ `DB_DATABASE=/app/mysite/database/database.sqlite` (путь В контейнере)
- ⚠️ `/app` - корень контейнера
- ⚠️ `mysite` - ваш Base Directory
- ⚠️ `database/database.sqlite` - относительный путь от Base Directory

---

### 3. Persistent Storage

#### Volume Mount
- ✅ **Name:** `database-storage` (или любое имя)
- ✅ **Source Path:** `/root` (или любой путь на хосте)
- ✅ **Destination Path:** `/app/mysite/database` (путь В контейнере)

#### Важно:
- Destination Path должен быть: `/app/mysite/database` (не `/app/mysite/database/`)
- Это сохранит всю папку `database` между перезапусками

---

### 4. Проверка логики путей

**В контейнере Coolify:**
- Весь репозиторий клонируется в `/app`
- Если Base Directory = `mysite`, то:
  - Проект находится в: `/app/mysite`
  - База данных в: `/app/mysite/database/database.sqlite`
  - Persistent Storage для: `/app/mysite/database`

**Если Base Directory = `/mysite` (неправильно):**
- Coolify может интерпретировать это как абсолютный путь
- Может искать проект не в том месте
- Nixpacks не сможет найти Laravel файлы

---

## ✅ Итоговая проверка перед деплоем:

- [ ] Base Directory = `mysite` (без `/` в начале)
- [ ] Build Command указан или пустой
- [ ] Environment Variables заполнены
- [ ] `DB_DATABASE=/app/mysite/database/database.sqlite`
- [ ] Persistent Storage настроен: `/app/mysite/database`
- [ ] `APP_URL` указывает на ваш домен Coolify
- [ ] `STATAMIC_LICENSE_KEY` заполнен

---

## 🚀 После проверки:

1. Нажмите **"Save"** в разделе General
2. Нажмите **"Save All Environment Variables"** в разделе Environment Variables
3. Нажмите **"Deploy"** для запуска деплоя

---

## 🔧 Если всё ещё ошибка "Nixpacks failed to detect":

1. **Убедитесь Base Directory = `mysite`** (точно без `/`)
2. **Удалите Build Command** временно - пусть Nixpacks определит сам
3. **Проверьте что в репозитории есть:**
   - `mysite/composer.json`
   - `mysite/artisan`
   - `mysite/package.json`
4. **Попробуйте деплой снова**


