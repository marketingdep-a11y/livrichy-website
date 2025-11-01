# ✅ Интеграция Google Sheets для импорта агентов - ГОТОВО

## Что было реализовано

### 1. ✅ Установлен пакет Google API Client
- Установлен `google/apiclient` через Composer
- Пакет готов к использованию

### 2. ✅ Создан сервис импорта
**Файл:** `app/Services/AgentImport/GoogleSheetsAgentImporter.php`

**Функциональность:**
- Подключение к Google Sheets API
- Чтение данных из таблицы
- Маппинг полей:
  - `Agent' ID` → `external_id`
  - `Name Of Agent` → `title`
  - `Position` → `position`
  - `Photo` → `image` (автоматическое скачивание)
  - `Bio` → `content` (форматирование в Bard)
  - `Status` → фильтрация по "Published"
- Импорт только агентов со статусом "Published"
- **Автоматическое удаление** агентов, которых нет в списке "Published"
- Детальная отчетность по результатам синхронизации

### 3. ✅ Создана консольная команда
**Файл:** `app/Console/Commands/SyncGoogleSheetsAgents.php`

**Использование:**
```bash
php artisan import:google-sheets-agents
```

### 4. ✅ Настроено автоматическое расписание
**Файл:** `app/Console/Kernel.php`

**Расписание:**
- Каждую **среду** в **18:00 UAE** (14:00 UTC)
- С защитой от повторного запуска (withoutOverlapping)
- Выполняется только на одном сервере (onOneServer)
- Работает в фоновом режиме (runInBackground)

### 5. ✅ Добавлена конфигурация
**Файл:** `config/services.php`

**Параметры:**
- `spreadsheet_id` - ID Google Sheets документа
- `range` - диапазон ячеек для чтения (по умолчанию: Table1!A2:H)
- `credentials_path` - путь к JSON файлу с credentials

### 6. ✅ Создана документация
**Файлы:**
- `GOOGLE_SHEETS_SETUP.md` - подробная инструкция по настройке
- `env.google-sheets.example` - пример переменных окружения

## Что нужно сделать дальше

### 1. Получить Google API Credentials

Следуйте инструкциям в файле `GOOGLE_SHEETS_SETUP.md`:
- Создайте Google Cloud Project
- Включите Google Sheets API
- Создайте Service Account
- Скачайте JSON файл с credentials
- Предоставьте доступ к таблице

### 2. Установить credentials файл

```bash
# Поместите скачанный JSON файл в:
storage/app/google-credentials.json
```

### 3. Настроить переменные окружения

Добавьте в ваш `.env` файл:

```env
GOOGLE_SHEETS_AGENTS_SPREADSHEET_ID=ваш_spreadsheet_id
GOOGLE_SHEETS_AGENTS_RANGE=Table1!A2:H
GOOGLE_SHEETS_AGENTS_CREDENTIALS_PATH=/Users/paveltraskou/Documents/Livrichy/mysite/storage/app/google-credentials.json
```

### 4. Протестировать импорт

```bash
php artisan import:google-sheets-agents
```

## Логика работы

1. **Чтение данных:** Система подключается к Google Sheets и читает все строки из указанного диапазона
2. **Фильтрация:** Обрабатываются только агенты со статусом "Published"
3. **Синхронизация:**
   - Создаются новые агенты
   - Обновляются существующие агенты
   - **Удаляются** агенты, которых нет в списке "Published"
4. **Отчетность:** Выводится детальный отчет о результатах синхронизации

## Формат таблицы

| Колонка | Название | Описание |
|---------|----------|----------|
| A | Agent' ID | Уникальный ID агента |
| B | LastUpdated | Дата последнего обновления |
| C | Department | Отдел (игнорируется) |
| D | Name Of Agent | Имя агента |
| E | Position | Должность |
| F | Photo | URL фотографии |
| G | Bio | Биография |
| H | Status | Статус ("Published" для публикации) |

## Автоматическое расписание

Команда автоматически запускается каждую **среду в 18:00 UAE**.

Убедитесь, что Laravel Scheduler запущен в cron:

```bash
* * * * * cd /Users/paveltraskou/Documents/Livrichy/mysite && php artisan schedule:run >> /dev/null 2>&1
```

## Проверка расписания

```bash
# Посмотреть все запланированные задачи
php artisan schedule:list

# Запустить задачи вручную (для тестирования)
php artisan schedule:run
```

## Техническая информация

**Файлы:**
- `app/Services/AgentImport/GoogleSheetsAgentImporter.php` - основной сервис
- `app/Console/Commands/SyncGoogleSheetsAgents.php` - консольная команда
- `app/Console/Kernel.php` - регистрация команды и расписания
- `config/services.php` - конфигурация

**Зависимости:**
- `google/apiclient` v2.18.4

**Логирование:**
Все ошибки и важные события логируются в Laravel log.

---

🎉 **Интеграция готова к использованию!**

Следуйте инструкциям в `GOOGLE_SHEETS_SETUP.md` для получения Google API credentials.

