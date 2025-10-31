# 🔒 Настройка Persistent Storage в Coolify

## ⚠️ КРИТИЧЕСКИ ВАЖНО!

**Без Persistent Storage база данных будет очищаться при каждом редеплое!**

---

## 📋 Как настроить Persistent Storage в Coolify:

### Шаг 1: Откройте настройки приложения
1. В Coolify откройте ваше приложение
2. Найдите раздел **"Resources"** или **"Volumes"** или **"Persistent Storage"**
3. Или в боковом меню найдите **"Storage"** / **"Volumes"**

### Шаг 2: Добавьте Persistent Volume
1. Нажмите **"Add Volume"** или **"Add Persistent Storage"**
2. Заполните форму:
   - **Name:** `database-storage` (или любое имя)
   - **Source Path:** `/root/database-storage` (путь на хосте Coolify)
   - **Destination Path:** `/app/database` ⚠️ **ВАЖНО!**
   - **Type:** `Directory` или `Volume`

### Шаг 3: Сохраните и перезапустите
1. Нажмите **"Save"** или **"Apply"**
2. Перезапустите приложение в Coolify
3. База данных теперь будет сохраняться между деплоями

---

## ✅ Проверка работы:

После настройки Persistent Storage:

1. **Первый деплой:**
   - Entrypoint скрипт создаст базу данных
   - Импортирует данные из файлов
   - Сохранит всё в Persistent Volume

2. **Следующие деплои:**
   - Entrypoint скрипт обнаружит существующую базу
   - НЕ будет импортировать данные из файлов
   - Сохранит все изменения из админки!

---

## 🔍 Пути в контейнере:

**Важно понимать:**
- **Base Directory в Coolify:** `mysite` (или пусто)
- **Путь к проекту в контейнере:** `/app/mysite` или `/app`
- **Путь к базе данных:** `/app/database/database.sqlite` (если Base Directory = `mysite`)
- **Persistent Storage должен монтироваться в:** `/app/database` или `/app/mysite/database`

---

## 🚨 Если база данных всё ещё очищается:

1. **Проверьте Destination Path:**
   - Убедитесь что это `/app/database` (если Base Directory пустой)
   - Или `/app/mysite/database` (если Base Directory = `mysite`)

2. **Проверьте что Volume монтируется:**
   - В логах деплоя должна быть информация о монтировании volume
   - Можно проверить через Terminal: `ls -la /app/database`

3. **Проверьте права доступа:**
   - База данных должна быть доступна для записи
   - Права должны быть: `775` или `777`

---

## 📝 Дополнительные настройки:

### Для Storage директории (изображения):

Также можно добавить Persistent Storage для:
- **Destination Path:** `/app/storage`
- Это сохранит загруженные файлы между деплоями

### Для Bootstrap Cache:

Можно добавить Persistent Storage для:
- **Destination Path:** `/app/bootstrap/cache`
- Это ускорит работу приложения

---

## ⚠️ Важные замечания:

1. **База данных НЕ в Git:**
   - `database.sqlite` в `.gitignore`
   - Это правильно! Не коммитьте базу в Git

2. **Два способа хранения данных:**
   - **Файлы** (`content/`) - версионируются в Git
   - **База данных** (`database/`) - НЕ версионируется, использует Persistent Storage

3. **Изменения в админке:**
   - Сохраняются в базу данных
   - НЕ синхронизируются обратно в файлы автоматически
   - Нужно экспортировать вручную, если нужно


