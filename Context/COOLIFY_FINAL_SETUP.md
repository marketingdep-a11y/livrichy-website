# ✅ Финальная настройка Coolify (Base Directory = /)

## 📋 Что проверили:
- ✅ Проект находится прямо в `/app/` (нет прослойки `mysite`)
- ✅ База данных в `/app/database/database.sqlite`
- ✅ Entrypoint и Dockerfile исправлены

---

## 🔧 Что нужно исправить в Coolify:

### 1. **Persistent Storage** (критически важно!)

В Coolify:
1. Откройте приложение → **Configuration** → **Persistent Storage**
2. Найдите существующий Volume с Destination Path `/app/mysite/database`
3. Нажмите **"Update"** или **"Edit"**
4. Измените **Destination Path**:
   - **Было:** `/app/mysite/database` ❌
   - **Должно быть:** `/app/database` ✅
5. Сохраните изменения

**Или:**
- Удалите старый Volume
- Создайте новый Volume:
  - **Name:** `database-storage` (любое имя)
  - **Source Path:** `/root/database-storage` (или любой путь на хосте)
  - **Destination Path:** `/app/database` ⚠️ **ВАЖНО!**
  - Сохраните

---

### 2. **Environment Variables** (опционально)

Если хотите явно указать путь к базе данных, можно добавить в Environment Variables:
- **Key:** `DB_DATABASE`
- **Value:** `/app/database/database.sqlite`

**Но это НЕ обязательно!** Laravel автоматически использует `database/database.sqlite` (что равно `/app/database/database.sqlite`).

---

### 3. **Configuration → General** (уже правильно)

- **Base Directory:** `/` ✅ (правильно)
- **Dockerfile Location:** можно оставить `/Dockerfile` или указать `mysite/Dockerfile` (в зависимости от того, где находится Dockerfile в репозитории)
- **Build Pack:** `Dockerfile` ✅

---

## ✅ После исправления:

1. **Измените Persistent Storage** на `/app/database`
2. **Сохраните** настройки
3. **Перезапустите** или **Redeploy** приложение
4. База данных теперь будет сохраняться между деплоями! 🎉

---

## 🔍 Проверка:

После редеплоя проверьте в Terminal:
```bash
# Проверить что база данных существует
ls -la /app/database/database.sqlite

# Проверить монтирование Persistent Storage
mount | grep database

# Проверить что данные сохраняются
# Сделайте изменение в админке, затем перезапустите контейнер
# Данные должны сохраниться
```


